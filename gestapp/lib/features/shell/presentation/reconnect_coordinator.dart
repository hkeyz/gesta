import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../../management/application/management_providers.dart';

class ReconnectCoordinator extends ConsumerWidget {
  const ReconnectCoordinator({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    ref.listen<AsyncValue<bool>>(deviceConnectivityProvider, (previous, next) {
      if (previous?.value == false && next.value == true) {
        _refresh(ref);
      }
    });
    ref.listen<AsyncValue<bool>>(serverConnectionProvider, (previous, next) {
      if (previous?.value == false && next.value == true) {
        _refresh(ref);
      }
    });
    return const SizedBox.shrink();
  }

  void _refresh(WidgetRef ref) {
    ref.invalidate(bootstrapProvider);
    ref.invalidate(dashboardProvider);
    ref.invalidate(activityProvider);
    ref.invalidate(transactionsProvider);
    ref.invalidate(inventorySummaryProvider);
    ref.invalidate(productsProvider);
    ref.invalidate(contactsProvider);
    ref.invalidate(openCashRegistersProvider);
  }
}
