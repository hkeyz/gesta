import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../storage/credential_store.dart';
import '../storage/api_cache.dart';
import 'api_exception.dart';

class ApiClient {
  ApiClient(this._credentials, this._cache)
    : _dio = Dio(
        BaseOptions(
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 20),
          headers: const {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        ),
      ) {
    setBaseUrl(AppConfig.defaultApiUrl);
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _credentials.readToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final CredentialStore _credentials;
  final ApiCache _cache;
  final Dio _dio;
  final _unauthorizedController = StreamController<void>.broadcast();
  final _connectionController = StreamController<bool>.broadcast();

  Stream<void> get unauthorized => _unauthorizedController.stream;
  Stream<bool> get connectionChanges => _connectionController.stream;

  void setBaseUrl(String url) {
    _dio.options.baseUrl = AppConfig.normalizeApiUrl(url);
  }

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, dynamic>? query,
    bool useCache = true,
  }) async {
    final key = _cacheKey(path, query);
    try {
      final response = await _request(
        () => _dio.get(path, queryParameters: query),
      );
      _connectionController.add(true);
      if (useCache && !path.startsWith('/auth/')) {
        await _cache.write(key, response);
      }
      return response;
    } on ApiException catch (error) {
      if (!error.isNetworkError || !useCache || path.startsWith('/auth/')) {
        rethrow;
      }
      final cached = await _cache.read(key);
      if (cached == null) rethrow;
      _connectionController.add(false);
      final response = Map<String, dynamic>.from(cached.body);
      final meta = response['meta'] is Map
          ? Map<String, dynamic>.from(response['meta'])
          : <String, dynamic>{};
      response['meta'] = {
        ...meta,
        'offline': true,
        'cached_at': cached.savedAt.toIso8601String(),
      };
      return response;
    }
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    final response = await _request(() => _dio.post(path, data: data));
    _connectionController.add(true);
    return response;
  }

  Future<void> clearCache() => _cache.clear();

  Future<Map<String, dynamic>> _request(
    Future<Response<dynamic>> Function() operation,
  ) async {
    try {
      final response = await operation();
      final body = response.data;
      if (body is! Map) {
        throw const ApiException('Réponse serveur invalide.');
      }
      return Map<String, dynamic>.from(body);
    } on DioException catch (error) {
      final body = error.response?.data;
      final payload = body is Map ? body['error'] : null;
      final message = payload is Map ? payload['message']?.toString() : null;
      final isNetworkError =
          error.response == null &&
          {
            DioExceptionType.connectionTimeout,
            DioExceptionType.receiveTimeout,
            DioExceptionType.sendTimeout,
            DioExceptionType.connectionError,
            DioExceptionType.unknown,
          }.contains(error.type);
      if (error.response?.statusCode == 401) {
        final token = await _credentials.readToken();
        if (token?.isNotEmpty == true) {
          _unauthorizedController.add(null);
        }
      }
      if (isNetworkError) {
        _connectionController.add(false);
      }
      throw ApiException(
        message?.isNotEmpty == true ? message! : _networkMessage(error.type),
        statusCode: error.response?.statusCode,
        code: payload is Map ? payload['code']?.toString() : null,
        isNetworkError: isNetworkError,
      );
    }
  }

  String _networkMessage(DioExceptionType type) {
    return switch (type) {
      DioExceptionType.connectionTimeout ||
      DioExceptionType.receiveTimeout ||
      DioExceptionType.sendTimeout =>
        'Le serveur met trop de temps à répondre.',
      DioExceptionType.connectionError =>
        'Impossible de joindre le serveur. Vérifiez l’adresse et le réseau.',
      _ => 'Une erreur réseau est survenue.',
    };
  }

  String _cacheKey(String path, Map<String, dynamic>? query) {
    final sorted = query == null
        ? <String, dynamic>{}
        : Map.fromEntries(
            query.entries.toList()..sort((a, b) => a.key.compareTo(b.key)),
          );
    return '${_dio.options.baseUrl}|$path|${jsonEncode(sorted)}';
  }
}
