import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class NotificationPreferences {
  const NotificationPreferences({
    this.enabled = true,
    this.saleThreshold = 100000,
    this.maxRegisterMinutes = 720,
  });

  final bool enabled;
  final double saleThreshold;
  final int maxRegisterMinutes;

  NotificationPreferences copyWith({
    bool? enabled,
    double? saleThreshold,
    int? maxRegisterMinutes,
  }) {
    return NotificationPreferences(
      enabled: enabled ?? this.enabled,
      saleThreshold: saleThreshold ?? this.saleThreshold,
      maxRegisterMinutes: maxRegisterMinutes ?? this.maxRegisterMinutes,
    );
  }
}

class NotificationPreferencesController
    extends AsyncNotifier<NotificationPreferences> {
  static const _enabledKey = 'gesta_notifications_enabled';
  static const _saleKey = 'gesta_notifications_sale_threshold';
  static const _registerKey = 'gesta_notifications_register_minutes';

  @override
  Future<NotificationPreferences> build() async {
    final preferences = await SharedPreferences.getInstance();
    return NotificationPreferences(
      enabled: preferences.getBool(_enabledKey) ?? true,
      saleThreshold: preferences.getDouble(_saleKey) ?? 100000,
      maxRegisterMinutes: preferences.getInt(_registerKey) ?? 720,
    );
  }

  Future<void> setEnabled(bool value) async {
    final current = state.value ?? const NotificationPreferences();
    state = AsyncData(current.copyWith(enabled: value));
    final preferences = await SharedPreferences.getInstance();
    await preferences.setBool(_enabledKey, value);
  }

  Future<void> configure({
    required double saleThreshold,
    required int maxRegisterMinutes,
  }) async {
    final current = state.value ?? const NotificationPreferences();
    state = AsyncData(
      current.copyWith(
        saleThreshold: saleThreshold,
        maxRegisterMinutes: maxRegisterMinutes,
      ),
    );
    final preferences = await SharedPreferences.getInstance();
    await Future.wait([
      preferences.setDouble(_saleKey, saleThreshold),
      preferences.setInt(_registerKey, maxRegisterMinutes),
    ]);
  }
}

final notificationPreferencesProvider =
    AsyncNotifierProvider<
      NotificationPreferencesController,
      NotificationPreferences
    >(NotificationPreferencesController.new);
