import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/notifications/notification_service.dart';
import '../../auth/application/auth_controller.dart';
import '../../notifications/application/notification_providers.dart';
import '../application/management_providers.dart';
import 'widgets/common.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final business = ref.watch(bootstrapProvider).value;
    final auth = ref.watch(authControllerProvider).value;
    if (business == null) {
      return const Center(child: CircularProgressIndicator());
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
      children: [
        Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 620),
            child: Column(
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 40,
                          backgroundColor: const Color(0xFFD7F3EF),
                          child: Text(
                            business.userName.isEmpty
                                ? 'G'
                                : business.userName[0].toUpperCase(),
                            style: const TextStyle(
                              color: AppTheme.teal,
                              fontSize: 30,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ),
                        const SizedBox(height: 14),
                        Text(
                          business.userName,
                          style: const TextStyle(
                            color: AppTheme.navy,
                            fontSize: 22,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          business.businessName,
                          style: const TextStyle(color: Colors.blueGrey),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Card(
                  child: Column(
                    children: [
                      ListTile(
                        leading: const Icon(Icons.dns_outlined),
                        title: const Text('Serveur'),
                        subtitle: Text(auth?.serverUrl ?? ''),
                      ),
                      const Divider(height: 1),
                      ListTile(
                        leading: const Icon(Icons.store_outlined),
                        title: const Text('Établissements accessibles'),
                        subtitle: Text('${business.locations.length}'),
                      ),
                      const Divider(height: 1),
                      ListTile(
                        leading: const Icon(Icons.sync),
                        title: const Text('Synchronisation'),
                        subtitle: Text(
                          'Tableau ${business.dashboardRefreshSeconds}s · activité ${business.activityRefreshSeconds}s',
                        ),
                        trailing: const LiveBadge(label: 'ACTIVE'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 22),
                const _NotificationSettingsCard(),
                const SizedBox(height: 22),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () =>
                        ref.read(authControllerProvider.notifier).logout(),
                    icon: const Icon(Icons.logout),
                    label: const Padding(
                      padding: EdgeInsets.symmetric(vertical: 13),
                      child: Text('Se déconnecter'),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Gesta Pilot · API mobile v1',
                  style: TextStyle(
                    color: Colors.blueGrey.shade400,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _NotificationSettingsCard extends ConsumerWidget {
  const _NotificationSettingsCard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.watch(notificationPreferencesProvider);
    return Card(
      child: settings.when(
        loading: () => const Padding(
          padding: EdgeInsets.all(20),
          child: Center(child: CircularProgressIndicator()),
        ),
        error: (error, _) => Padding(
          padding: const EdgeInsets.all(16),
          child: Text(error.toString()),
        ),
        data: (value) => Column(
          children: [
            SwitchListTile(
              secondary: const Icon(Icons.notifications_active_outlined),
              title: const Text('Alertes de pilotage'),
              subtitle: const Text(
                'Ventes importantes, stock faible et caisses à vérifier',
              ),
              value: value.enabled,
              onChanged: (enabled) async {
                await ref
                    .read(notificationPreferencesProvider.notifier)
                    .setEnabled(enabled);
                if (enabled) {
                  await NotificationService.instance.requestPermission();
                }
              },
            ),
            if (value.enabled) ...[
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.tune),
                title: const Text('Seuils des alertes'),
                subtitle: Text(
                  'Vente ≥ ${value.saleThreshold.toStringAsFixed(0)} · '
                  'caisse ≥ ${value.maxRegisterMinutes ~/ 60} h',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => _configure(context, ref, value),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Future<void> _configure(
    BuildContext context,
    WidgetRef ref,
    NotificationPreferences current,
  ) async {
    final sale = TextEditingController(
      text: current.saleThreshold.toStringAsFixed(0),
    );
    final hours = TextEditingController(
      text: (current.maxRegisterMinutes / 60).toStringAsFixed(0),
    );
    final result = await showDialog<(double, int)>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Seuils des alertes'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: sale,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Montant d’une vente importante',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: hours,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Durée anormale d’une caisse (heures)',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Annuler'),
          ),
          FilledButton(
            onPressed: () {
              final amount = double.tryParse(sale.text);
              final duration = int.tryParse(hours.text);
              if (amount != null && amount >= 0 && duration != null) {
                Navigator.pop(context, (amount, duration * 60));
              }
            },
            child: const Text('Enregistrer'),
          ),
        ],
      ),
    );
    sale.dispose();
    hours.dispose();
    if (result != null) {
      await ref
          .read(notificationPreferencesProvider.notifier)
          .configure(saleThreshold: result.$1, maxRegisterMinutes: result.$2);
    }
  }
}
