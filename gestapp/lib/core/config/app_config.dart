class AppConfig {
  const AppConfig._();

  static const appName = 'Gesta Pilot';
  static const defaultApiUrl = String.fromEnvironment(
    'GESTA_API_URL',
    defaultValue: 'https://gesta.diakasoft.com/public/api/mobile/v1',
  );

  static String normalizeApiUrl(String value) {
    var normalized = value.trim();
    while (normalized.endsWith('/')) {
      normalized = normalized.substring(0, normalized.length - 1);
    }
    return normalized;
  }
}
