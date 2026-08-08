import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class CredentialStore {
  CredentialStore({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  static const _tokenKey = 'gesta_mobile_token';
  static const _serverKey = 'gesta_mobile_server';
  static const _userKey = 'gesta_mobile_user';
  final FlutterSecureStorage _storage;

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<String?> readServer() => _storage.read(key: _serverKey);

  Future<Map<String, dynamic>?> readUser() async {
    final raw = await _storage.read(key: _userKey);
    if (raw == null) return null;
    try {
      final decoded = jsonDecode(raw);
      return decoded is Map ? Map<String, dynamic>.from(decoded) : null;
    } catch (_) {
      return null;
    }
  }

  Future<void> save({
    required String token,
    required String server,
    required Map<String, dynamic> user,
  }) async {
    await Future.wait([
      _storage.write(key: _tokenKey, value: token),
      _storage.write(key: _serverKey, value: server),
      _storage.write(key: _userKey, value: jsonEncode(user)),
    ]);
  }

  Future<void> clearToken() => _storage.delete(key: _tokenKey);
}
