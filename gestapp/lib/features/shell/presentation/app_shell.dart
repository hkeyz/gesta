import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../management/application/management_providers.dart';
import '../../management/presentation/activity_screen.dart';
import '../../management/presentation/contacts_screen.dart';
import '../../management/presentation/dashboard_screen.dart';
import '../../management/presentation/inventory_screen.dart';
import '../../management/presentation/profile_screen.dart';
import '../../management/presentation/transactions_screen.dart';
import '../../management/presentation/widgets/common.dart';
import '../../notifications/presentation/notification_monitor.dart';
import 'reconnect_coordinator.dart';

class AppShell extends ConsumerStatefulWidget {
  const AppShell({super.key});

  @override
  ConsumerState<AppShell> createState() => _AppShellState();
}

class _AppShellState extends ConsumerState<AppShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final bootstrap = ref.watch(bootstrapProvider);

    return bootstrap.when(
      loading: () =>
          const Scaffold(body: Center(child: CircularProgressIndicator())),
      error: (error, _) => Scaffold(
        body: ErrorPanel(
          error: error,
          onRetry: () => ref.invalidate(bootstrapProvider),
        ),
      ),
      data: (business) {
        final entries = [
          if (business.features['dashboard'] ?? false)
            const _NavEntry(
              destination: NavigationDestination(
                icon: Icon(Icons.space_dashboard_outlined),
                selectedIcon: Icon(Icons.space_dashboard_rounded),
                label: 'Vue d’ensemble',
              ),
              page: DashboardScreen(),
            ),
          const _NavEntry(
            destination: NavigationDestination(
              icon: Icon(Icons.bolt_outlined),
              selectedIcon: Icon(Icons.bolt),
              label: 'Activité',
            ),
            page: ActivityScreen(),
          ),
          if ((business.features['sales'] ?? false) ||
              (business.features['purchases'] ?? false) ||
              (business.features['expenses'] ?? false))
            const _NavEntry(
              destination: NavigationDestination(
                icon: Icon(Icons.receipt_long_outlined),
                selectedIcon: Icon(Icons.receipt_long),
                label: 'Opérations',
              ),
              page: TransactionsScreen(),
            ),
          if (business.features['inventory'] ?? false)
            const _NavEntry(
              destination: NavigationDestination(
                icon: Icon(Icons.inventory_2_outlined),
                selectedIcon: Icon(Icons.inventory_2),
                label: 'Stock',
              ),
              page: InventoryScreen(),
            ),
          if (business.features['contacts'] ?? false)
            const _NavEntry(
              destination: NavigationDestination(
                icon: Icon(Icons.people_outline),
                selectedIcon: Icon(Icons.people),
                label: 'Contacts',
              ),
              page: ContactsScreen(),
            ),
          const _NavEntry(
            destination: NavigationDestination(
              icon: Icon(Icons.person_outline),
              selectedIcon: Icon(Icons.person),
              label: 'Compte',
            ),
            page: ProfileScreen(),
          ),
        ];
        final selectedIndex = _index.clamp(0, entries.length - 1);

        return LayoutBuilder(
          builder: (context, constraints) {
            final isLandscape =
                MediaQuery.orientationOf(context) == Orientation.landscape;
            final isWide =
                constraints.maxWidth >= 760 ||
                (isLandscape && constraints.maxWidth >= 600);
            final extended = constraints.maxWidth >= 1180;
            final title = entries[selectedIndex].destination.label;

            return Scaffold(
              appBar: AppBar(
                titleSpacing: isWide ? 28 : null,
                title: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: AppTheme.navy,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    Text(
                      business.businessName,
                      style: TextStyle(
                        color: Colors.blueGrey.shade500,
                        fontSize: 12,
                        fontWeight: FontWeight.w400,
                      ),
                    ),
                  ],
                ),
                actions: const [
                  Padding(
                    padding: EdgeInsets.only(right: 16),
                    child: Center(child: LiveBadge()),
                  ),
                ],
              ),
              body: Column(
                children: [
                  const NotificationMonitor(),
                  const ReconnectCoordinator(),
                  const NetworkBanner(),
                  Expanded(
                    child: Row(
                      children: [
                        if (isWide)
                          NavigationRail(
                            extended: extended,
                            minExtendedWidth: 230,
                            backgroundColor: Colors.white,
                            selectedIndex: selectedIndex,
                            onDestinationSelected: (value) {
                              setState(() => _index = value);
                            },
                            labelType: extended
                                ? NavigationRailLabelType.none
                                : NavigationRailLabelType.selected,
                            leading: Padding(
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              child: Container(
                                width: 44,
                                height: 44,
                                decoration: BoxDecoration(
                                  color: AppTheme.navy,
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: const Icon(
                                  Icons.insights_rounded,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                            destinations: entries
                                .map(
                                  (item) => NavigationRailDestination(
                                    icon: item.destination.icon,
                                    selectedIcon: item.destination.selectedIcon,
                                    label: Text(item.destination.label),
                                  ),
                                )
                                .toList(),
                          ),
                        Expanded(
                          child: KeyedSubtree(
                            key: ValueKey(selectedIndex),
                            child: entries[selectedIndex].page,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              bottomNavigationBar: isWide
                  ? null
                  : NavigationBar(
                      labelBehavior:
                          NavigationDestinationLabelBehavior.onlyShowSelected,
                      selectedIndex: selectedIndex,
                      onDestinationSelected: (value) {
                        setState(() => _index = value);
                      },
                      destinations: entries
                          .map((item) => item.destination)
                          .toList(),
                    ),
            );
          },
        );
      },
    );
  }
}

class _NavEntry {
  const _NavEntry({required this.destination, required this.page});

  final NavigationDestination destination;
  final Widget page;
}
