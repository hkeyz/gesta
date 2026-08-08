import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/formatters.dart';
import '../../../core/theme/app_theme.dart';
import '../application/management_providers.dart';
import '../domain/models.dart';
import 'transaction_details_sheet.dart';
import 'widgets/common.dart';

class ActivityScreen extends ConsumerWidget {
  const ActivityScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final business = ref.watch(bootstrapProvider).value;
    final activities = ref.watch(activityProvider);
    if (business == null) {
      return const Center(child: CircularProgressIndicator());
    }
    final formatters = AppFormatters(business);

    return activities.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (error, _) => ErrorPanel(
        error: error,
        onRetry: () => ref.invalidate(activityProvider),
      ),
      data: (feed) {
        final items = feed.items;
        return NotificationListener<ScrollNotification>(
          onNotification: (notification) {
            if (notification.metrics.extentAfter < 500) {
              ref.read(activityProvider.notifier).loadMore();
            }
            return false;
          },
          child: RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(activityProvider);
              await ref.read(activityProvider.future);
            },
            child: items.isEmpty
                ? const CustomScrollView(
                    physics: AlwaysScrollableScrollPhysics(),
                    slivers: [
                      SliverFillRemaining(
                        child: EmptyPanel(
                          icon: Icons.bolt_outlined,
                          message: 'Aucune activité récente.',
                        ),
                      ),
                    ],
                  )
                : ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                    itemCount: items.length + 1 + (feed.hasMore ? 1 : 0),
                    separatorBuilder: (_, _) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      if (index == 0) {
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 6),
                          child: SectionHeader(
                            title: 'Fil d’activité',
                            subtitle:
                                'Actualisation automatique toutes les ${business.activityRefreshSeconds} secondes',
                            trailing: IconButton(
                              onPressed: () => ref.invalidate(activityProvider),
                              icon: const Icon(Icons.refresh),
                            ),
                          ),
                        );
                      }
                      if (index > items.length) {
                        return const Padding(
                          padding: EdgeInsets.all(20),
                          child: Center(child: CircularProgressIndicator()),
                        );
                      }
                      final item = items[index - 1];
                      return InkWell(
                        borderRadius: BorderRadius.circular(20),
                        onTap: () => showTransactionDetails(
                          context,
                          ref,
                          item.transaction,
                          formatters,
                        ),
                        child: _ActivityCard(
                          item: item,
                          formatters: formatters,
                        ),
                      );
                    },
                  ),
          ),
        );
      },
    );
  }
}

class _ActivityCard extends StatelessWidget {
  const _ActivityCard({required this.item, required this.formatters});

  final ActivityItem item;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    final style = _categoryStyle(item.category);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: style.color.withValues(alpha: .11),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(style.icon, color: style.color),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          item.title,
                          style: const TextStyle(
                            color: AppTheme.navy,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                      Text(
                        formatters.money(item.transaction.amount),
                        style: TextStyle(
                          color: style.color,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                  if (item.description.isNotEmpty) ...[
                    const SizedBox(height: 5),
                    Text(
                      item.description,
                      style: TextStyle(color: Colors.blueGrey.shade600),
                    ),
                  ],
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Icon(
                        Icons.schedule,
                        size: 14,
                        color: Colors.blueGrey.shade400,
                      ),
                      const SizedBox(width: 5),
                      Text(
                        formatters.date(item.transaction.occurredAt),
                        style: TextStyle(
                          color: Colors.blueGrey.shade500,
                          fontSize: 12,
                        ),
                      ),
                    ],
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

({IconData icon, Color color}) _categoryStyle(String category) {
  return switch (category) {
    'sale' => (icon: Icons.point_of_sale, color: AppTheme.teal),
    'purchase' => (icon: Icons.local_shipping_outlined, color: Colors.indigo),
    'expense' => (icon: Icons.receipt_long_outlined, color: Colors.deepOrange),
    'return' => (icon: Icons.undo_rounded, color: Colors.purple),
    'stock' => (icon: Icons.inventory_2_outlined, color: Colors.blue),
    _ => (icon: Icons.swap_horiz, color: Colors.blueGrey),
  };
}
