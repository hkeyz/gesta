import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/formatters.dart';
import '../../../../core/providers.dart';
import '../../../../core/theme/app_theme.dart';
import '../../domain/models.dart';

class LiveBadge extends StatelessWidget {
  const LiveBadge({super.key, this.label = 'EN DIRECT'});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0xFFE8F8F5),
        borderRadius: BorderRadius.circular(30),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 7,
            height: 7,
            decoration: const BoxDecoration(
              color: AppTheme.teal,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 7),
          Text(
            label,
            style: const TextStyle(
              color: AppTheme.teal,
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: .5,
            ),
          ),
        ],
      ),
    );
  }
}

class NetworkBanner extends ConsumerWidget {
  const NetworkBanner({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final deviceOnline = ref.watch(deviceConnectivityProvider).value;
    final serverOnline = ref.watch(serverConnectionProvider).value;
    if (deviceOnline != false && serverOnline != false) {
      return const SizedBox.shrink();
    }

    final noNetwork = deviceOnline == false;
    return Material(
      color: noNetwork ? const Color(0xFFFFE4E6) : const Color(0xFFFFF4D6),
      child: SafeArea(
        top: false,
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: Row(
            children: [
              Icon(
                noNetwork ? Icons.wifi_off_rounded : Icons.cloud_off_outlined,
                size: 18,
                color: noNetwork ? Colors.red.shade800 : Colors.orange.shade900,
              ),
              const SizedBox(width: 9),
              Expanded(
                child: Text(
                  noNetwork
                      ? 'Pas de connexion Internet — données indisponibles.'
                      : 'Serveur indisponible — données temporairement inaccessibles.',
                  style: TextStyle(
                    color: noNetwork
                        ? Colors.red.shade900
                        : Colors.orange.shade900,
                    fontWeight: FontWeight.w700,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.trailing,
  });

  final String title;
  final String? subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: AppTheme.navy,
                  fontWeight: FontWeight.w800,
                ),
              ),
              if (subtitle != null)
                Text(
                  subtitle!,
                  style: TextStyle(color: Colors.blueGrey.shade500),
                ),
            ],
          ),
        ),
        ?trailing,
      ],
    );
  }
}

class ErrorPanel extends StatelessWidget {
  const ErrorPanel({super.key, required this.error, required this.onRetry});

  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.cloud_off_outlined,
              size: 48,
              color: Colors.blueGrey.shade400,
            ),
            const SizedBox(height: 16),
            Text(
              error.toString(),
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Réessayer'),
            ),
          ],
        ),
      ),
    );
  }
}

class EmptyPanel extends StatelessWidget {
  const EmptyPanel({super.key, required this.icon, required this.message});

  final IconData icon;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(36),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 46, color: Colors.blueGrey.shade300),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.blueGrey.shade600),
            ),
          ],
        ),
      ),
    );
  }
}

class TransactionTile extends StatelessWidget {
  const TransactionTile({
    super.key,
    required this.item,
    required this.formatters,
  });

  final TransactionItem item;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    final presentation = _typePresentation(item.type);
    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        leading: Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: presentation.color.withValues(alpha: .12),
            borderRadius: BorderRadius.circular(13),
          ),
          child: Icon(presentation.icon, color: presentation.color),
        ),
        title: Row(
          children: [
            Expanded(
              child: Text(
                item.reference,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
            Text(
              formatters.money(item.amount),
              style: TextStyle(
                color: item.amount < 0 ? Colors.red.shade700 : AppTheme.navy,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Text(
            [
              _transactionTypeLabel(item.type),
              if (item.status.isNotEmpty) _transactionStatusLabel(item.status),
              if (item.contact.isNotEmpty) item.contact,
              if (item.location.isNotEmpty) item.location,
              formatters.date(item.occurredAt),
            ].join(' · '),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ),
    );
  }
}

String _transactionStatusLabel(String status) {
  return switch (status) {
    'draft' => 'Brouillon',
    'final' => 'Finalisée',
    'received' => 'Reçue',
    'pending' => 'En attente',
    'ordered' => 'Commandée',
    'cancelled' => 'Annulée',
    _ => status,
  };
}

String _transactionTypeLabel(String type) {
  return switch (type) {
    'sell' => 'Vente',
    'sell_return' => 'Retour client',
    'purchase' => 'Achat',
    'purchase_return' => 'Retour fournisseur',
    'expense' => 'Dépense',
    _ => 'Opération',
  };
}

({IconData icon, Color color}) _typePresentation(String type) {
  if (type.contains('purchase')) {
    return (icon: Icons.local_shipping_outlined, color: Colors.indigo);
  }
  if (type.contains('expense')) {
    return (icon: Icons.receipt_long_outlined, color: Colors.deepOrange);
  }
  if (type.contains('return')) {
    return (icon: Icons.undo_rounded, color: Colors.purple);
  }
  if (type.contains('stock') || type.contains('transfer')) {
    return (icon: Icons.inventory_2_outlined, color: Colors.blue);
  }
  return (icon: Icons.point_of_sale_outlined, color: AppTheme.teal);
}
