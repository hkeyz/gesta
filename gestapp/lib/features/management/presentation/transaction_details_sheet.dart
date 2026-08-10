import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/formatters.dart';
import '../../../core/theme/app_theme.dart';
import '../application/management_providers.dart';
import '../domain/models.dart';
import 'widgets/common.dart';

void showTransactionDetails(
  BuildContext context,
  WidgetRef ref,
  TransactionItem item,
  AppFormatters formatters,
) {
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    backgroundColor: Colors.transparent,
    builder: (_) => TransactionDetailsSheet(item: item, formatters: formatters),
  );
}

class TransactionDetailsSheet extends ConsumerWidget {
  const TransactionDetailsSheet({
    super.key,
    required this.item,
    required this.formatters,
  });

  final TransactionItem item;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(transactionDetailProvider(item.id));
    return DraggableScrollableSheet(
      initialChildSize: .84,
      minChildSize: .55,
      maxChildSize: .97,
      expand: false,
      builder: (context, controller) {
        return Material(
          color: Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(26)),
          child: detail.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => ErrorPanel(
              error: error,
              onRetry: () => ref.invalidate(transactionDetailProvider(item.id)),
            ),
            data: (data) {
              final sellLines = mapList(data['sell_lines']);
              final purchaseLines = mapList(data['purchase_lines']);
              final lines = sellLines.isNotEmpty ? sellLines : purchaseLines;
              final payments = mapList(data['payments']);

              return ListView(
                controller: controller,
                keyboardDismissBehavior:
                    ScrollViewKeyboardDismissBehavior.onDrag,
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 36),
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
                  const SizedBox(height: 22),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          item.reference,
                          style: const TextStyle(
                            color: AppTheme.navy,
                            fontSize: 23,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ),
                      Text(
                        formatters.money(item.amount),
                        style: const TextStyle(
                          color: AppTheme.teal,
                          fontSize: 20,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    [
                      item.contact,
                      item.location,
                      formatters.date(item.occurredAt),
                    ].where((value) => value.isNotEmpty).join(' · '),
                    style: TextStyle(color: Colors.blueGrey.shade600),
                  ),
                  const SizedBox(height: 22),
                  _DetailSummary(data: data, formatters: formatters),
                  const SizedBox(height: 24),
                  const SectionHeader(title: 'Articles'),
                  const SizedBox(height: 10),
                  if (lines.isEmpty)
                    const Text('Aucune ligne disponible.')
                  else
                    ...lines.map(
                      (line) => ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          line['product_name']?.toString() ?? 'Article',
                        ),
                        subtitle: Text(
                          [
                            line['variation_name']?.toString() ?? '',
                            'Qté ${numberValue(line['quantity']).toStringAsFixed(2)}',
                          ].where((value) => value.isNotEmpty).join(' · '),
                        ),
                        trailing: Text(
                          formatters.money(line['line_total']),
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                  const SizedBox(height: 20),
                  const SectionHeader(title: 'Paiements'),
                  const SizedBox(height: 10),
                  if (payments.isEmpty)
                    const Text('Aucun paiement enregistré.')
                  else
                    ...payments.map(
                      (payment) => ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.payments_outlined),
                        title: Text(
                          _paymentLabel(
                            payment['method']?.toString() ?? 'Paiement',
                          ),
                        ),
                        subtitle: Text(payment['reference']?.toString() ?? ''),
                        trailing: Text(
                          formatters.money(payment['amount']),
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
        );
      },
    );
  }
}

class _DetailSummary extends StatelessWidget {
  const _DetailSummary({required this.data, required this.formatters});

  final Map<String, dynamic> data;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    final parent = mapValue(data['parent_transaction']);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Wrap(
          spacing: 28,
          runSpacing: 14,
          children: [
            _value('Statut', data['status']?.toString() ?? '—'),
            _value('Paiement', data['payment_status']?.toString() ?? '—'),
            if (parent.isNotEmpty)
              _value('Vente d’origine', parent['reference']?.toString() ?? '—'),
            _value('Taxes', formatters.money(data['tax_amount'])),
            _value('Remise', formatters.money(data['discount_amount'])),
          ],
        ),
      ),
    );
  }

  Widget _value(String label, String value) {
    return SizedBox(
      width: 120,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.blueGrey)),
          const SizedBox(height: 3),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}

String _paymentLabel(String method) {
  return switch (method) {
    'cash' => 'Espèces',
    'card' => 'Carte',
    'bank_transfer' => 'Virement',
    'cheque' => 'Chèque',
    _ => method,
  };
}
