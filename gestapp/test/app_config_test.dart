import 'package:gestapp/core/config/app_config.dart';
import 'package:gestapp/features/management/domain/models.dart';
import 'package:test/test.dart';

void main() {
  test('utilise le serveur de production par défaut', () {
    expect(
      AppConfig.defaultApiUrl,
      'https://gesta.diakasoft.com/public/api/mobile/v1',
    );
  });

  test('normalise une URL API avec slash final', () {
    expect(
      AppConfig.normalizeApiUrl('https://gesta.test/api/mobile/v1///'),
      'https://gesta.test/api/mobile/v1',
    );
  });

  test('décode le contexte métier retourné par bootstrap', () {
    final context = BusinessContext.fromJson({
      'user': {'full_name': 'Patron Test'},
      'business': {
        'name': 'Boutique Test',
        'currency': {'symbol': '€', 'code': 'EUR', 'precision': 2},
      },
      'locations': [
        {'id': 4, 'name': 'Centre'},
      ],
      'features': {'dashboard': true},
      'realtime': {
        'dashboard_refresh_seconds': 15,
        'activity_refresh_seconds': 10,
      },
    });

    expect(context.businessName, 'Boutique Test');
    expect(context.userName, 'Patron Test');
    expect(context.locations.single.id, 4);
    expect(context.dashboardRefreshSeconds, 15);
  });

  test('fusionne deux pages sans perdre la pagination', () {
    const first = PageResult<int>(
      items: [1, 2],
      currentPage: 1,
      lastPage: 2,
      total: 3,
    );
    const second = PageResult<int>(
      items: [3],
      currentPage: 2,
      lastPage: 2,
      total: 3,
    );

    final merged = first.append(second);
    expect(merged.items, [1, 2, 3]);
    expect(merged.hasMore, isFalse);
  });
}
