import 'package:flutter_riverpod/flutter_riverpod.dart';

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
  @override
  Future<NotificationPreferences> build() async =>
      const NotificationPreferences();

  Future<void> setEnabled(bool value) async {
    final current = state.value ?? const NotificationPreferences();
    state = AsyncData(current.copyWith(enabled: value));
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
  }
}

final notificationPreferencesProvider =
    AsyncNotifierProvider<
      NotificationPreferencesController,
      NotificationPreferences
    >(NotificationPreferencesController.new);
