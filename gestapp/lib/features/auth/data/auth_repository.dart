import '../../../core/network/api_client.dart';
import '../../../core/storage/credential_store.dart';
import '../domain/auth_state.dart';

class AuthRepository {
  AuthRepository(this._client, this._credentials);

  final ApiClient _client;
  final CredentialStore _credentials;

  Future<AuthState> restore(String serverUrl) async {
    _client.setBaseUrl(serverUrl);
    final response = await _client.get('/auth/me');
    final data = _data(response);
    return AuthState.signedIn(serverUrl, _map(data['user']));
  }

  Future<AuthState> login({
    required String username,
    required String password,
    required String serverUrl,
  }) async {
    _client.setBaseUrl(serverUrl);
    final response = await _client.post(
      '/auth/login',
      data: {
        'username': username.trim(),
        'password': password,
        'device_name': 'Gesta Pilot',
      },
    );
    final data = _data(response);
    final token = data['access_token']?.toString();
    if (token == null || token.isEmpty) {
      throw const FormatException('Le serveur n’a retourné aucun jeton.');
    }
    final user = _map(data['user']);
    await _credentials.save(token: token, server: serverUrl, user: user);
    return AuthState.signedIn(serverUrl, user);
  }

  Future<void> logout() async {
    try {
      await _client.post('/auth/logout');
    } finally {
      await _credentials.clearToken();
      await _client.clearCache();
    }
  }

  Map<String, dynamic> _data(Map<String, dynamic> response) {
    return _map(response['data']);
  }

  Map<String, dynamic> _map(dynamic value) {
    return value is Map ? Map<String, dynamic>.from(value) : {};
  }
}
