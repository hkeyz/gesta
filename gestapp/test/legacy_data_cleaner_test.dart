import 'package:flutter_test/flutter_test.dart';
import 'package:gestapp/core/storage/legacy_data_cleaner.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('supprime les anciennes données persistées', () async {
    const cachedKey = 'gesta_api_cache_previous_response';
    SharedPreferences.setMockInitialValues({
      'gesta_api_cache_keys': [cachedKey],
      cachedKey: '{"body":{"tenant":1}}',
      'gesta_api_cache_orphan': '{"body":{"tenant":2}}',
      'gesta_notifications_enabled': false,
    });

    await LegacyDataCleaner().purge();

    final preferences = await SharedPreferences.getInstance();
    expect(preferences.containsKey('gesta_api_cache_keys'), isFalse);
    expect(preferences.containsKey(cachedKey), isFalse);
    expect(preferences.containsKey('gesta_api_cache_orphan'), isFalse);
    expect(preferences.containsKey('gesta_notifications_enabled'), isFalse);
  });
}
