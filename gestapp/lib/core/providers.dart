import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'network/api_client.dart';
import 'storage/credential_store.dart';
import 'storage/api_cache.dart';

final apiCacheProvider = Provider<ApiCache>((ref) => ApiCache());

final credentialStoreProvider = Provider<CredentialStore>(
  (ref) => CredentialStore(),
);

final apiClientProvider = Provider<ApiClient>(
  (ref) => ApiClient(
    ref.watch(credentialStoreProvider),
    ref.watch(apiCacheProvider),
  ),
);

final deviceConnectivityProvider = StreamProvider<bool>((ref) async* {
  final connectivity = Connectivity();
  final initial = await connectivity.checkConnectivity();
  yield !initial.contains(ConnectivityResult.none);
  yield* connectivity.onConnectivityChanged.map(
    (results) => !results.contains(ConnectivityResult.none),
  );
});

final serverConnectionProvider = StreamProvider<bool>(
  (ref) => ref.watch(apiClientProvider).connectionChanges,
);
