import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

class CachedApiResponse {
  const CachedApiResponse({required this.body, required this.savedAt});

  final Map<String, dynamic> body;
  final DateTime savedAt;
}

class ApiCache {
  static const _keysRegistry = 'gesta_api_cache_keys';
  static const _prefix = 'gesta_api_cache_';

  Future<void> write(String key, Map<String, dynamic> body) async {
    final preferences = await SharedPreferences.getInstance();
    final storageKey = _storageKey(key);
    await preferences.setString(
      storageKey,
      jsonEncode({'saved_at': DateTime.now().toIso8601String(), 'body': body}),
    );
    final keys = preferences.getStringList(_keysRegistry) ?? [];
    if (!keys.contains(storageKey)) {
      await preferences.setStringList(_keysRegistry, [...keys, storageKey]);
    }
  }

  Future<CachedApiResponse?> read(String key) async {
    final preferences = await SharedPreferences.getInstance();
    final raw = preferences.getString(_storageKey(key));
    if (raw == null) return null;

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) return null;
      final body = decoded['body'];
      final savedAt = DateTime.tryParse(decoded['saved_at']?.toString() ?? '');
      if (body is! Map || savedAt == null) return null;
      return CachedApiResponse(
        body: Map<String, dynamic>.from(body),
        savedAt: savedAt,
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> clear() async {
    final preferences = await SharedPreferences.getInstance();
    final keys = preferences.getStringList(_keysRegistry) ?? [];
    for (final key in keys) {
      await preferences.remove(key);
    }
    await preferences.remove(_keysRegistry);
  }

  String _storageKey(String key) {
    return '$_prefix${base64Url.encode(utf8.encode(key))}';
  }
}
