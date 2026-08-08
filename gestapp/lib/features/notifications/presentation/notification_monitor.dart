import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/formatters.dart';
import '../../../core/notifications/notification_service.dart';
import '../../management/application/management_providers.dart';
import '../../management/domain/models.dart';
import '../application/notification_providers.dart';

class NotificationMonitor extends ConsumerStatefulWidget {
  const NotificationMonitor({super.key});

  @override
  ConsumerState<NotificationMonitor> createState() =>
      _NotificationMonitorState();
}

class _NotificationMonitorState extends ConsumerState<NotificationMonitor> {
  final Set<int> _seenTransactions = {};
  final Set<int> _warnedRegisters = {};
  int? _lastLowStock;
  bool _permissionRequested = false;

  @override
  Widget build(BuildContext context) {
    final preferences = ref.watch(notificationPreferencesProvider).value;
    final business = ref.watch(bootstrapProvider).value;
    if (preferences?.enabled == true && !_permissionRequested) {
      _permissionRequested = true;
      Future.microtask(NotificationService.instance.requestPermission);
    }

    if (business?.features['dashboard'] ?? false) {
      ref.listen<AsyncValue<DashboardData>>(dashboardProvider, (_, next) {
        final data = next.value;
        final settings = ref.read(notificationPreferencesProvider).value;
        final context = ref.read(bootstrapProvider).value;
        if (data == null || settings?.enabled != true || context == null) {
          return;
        }
        _handleDashboard(data, settings!, AppFormatters(context));
      });
    }
    if ((business?.features['cash_registers'] ?? false) ||
        (business?.features['dashboard'] ?? false)) {
      ref.listen<AsyncValue<List<Map<String, dynamic>>>>(
        openCashRegistersProvider,
        (_, next) {
          final settings = ref.read(notificationPreferencesProvider).value;
          if (settings?.enabled != true || next.value == null) return;
          _handleRegisters(next.value!, settings!);
        },
      );
    }

    return const SizedBox.shrink();
  }

  void _handleDashboard(
    DashboardData data,
    NotificationPreferences settings,
    AppFormatters formatters,
  ) {
    final currentIds = data.recentTransactions.map((item) => item.id).toSet();
    if (_seenTransactions.isEmpty) {
      _seenTransactions.addAll(currentIds);
    } else {
      for (final item in data.recentTransactions) {
        if (!_seenTransactions.contains(item.id) &&
            item.type == 'sell' &&
            item.amount >= settings.saleThreshold) {
          NotificationService.instance.show(
            title: 'Vente importante',
            body: '${item.reference} · ${formatters.money(item.amount)}',
            payload: 'transaction:${item.id}',
          );
        }
      }
      _seenTransactions.addAll(currentIds);
    }

    final lowStock = intValue(data.alerts['low_stock_count']);
    if (_lastLowStock != null && lowStock > _lastLowStock!) {
      NotificationService.instance.show(
        title: 'Alerte de stock',
        body: '$lowStock produit(s) sont maintenant sous le seuil.',
        payload: 'stock:$lowStock',
      );
    }
    _lastLowStock = lowStock;
  }

  void _handleRegisters(
    List<Map<String, dynamic>> registers,
    NotificationPreferences settings,
  ) {
    for (final register in registers) {
      final id = intValue(register['id']);
      final minutes = intValue(register['duration_minutes']);
      final net = numberValue(mapValue(register['cash_movements'])['net']);
      if (!_warnedRegisters.contains(id) &&
          (minutes >= settings.maxRegisterMinutes || net < 0)) {
        final user = mapValue(register['user'])['name'] ?? 'Une caisse';
        NotificationService.instance.show(
          title: 'Caisse à vérifier',
          body: net < 0
              ? '$user présente un solde négatif.'
              : '$user est ouverte depuis ${minutes ~/ 60} h.',
          payload: 'register:$id',
        );
        _warnedRegisters.add(id);
      }
    }
  }
}
