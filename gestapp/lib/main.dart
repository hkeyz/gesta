import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';
import 'core/notifications/notification_service.dart';
import 'core/storage/credential_store.dart';
import 'core/storage/legacy_data_cleaner.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await LegacyDataCleaner().purge();
  await CredentialStore().purgeLegacyUser();
  await NotificationService.instance.initialize();
  await NotificationService.instance.cancelAll();
  runApp(const ProviderScope(child: GestaApp()));
}
