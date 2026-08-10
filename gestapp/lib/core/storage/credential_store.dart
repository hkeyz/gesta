import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class CredentialStore {
  CredentialStore({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  static const _tokenKey = 'gesta_mobile_token';
  static const _serverKey = 'gesta_mobile_server';
  static const _legacyUserKey = 'gesta_mobile_user';
  final FlutterSecureStorage _storage;

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<String?> readServer() => _storage.read(key: _serverKey);

  Future<void> save({required String token, required String server}) async {
    await Future.wait([
      _storage.write(key: _tokenKey, value: token),
      _storage.write(key: _serverKey, value: server),
      _storage.delete(key: _legacyUserKey),
    ]);
  }

  Future<void> clearToken() async {
    await Future.wait([
      _storage.delete(key: _tokenKey),
      _storage.delete(key: _legacyUserKey),
    ]);
  }

  Future<void> purgeLegacyUser() => _storage.delete(key: _legacyUserKey);
}
