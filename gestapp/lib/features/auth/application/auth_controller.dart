import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/providers.dart';
import '../data/auth_repository.dart';
import '../domain/auth_state.dart';

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(
    ref.watch(apiClientProvider),
    ref.watch(credentialStoreProvider),
  ),
);

final authControllerProvider = AsyncNotifierProvider<AuthController, AuthState>(
  AuthController.new,
);

class AuthController extends AsyncNotifier<AuthState> {
  StreamSubscription<void>? _unauthorizedSubscription;

  @override
  Future<AuthState> build() async {
    final credentials = ref.read(credentialStoreProvider);
    final server = AppConfig.normalizeApiUrl(
      await credentials.readServer() ?? AppConfig.defaultApiUrl,
    );
    final token = await credentials.readToken();
    if (token == null || token.isEmpty) {
      _listenForExpiration(server);
      return AuthState.signedOut(server);
    }

    try {
      final restored = await ref.read(authRepositoryProvider).restore(server);
      _listenForExpiration(server);
      return restored;
    } catch (error) {
      if (error is ApiException && error.isNetworkError) {
        final cachedUser = await credentials.readUser();
        if (cachedUser != null) {
          _listenForExpiration(server);
          return AuthState.signedIn(server, cachedUser);
        }
      }
      await credentials.clearToken();
      _listenForExpiration(server);
      return AuthState.signedOut(
        server,
        message: 'Votre session a expiré. Reconnectez-vous.',
      );
    }
  }

  void _listenForExpiration(String server) {
    _unauthorizedSubscription ??= ref
        .read(apiClientProvider)
        .unauthorized
        .listen((_) async {
          await ref.read(credentialStoreProvider).clearToken();
          await ref.read(apiClientProvider).clearCache();
          if (ref.mounted) {
            state = AsyncData(
              AuthState.signedOut(
                server,
                message: 'Votre session a expiré. Reconnectez-vous.',
              ),
            );
          }
        });
    ref.onDispose(() => _unauthorizedSubscription?.cancel());
  }

  Future<void> login({
    required String username,
    required String password,
    required String serverUrl,
  }) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref
          .read(authRepositoryProvider)
          .login(
            username: username,
            password: password,
            serverUrl: AppConfig.normalizeApiUrl(serverUrl),
          ),
    );
  }

  Future<void> logout() async {
    final current = state.value;
    final server = current?.serverUrl ?? AppConfig.defaultApiUrl;
    state = const AsyncLoading();
    try {
      await ref.read(authRepositoryProvider).logout();
    } finally {
      state = AsyncData(AuthState.signedOut(server));
    }
  }
}
