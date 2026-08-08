import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/formatters.dart';
import '../application/management_providers.dart';
import 'transaction_details_sheet.dart';
import 'widgets/common.dart';

class TransactionsScreen extends ConsumerStatefulWidget {
  const TransactionsScreen({super.key});

  @override
  ConsumerState<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends ConsumerState<TransactionsScreen> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_loadMore);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _loadMore() {
    if (_scroll.position.extentAfter < 500) {
      ref.read(transactionsProvider.notifier).loadMore();
    }
  }

  void _onSearch(String value) {
    setState(() {});
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 450), () {
      ref.read(operationFiltersProvider.notifier).search(value.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    final business = ref.watch(bootstrapProvider).value;
    final page = ref.watch(transactionsProvider);
    if (business == null) {
      return const Center(child: CircularProgressIndicator());
    }
    final formatters = AppFormatters(business);
    final availableTypes = [
      if (business.features['sales'] ?? false) 'sell',
      if (business.features['purchases'] ?? false) 'purchase',
      if (business.features['expenses'] ?? false) 'expense',
    ];
    final rawType = ref.watch(transactionTypeProvider);
    final selectedType = availableTypes.contains(rawType)
        ? rawType
        : availableTypes.first;
    final filters = ref.watch(operationFiltersProvider);

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 6, 12, 10),
          child: Column(
            children: [
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: SegmentedButton<String>(
                  segments: [
                    if (availableTypes.contains('sell'))
                      const ButtonSegment(
                        value: 'sell',
                        icon: Icon(Icons.point_of_sale_outlined),
                        label: Text('Ventes'),
                      ),
                    if (availableTypes.contains('purchase'))
                      const ButtonSegment(
                        value: 'purchase',
                        icon: Icon(Icons.local_shipping_outlined),
                        label: Text('Achats'),
                      ),
                    if (availableTypes.contains('expense'))
                      const ButtonSegment(
                        value: 'expense',
                        icon: Icon(Icons.receipt_long_outlined),
                        label: Text('Dépenses'),
                      ),
                  ],
                  selected: {selectedType},
                  showSelectedIcon: false,
                  onSelectionChanged: (selection) {
                    ref
                        .read(transactionTypeProvider.notifier)
                        .select(selection.first);
                  },
                ),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _search,
                      onChanged: _onSearch,
                      textInputAction: TextInputAction.search,
                      decoration: InputDecoration(
                        hintText: 'Référence, contact ou téléphone',
                        prefixIcon: const Icon(Icons.search),
                        suffixIcon: _search.text.isEmpty
                            ? null
                            : IconButton(
                                onPressed: () {
                                  _search.clear();
                                  _onSearch('');
                                  setState(() {});
                                },
                                icon: const Icon(Icons.close),
                              ),
                        isDense: true,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Badge(
                    isLabelVisible: filters.activeCount > 0,
                    label: Text('${filters.activeCount}'),
                    child: IconButton.filledTonal(
                      tooltip: 'Filtres avancés',
                      onPressed: () => _showFilters(context, filters),
                      icon: const Icon(Icons.tune),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        Expanded(
          child: page.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => ErrorPanel(
              error: error,
              onRetry: () => ref.invalidate(transactionsProvider),
            ),
            data: (result) => RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(transactionsProvider);
                await ref.read(transactionsProvider.future);
              },
              child: result.items.isEmpty
                  ? const CustomScrollView(
                      physics: AlwaysScrollableScrollPhysics(),
                      slivers: [
                        SliverFillRemaining(
                          child: EmptyPanel(
                            icon: Icons.search_off,
                            message:
                                'Aucune opération ne correspond aux filtres.',
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
                        return InkWell(
                          borderRadius: BorderRadius.circular(20),
                          onTap: () => showTransactionDetails(
                            context,
                            ref,
                            item,
                            formatters,
                          ),
                          child: TransactionTile(
                            item: item,
                            formatters: formatters,
                          ),
                        );
                      },
                    ),
            ),
          ),
        ),
      ],
    );
  }

  Future<void> _showFilters(
    BuildContext context,
    OperationFilters current,
  ) async {
    var status = current.status;
    var payment = current.paymentStatus;
    var range = current.from == null || current.to == null
        ? null
        : DateTimeRange(start: current.from!, end: current.to!);

    final applied = await showModalBottomSheet<OperationFilters>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) {
          return Padding(
            padding: EdgeInsets.fromLTRB(
              20,
              20,
              20,
              20 + MediaQuery.viewInsetsOf(context).bottom,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SectionHeader(
                    title: 'Filtres avancés',
                    subtitle: 'Affinez la liste des opérations',
                  ),
                  const SizedBox(height: 20),
                  DropdownButtonFormField<String?>(
                    initialValue: status,
                    decoration: const InputDecoration(labelText: 'Statut'),
                    items: const [
                      DropdownMenuItem(value: null, child: Text('Tous')),
                      DropdownMenuItem(value: 'final', child: Text('Final')),
                      DropdownMenuItem(
                        value: 'draft',
                        child: Text('Brouillon'),
                      ),
                      DropdownMenuItem(value: 'received', child: Text('Reçu')),
                      DropdownMenuItem(
                        value: 'pending',
                        child: Text('En attente'),
                      ),
                    ],
                    onChanged: (value) => setModalState(() => status = value),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String?>(
                    initialValue: payment,
                    decoration: const InputDecoration(
                      labelText: 'État du paiement',
                    ),
                    items: const [
                      DropdownMenuItem(value: null, child: Text('Tous')),
                      DropdownMenuItem(value: 'paid', child: Text('Payé')),
                      DropdownMenuItem(
                        value: 'partial',
                        child: Text('Partiel'),
                      ),
                      DropdownMenuItem(value: 'due', child: Text('À payer')),
                    ],
                    onChanged: (value) => setModalState(() => payment = value),
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: () async {
                      final selected = await showDateRangePicker(
                        context: context,
                        firstDate: DateTime(2020),
                        lastDate: DateTime.now(),
                        initialDateRange: range,
                      );
                      if (selected != null) {
                        setModalState(() => range = selected);
                      }
                    },
                    icon: const Icon(Icons.date_range),
                    label: Text(
                      range == null
                          ? 'Choisir une période'
                          : '${range!.start.day}/${range!.start.month}/${range!.start.year} — '
                                '${range!.end.day}/${range!.end.month}/${range!.end.year}',
                    ),
                  ),
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      TextButton(
                        onPressed: () => Navigator.pop(
                          context,
                          OperationFilters(search: current.search),
                        ),
                        child: const Text('Réinitialiser'),
                      ),
                      const Spacer(),
                      FilledButton(
                        onPressed: () => Navigator.pop(
                          context,
                          OperationFilters(
                            search: current.search,
                            status: status,
                            paymentStatus: payment,
                            from: range?.start,
                            to: range?.end,
                          ),
                        ),
                        child: const Text('Appliquer'),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );

    if (applied != null) {
      ref.read(operationFiltersProvider.notifier).apply(applied);
    }
  }
}
