import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/formatters.dart';
import '../../../core/theme/app_theme.dart';
import '../application/management_providers.dart';
import '../domain/models.dart';
import 'transaction_details_sheet.dart';
import 'widgets/common.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final business = ref.watch(bootstrapProvider).value;
    final dashboard = ref.watch(dashboardProvider);
    if (business == null) {
      return const Center(child: CircularProgressIndicator());
    }
    final formatters = AppFormatters(business);

    return dashboard.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (error, _) => ErrorPanel(
        error: error,
        onRetry: () => ref.invalidate(dashboardProvider),
      ),
      data: (data) => RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(dashboardProvider);
          await ref.read(dashboardProvider.future);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
              sliver: SliverList.list(
                children: [
                  _Filters(business: business),
                  const SizedBox(height: 20),
                  _MetricGrid(data: data, formatters: formatters),
                  const SizedBox(height: 12),
                  _SecondaryMetrics(data: data, formatters: formatters),
                  const SizedBox(height: 24),
                  _Alerts(data: data),
                  const SizedBox(height: 24),
                  _ResponsiveInsights(data: data, formatters: formatters),
                  const SizedBox(height: 24),
                  _CashRegisters(formatters: formatters),
                  const SizedBox(height: 24),
                  SectionHeader(
                    title: 'Dernières opérations',
                    subtitle: 'Mise à jour toutes les 15 secondes',
                    trailing: IconButton(
                      tooltip: 'Actualiser',
                      onPressed: () => ref.invalidate(dashboardProvider),
                      icon: const Icon(Icons.refresh),
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (data.recentTransactions.isEmpty)
                    const EmptyPanel(
                      icon: Icons.receipt_long_outlined,
                      message: 'Aucune opération récente.',
                    )
                  else
                    ...data.recentTransactions.map(
                      (item) => Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: TransactionTile(
                          item: item,
                          formatters: formatters,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SecondaryMetrics extends StatelessWidget {
  const _SecondaryMetrics({required this.data, required this.formatters});

  final DashboardData data;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    final metrics = data.metrics;
    final items = [
      ('Tickets', formatters.number(metrics['sales_count'])),
      ('Ticket moyen', formatters.money(metrics['average_ticket'])),
      ('Reste à encaisser', formatters.money(metrics['sales_due'])),
      ('Achats', formatters.money(metrics['purchases'])),
      ('Bénéfice brut', formatters.money(metrics['gross_profit'])),
    ];

    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
        child: Wrap(
          spacing: 30,
          runSpacing: 14,
          children: items
              .map(
                (item) => SizedBox(
                  width: 150,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.$1,
                        style: TextStyle(color: Colors.blueGrey.shade600),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        item.$2,
                        style: const TextStyle(
                          color: AppTheme.navy,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ),
              )
              .toList(),
        ),
      ),
    );
  }
}

class _Filters extends ConsumerWidget {
  const _Filters({required this.business});

  final BusinessContext business;

  static const ranges = {
    'today': 'Aujourd’hui',
    'yesterday': 'Hier',
    'week': 'Cette semaine',
    'month': 'Ce mois',
    'last_30_days': '30 jours',
  };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filter = ref.watch(dashboardFilterProvider);
    return Wrap(
      spacing: 12,
      runSpacing: 12,
      children: [
        SizedBox(
          width: 190,
          child: DropdownButtonFormField<String>(
            initialValue: filter.range,
            decoration: const InputDecoration(
              labelText: 'Période',
              prefixIcon: Icon(Icons.calendar_today_outlined),
              isDense: true,
            ),
            items: ranges.entries
                .map(
                  (item) => DropdownMenuItem(
                    value: item.key,
                    child: Text(item.value),
                  ),
                )
                .toList(),
            onChanged: (value) {
              if (value != null) {
                ref.read(dashboardFilterProvider.notifier).selectRange(value);
              }
            },
          ),
        ),
        SizedBox(
          width: 220,
          child: DropdownButtonFormField<int?>(
            initialValue: filter.locationId,
            decoration: const InputDecoration(
              labelText: 'Établissement',
              prefixIcon: Icon(Icons.store_outlined),
              isDense: true,
            ),
            items: [
              const DropdownMenuItem<int?>(
                value: null,
                child: Text('Tous les sites'),
              ),
              ...business.locations.map(
                (location) => DropdownMenuItem<int?>(
                  value: location.id,
                  child: Text(location.name, overflow: TextOverflow.ellipsis),
                ),
              ),
            ],
            onChanged: (value) {
              ref.read(dashboardFilterProvider.notifier).selectLocation(value);
            },
          ),
        ),
      ],
    );
  }
}

class _MetricGrid extends StatelessWidget {
  const _MetricGrid({required this.data, required this.formatters});

  final DashboardData data;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    final metrics = data.metrics;
    final items = [
      (
        'Ventes nettes',
        formatters.money(metrics['net_sales']),
        Icons.trending_up_rounded,
        AppTheme.teal,
        metrics['net_sales_change_percent'],
      ),
      (
        'Bénéfice net',
        formatters.money(metrics['net_profit']),
        Icons.account_balance_wallet_outlined,
        Colors.indigo,
        metrics['gross_profit_change_percent'],
      ),
      (
        'Dépenses',
        formatters.money(metrics['expenses']),
        Icons.receipt_long_outlined,
        Colors.deepOrange,
        metrics['expenses_change_percent'],
      ),
      (
        'Encaissé',
        formatters.money(metrics['collected']),
        Icons.payments_outlined,
        Colors.green,
        metrics['collected_change_percent'],
      ),
    ];

    return LayoutBuilder(
      builder: (context, constraints) {
        final columns = constraints.maxWidth >= 1050
            ? 4
            : constraints.maxWidth >= 560
            ? 2
            : 1;
        const spacing = 12.0;
        final width =
            (constraints.maxWidth - spacing * (columns - 1)) / columns;
        return Wrap(
          spacing: spacing,
          runSpacing: spacing,
          children: items
              .map(
                (item) => SizedBox(
                  width: width,
                  child: _MetricCard(
                    label: item.$1,
                    value: item.$2,
                    icon: item.$3,
                    color: item.$4,
                    change: item.$5,
                  ),
                ),
              )
              .toList(),
        );
      },
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
    required this.change,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final dynamic change;

  @override
  Widget build(BuildContext context) {
    final percent = change == null ? null : numberValue(change);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: color.withValues(alpha: .12),
                borderRadius: BorderRadius.circular(15),
              ),
              child: Icon(icon, color: color),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(color: Colors.blueGrey.shade600),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    value,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppTheme.navy,
                      fontSize: 20,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ],
              ),
            ),
            if (percent != null)
              Text(
                '${percent >= 0 ? '+' : ''}${percent.toStringAsFixed(1)}%',
                style: TextStyle(
                  color: percent >= 0 ? Colors.green.shade700 : Colors.red,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _Alerts extends StatelessWidget {
  const _Alerts({required this.data});

  final DashboardData data;

  @override
  Widget build(BuildContext context) {
    final low = intValue(data.alerts['low_stock_count']);
    final overdue = intValue(data.alerts['overdue_sales_count']);
    final registers = data.openRegisters.length;
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: [
        _AlertChip(
          icon: Icons.warning_amber_rounded,
          label: '$low stock${low > 1 ? 's' : ''} faible${low > 1 ? 's' : ''}',
          color: low > 0 ? Colors.orange : Colors.blueGrey,
        ),
        _AlertChip(
          icon: Icons.schedule_outlined,
          label: '$overdue vente${overdue > 1 ? 's' : ''} en retard',
          color: overdue > 0 ? Colors.red : Colors.blueGrey,
        ),
        _AlertChip(
          icon: Icons.point_of_sale_outlined,
          label:
              '$registers caisse${registers > 1 ? 's' : ''} ouverte${registers > 1 ? 's' : ''}',
          color: registers > 0 ? AppTheme.teal : Colors.blueGrey,
        ),
      ],
    );
  }
}

class _AlertChip extends StatelessWidget {
  const _AlertChip({
    required this.icon,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .09),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 19, color: color),
          const SizedBox(width: 8),
          Text(
            label,
            style: TextStyle(color: color, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}

class _ResponsiveInsights extends StatelessWidget {
  const _ResponsiveInsights({required this.data, required this.formatters});

  final DashboardData data;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final chart = _TrendCard(data: data, formatters: formatters);
        final products = _TopProducts(data: data, formatters: formatters);
        if (constraints.maxWidth < 800) {
          return Column(
            children: [chart, const SizedBox(height: 14), products],
          );
        }
        return Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(flex: 3, child: chart),
            const SizedBox(width: 14),
            Expanded(flex: 2, child: products),
          ],
        );
      },
    );
  }
}

class _TrendCard extends StatelessWidget {
  const _TrendCard({required this.data, required this.formatters});

  final DashboardData data;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    final maxValue = data.trend.fold<double>(
      0,
      (value, item) => math.max(value, numberValue(item['total'])),
    );
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SectionHeader(
              title: 'Évolution des ventes',
              subtitle: 'Total quotidien',
            ),
            const SizedBox(height: 22),
            SizedBox(
              height: 190,
              child: data.trend.isEmpty
                  ? const EmptyPanel(
                      icon: Icons.bar_chart_outlined,
                      message: 'Aucune vente sur cette période.',
                    )
                  : Row(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: data.trend.map((item) {
                        final value = numberValue(item['total']);
                        final ratio = maxValue == 0 ? 0.0 : value / maxValue;
                        return Expanded(
                          child: Tooltip(
                            message: formatters.money(value),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 3,
                              ),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  Expanded(
                                    child: Align(
                                      alignment: Alignment.bottomCenter,
                                      child: FractionallySizedBox(
                                        heightFactor: math
                                            .max(.04, ratio)
                                            .toDouble(),
                                        child: Container(
                                          decoration: BoxDecoration(
                                            color: AppTheme.teal,
                                            borderRadius: BorderRadius.circular(
                                              7,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 7),
                                  Text(
                                    item['period'].toString().split('-').last,
                                    style: const TextStyle(fontSize: 10),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TopProducts extends StatelessWidget {
  const _TopProducts({required this.data, required this.formatters});

  final DashboardData data;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          children: [
            const SectionHeader(
              title: 'Produits vedettes',
              subtitle: 'Par quantité vendue',
            ),
            const SizedBox(height: 14),
            if (data.topProducts.isEmpty)
              const EmptyPanel(
                icon: Icons.inventory_2_outlined,
                message: 'Pas encore de produit vendu.',
              )
            else
              ...data.topProducts.asMap().entries.map((entry) {
                final item = entry.value;
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: CircleAvatar(
                    backgroundColor: const Color(0xFFE8F8F5),
                    child: Text(
                      '${entry.key + 1}',
                      style: const TextStyle(
                        color: AppTheme.teal,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  title: Text(
                    item['name']?.toString() ?? 'Produit',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  subtitle: Text(
                    '${numberValue(item['quantity']).toStringAsFixed(1)} vendu',
                  ),
                  trailing: Text(
                    formatters.money(item['total']),
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }
}

class _CashRegisters extends ConsumerWidget {
  const _CashRegisters({required this.formatters});

  final AppFormatters formatters;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final registers = ref.watch(openCashRegistersProvider);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          children: [
            SectionHeader(
              title: 'Caisses ouvertes',
              subtitle: 'Encaissement depuis l’ouverture',
              trailing: IconButton(
                onPressed: () => ref.invalidate(openCashRegistersProvider),
                icon: const Icon(Icons.refresh),
              ),
            ),
            const SizedBox(height: 12),
            registers.when(
              loading: () => const Padding(
                padding: EdgeInsets.all(20),
                child: CircularProgressIndicator(),
              ),
              error: (error, _) => Text(
                error.toString(),
                style: const TextStyle(color: Colors.red),
              ),
              data: (items) {
                if (items.isEmpty) {
                  return const EmptyPanel(
                    icon: Icons.point_of_sale_outlined,
                    message: 'Aucune caisse ouverte.',
                  );
                }
                return Column(
                  children: items.map((register) {
                    final user = mapValue(register['user']);
                    final location = mapValue(register['location']);
                    final sales = mapValue(register['sales']);
                    final movements = mapValue(register['cash_movements']);
                    return ListTile(
                      onTap: () => showModalBottomSheet<void>(
                        context: context,
                        isScrollControlled: true,
                        useSafeArea: true,
                        backgroundColor: Colors.transparent,
                        builder: (_) => _CashRegisterDetails(
                          registerId: intValue(register['id']),
                          formatters: formatters,
                        ),
                      ),
                      contentPadding: EdgeInsets.zero,
                      leading: const CircleAvatar(
                        backgroundColor: Color(0xFFE8F8F5),
                        child: Icon(Icons.point_of_sale, color: AppTheme.teal),
                      ),
                      title: Text(
                        user['name']?.toString() ?? 'Caisse',
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                      subtitle: Text(
                        '${location['name'] ?? ''} · ${sales['count'] ?? 0} vente(s)',
                      ),
                      trailing: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            formatters.money(sales['total']),
                            style: const TextStyle(
                              color: AppTheme.navy,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          Text(
                            'Cash ${formatters.money(movements['net'])}',
                            style: const TextStyle(fontSize: 11),
                          ),
                        ],
                      ),
                    );
                  }).toList(),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _CashRegisterDetails extends ConsumerWidget {
  const _CashRegisterDetails({
    required this.registerId,
    required this.formatters,
  });

  final int registerId;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(cashRegisterDetailProvider(registerId));
    return DraggableScrollableSheet(
      initialChildSize: .88,
      minChildSize: .58,
      maxChildSize: .97,
      expand: false,
      builder: (context, controller) => Material(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(26)),
        child: detail.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ErrorPanel(
            error: error,
            onRetry: () =>
                ref.invalidate(cashRegisterDetailProvider(registerId)),
          ),
          data: (data) {
            final user = mapValue(data['user']);
            final location = mapValue(data['location']);
            final totals = mapValue(data['totals']);
            final breakdown = mapList(data['payment_breakdown']);
            final movements = mapList(data['movements']);
            final sales = mapList(
              data['recent_sales'],
            ).map(TransactionItem.fromJson).toList();
            return ListView(
              controller: controller,
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 36),
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.blueGrey.shade200,
                      borderRadius: BorderRadius.circular(20),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  user['name']?.toString() ?? 'Caisse',
                  style: const TextStyle(
                    color: AppTheme.navy,
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                Text(
                  '${location['name'] ?? ''} · ouverte depuis ${intValue(data['duration_minutes'])} min',
                  style: const TextStyle(color: Colors.blueGrey),
                ),
                const SizedBox(height: 20),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Wrap(
                      spacing: 28,
                      runSpacing: 14,
                      children: [
                        _registerValue(
                          'Entrées',
                          formatters.money(totals['credits']),
                          Colors.green,
                        ),
                        _registerValue(
                          'Sorties',
                          formatters.money(totals['debits']),
                          Colors.red,
                        ),
                        _registerValue(
                          'Net',
                          formatters.money(totals['net']),
                          AppTheme.teal,
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 22),
                const SectionHeader(title: 'Par moyen de paiement'),
                ...breakdown.map(
                  (method) => ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.payments_outlined),
                    title: Text(method['method']?.toString() ?? 'Paiement'),
                    subtitle: Text(
                      'Entrées ${formatters.money(method['credits'])} · sorties ${formatters.money(method['debits'])}',
                    ),
                    trailing: Text(
                      formatters.money(method['net']),
                      style: const TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                const SectionHeader(title: 'Mouvements récents'),
                ...movements
                    .take(12)
                    .map(
                      (movement) => ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: Icon(
                          movement['type'] == 'credit'
                              ? Icons.south_west
                              : Icons.north_east,
                          color: movement['type'] == 'credit'
                              ? Colors.green
                              : Colors.red,
                        ),
                        title: Text(
                          movement['transaction_type']?.toString() ??
                              'Mouvement',
                        ),
                        subtitle: Text(
                          movement['payment_method']?.toString() ?? '',
                        ),
                        trailing: Text(
                          formatters.money(movement['amount']),
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                const SizedBox(height: 18),
                const SectionHeader(title: 'Ventes de la caisse'),
                ...sales.map(
                  (sale) => Padding(
                    padding: const EdgeInsets.only(top: 9),
                    child: InkWell(
                      onTap: () => showTransactionDetails(
                        context,
                        ref,
                        sale,
                        formatters,
                      ),
                      child: TransactionTile(
                        item: sale,
                        formatters: formatters,
                      ),
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _registerValue(String label, String value, Color color) {
    return SizedBox(
      width: 130,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.blueGrey)),
          Text(
            value,
            style: TextStyle(color: color, fontWeight: FontWeight.w900),
          ),
        ],
      ),
    );
  }
}
