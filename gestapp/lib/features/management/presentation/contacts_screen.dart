import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/formatters.dart';
import '../../../core/theme/app_theme.dart';
import '../application/management_providers.dart';
import '../domain/models.dart';
import 'transaction_details_sheet.dart';
import 'widgets/common.dart';

class ContactsScreen extends ConsumerStatefulWidget {
  const ContactsScreen({super.key});

  @override
  ConsumerState<ContactsScreen> createState() => _ContactsScreenState();
}

class _ContactsScreenState extends ConsumerState<ContactsScreen> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(() {
      if (_scroll.position.extentAfter < 500) {
        ref.read(contactsProvider.notifier).loadMore();
      }
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _onSearch(String value) {
    setState(() {});
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 450), () {
      ref.read(contactFiltersProvider.notifier).search(value.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    final business = ref.watch(bootstrapProvider).value;
    final contacts = ref.watch(contactsProvider);
    final filter = ref.watch(contactFiltersProvider);
    if (business == null) {
      return const Center(child: CircularProgressIndicator());
    }
    final formatters = AppFormatters(business);

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 6, 12, 10),
          child: Column(
            children: [
              TextField(
                controller: _search,
                onChanged: _onSearch,
                textInputAction: TextInputAction.search,
                decoration: InputDecoration(
                  hintText: 'Nom, téléphone, email ou identifiant',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _search.text.isEmpty
                      ? null
                      : IconButton(
                          onPressed: () {
                            _search.clear();
                            _onSearch('');
                          },
                          icon: const Icon(Icons.close),
                        ),
                  isDense: true,
                ),
              ),
              const SizedBox(height: 10),
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: SegmentedButton<String>(
                  segments: const [
                    ButtonSegment(value: 'all', label: Text('Tous')),
                    ButtonSegment(
                      value: 'customer',
                      icon: Icon(Icons.people_outline),
                      label: Text('Clients'),
                    ),
                    ButtonSegment(
                      value: 'supplier',
                      icon: Icon(Icons.local_shipping_outlined),
                      label: Text('Fournisseurs'),
                    ),
                    ButtonSegment(
                      value: 'both',
                      icon: Icon(Icons.swap_horiz),
                      label: Text('Les deux'),
                    ),
                  ],
                  selected: {filter.type ?? 'all'},
                  showSelectedIcon: false,
                  onSelectionChanged: (selection) {
                    ref
                        .read(contactFiltersProvider.notifier)
                        .type(
                          selection.first == 'all' ? null : selection.first,
                        );
                  },
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: contacts.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => ErrorPanel(
              error: error,
              onRetry: () => ref.invalidate(contactsProvider),
            ),
            data: (result) => RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(contactsProvider);
                await ref.read(contactsProvider.future);
              },
              child: result.items.isEmpty
                  ? const CustomScrollView(
                      physics: AlwaysScrollableScrollPhysics(),
                      slivers: [
                        SliverFillRemaining(
                          child: EmptyPanel(
                            icon: Icons.person_search_outlined,
                            message: 'Aucun contact ne correspond.',
                          ),
                        ),
                      ],
                    )
                  : ListView.separated(
                      controller: _scroll,
                      keyboardDismissBehavior:
                          ScrollViewKeyboardDismissBehavior.onDrag,
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(12, 0, 12, 32),
                      itemCount: result.items.length + (result.hasMore ? 1 : 0),
                      separatorBuilder: (_, _) => const SizedBox(height: 9),
                      itemBuilder: (context, index) {
                        if (index == result.items.length) {
                          return const Padding(
                            padding: EdgeInsets.all(20),
                            child: Center(child: CircularProgressIndicator()),
                          );
                        }
                        final item = result.items[index];
                        return _ContactTile(
                          item: item,
                          formatters: formatters,
                          onTap: () => _showContact(context, item, formatters),
                        );
                      },
                    ),
            ),
          ),
        ),
      ],
    );
  }

  void _showContact(
    BuildContext context,
    ContactItem item,
    AppFormatters formatters,
  ) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ContactDetails(item: item, formatters: formatters),
    );
  }
}

class _ContactTile extends StatelessWidget {
  const _ContactTile({
    required this.item,
    required this.formatters,
    required this.onTap,
  });

  final ContactItem item;
  final AppFormatters formatters;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        leading: CircleAvatar(
          backgroundColor: const Color(0xFFD7F3EF),
          child: Text(
            item.name.isEmpty ? '?' : item.name[0].toUpperCase(),
            style: const TextStyle(
              color: AppTheme.teal,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        title: Text(
          item.name,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: Text(
          [
            item.mobile,
            item.email,
          ].where((value) => value.isNotEmpty).join(' · '),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              formatters.money(item.balance),
              style: TextStyle(
                color: item.balance > 0 ? Colors.orange : AppTheme.navy,
                fontWeight: FontWeight.w800,
              ),
            ),
            Text(
              _typeLabel(item.type),
              style: const TextStyle(fontSize: 11, color: Colors.blueGrey),
            ),
          ],
        ),
      ),
    );
  }
}

class _ContactDetails extends ConsumerWidget {
  const _ContactDetails({required this.item, required this.formatters});

  final ContactItem item;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(contactDetailProvider(item.id));
    return DraggableScrollableSheet(
      initialChildSize: .86,
      minChildSize: .55,
      maxChildSize: .97,
      expand: false,
      builder: (context, controller) => Material(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(26)),
        child: detail.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(contactDetailProvider(item.id)),
          ),
          data: (data) {
            final stats = mapValue(data['statistics']);
            final transactions = mapList(
              data['recent_transactions'],
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
                Row(
                  children: [
                    CircleAvatar(
                      radius: 30,
                      backgroundColor: const Color(0xFFD7F3EF),
                      child: Text(
                        item.name[0].toUpperCase(),
                        style: const TextStyle(
                          color: AppTheme.teal,
                          fontSize: 22,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item.name,
                            style: const TextStyle(
                              color: AppTheme.navy,
                              fontSize: 23,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                          Text(
                            _typeLabel(item.type),
                            style: const TextStyle(color: Colors.blueGrey),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                if (item.mobile.isNotEmpty)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.phone_outlined),
                    title: Text(item.mobile),
                  ),
                if (item.email.isNotEmpty)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.email_outlined),
                    title: Text(item.email),
                  ),
                if (item.address.isNotEmpty)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.location_on_outlined),
                    title: Text(item.address.join(', ')),
                  ),
                const SizedBox(height: 12),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Wrap(
                      spacing: 24,
                      runSpacing: 14,
                      children: [
                        _value('Solde', formatters.money(data['balance'])),
                        _value(
                          'Ventes',
                          formatters.money(stats['sales_total']),
                        ),
                        _value(
                          'Achats',
                          formatters.money(stats['purchase_total']),
                        ),
                        _value(
                          'À encaisser',
                          formatters.money(stats['sales_due']),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 22),
                const SectionHeader(title: 'Opérations récentes'),
                const SizedBox(height: 10),
                if (transactions.isEmpty)
                  const EmptyPanel(
                    icon: Icons.receipt_long_outlined,
                    message: 'Aucune opération récente.',
                  )
                else
                  ...transactions.map(
                    (transaction) => Padding(
                      padding: const EdgeInsets.only(bottom: 9),
                      child: InkWell(
                        onTap: () => showTransactionDetails(
                          context,
                          ref,
                          transaction,
                          formatters,
                        ),
                        child: TransactionTile(
                          item: transaction,
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

  Widget _value(String label, String value) {
    return SizedBox(
      width: 130,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.blueGrey)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w800)),
        ],
      ),
    );
  }
}

String _typeLabel(String type) {
  return switch (type) {
    'supplier' => 'Fournisseur',
    'both' => 'Client et fournisseur',
    _ => 'Client',
  };
}
