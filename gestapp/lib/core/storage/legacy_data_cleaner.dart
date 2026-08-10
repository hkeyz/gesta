import 'package:shared_preferences/shared_preferences.dart';

/// Removes data persisted by versions that supported offline mode and settings.
class LegacyDataCleaner {
  static const _apiCacheKeysRegistry = 'gesta_api_cache_keys';
  static const _apiCachePrefix = 'gesta_api_cache_';
  static const _notificationPrefix = 'gesta_notifications_';

  Future<void> purge() async {
    final preferences = await SharedPreferences.getInstance();
    final registeredKeys =
        preferences.getStringList(_apiCacheKeysRegistry) ?? const [];
    final keys = <String>{
      ...registeredKeys,
      ...preferences.getKeys().where(
        (key) =>
            key.startsWith(_apiCachePrefix) ||
            key.startsWith(_notificationPrefix),
      ),
    };

    for (final key in keys) {
      await preferences.remove(key);
    }
    await preferences.remove(_apiCacheKeysRegistry);
  }
}
