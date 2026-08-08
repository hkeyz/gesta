import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/formatters.dart';
import '../../../core/theme/app_theme.dart';
import '../application/management_providers.dart';
import '../domain/models.dart';
import 'widgets/common.dart';

class InventoryScreen extends ConsumerStatefulWidget {
  const InventoryScreen({super.key});

  @override
  ConsumerState<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends ConsumerState<InventoryScreen> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(() {
      if (_scroll.position.extentAfter < 600) {
        ref.read(productsProvider.notifier).loadMore();
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
      ref.read(productFiltersProvider.notifier).search(value.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    final business = ref.watch(bootstrapProvider).value;
    final summary = ref.watch(inventorySummaryProvider);
    final products = ref.watch(productsProvider);
    final categories = ref.watch(productCategoriesProvider);
    final filters = ref.watch(productFiltersProvider);
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
                  hintText: 'Rechercher un produit ou un SKU',
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
              LayoutBuilder(
                builder: (context, constraints) {
                  final width = constraints.maxWidth >= 600
                      ? (constraints.maxWidth - 10) / 2
                      : constraints.maxWidth;
                  return Wrap(
                    spacing: 10,
                    runSpacing: 10,
                    children: [
                      SizedBox(
                        width: width,
                        child: categories.when(
                          loading: () => const LinearProgressIndicator(),
                          error: (_, _) => const SizedBox.shrink(),
                          data: (items) {
                            final flat = [
                              for (final category in items) category,
                              for (final category in items)
                                ...category.children,
                            ];
                            return DropdownButtonFormField<int?>(
                              initialValue: filters.categoryId,
                              decoration: const InputDecoration(
                                labelText: 'Catégorie',
                                prefixIcon: Icon(Icons.category_outlined),
                                isDense: true,
                              ),
                              items: [
                                const DropdownMenuItem(
                                  value: null,
                                  child: Text('Toutes les catégories'),
                                ),
                                ...flat.map(
                                  (category) => DropdownMenuItem(
                                    value: category.id,
                                    child: Text(
                                      category.name,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ),
                              ],
                              onChanged: (value) => ref
                                  .read(productFiltersProvider.notifier)
                                  .category(value),
                            );
                          },
                        ),
                      ),
                      SizedBox(
                        width: width,
                        child: DropdownButtonFormField<String?>(
                          initialValue: filters.stockStatus,
                          decoration: const InputDecoration(
                            labelText: 'État du stock',
                            prefixIcon: Icon(Icons.inventory_outlined),
                            isDense: true,
                          ),
                          items: const [
                            DropdownMenuItem(
                              value: null,
                              child: Text('Tous les stocks'),
                            ),
                            DropdownMenuItem(
                              value: 'in_stock',
                              child: Text('En stock'),
                            ),
                            DropdownMenuItem(
                              value: 'low_stock',
                              child: Text('Stock faible'),
                            ),
                            DropdownMenuItem(
                              value: 'out_of_stock',
                              child: Text('Rupture'),
                            ),
                            DropdownMenuItem(
                              value: 'not_managed',
                              child: Text('Stock non suivi'),
                            ),
                          ],
                          onChanged: (value) => ref
                              .read(productFiltersProvider.notifier)
                              .stockStatus(value),
                        ),
                      ),
                    ],
                  );
                },
              ),
            ],
          ),
        ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(inventorySummaryProvider);
              ref.invalidate(productsProvider);
              await Future.wait([
                ref.read(inventorySummaryProvider.future),
                ref.read(productsProvider.future),
              ]);
            },
            child: CustomScrollView(
              controller: _scroll,
              keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(12, 4, 12, 16),
                  sliver: SliverToBoxAdapter(
                    child: summary.when(
                      loading: () => const SizedBox(
                        height: 120,
                        child: Center(child: CircularProgressIndicator()),
                      ),
                      error: (error, _) => ErrorPanel(
                        error: error,
                        onRetry: () => ref.invalidate(inventorySummaryProvider),
                      ),
                      data: (value) =>
                          _Summary(value: value, formatters: formatters),
                    ),
                  ),
                ),
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(12, 8, 12, 10),
                  sliver: SliverToBoxAdapter(
                    child: SectionHeader(
                      title: 'Catalogue',
                      subtitle: products.value == null
                          ? null
                          : '${products.value!.total} variation(s)',
                    ),
                  ),
                ),
                products.when(
                  loading: () => const SliverFillRemaining(
                    child: Center(child: CircularProgressIndicator()),
                  ),
                  error: (error, _) => SliverFillRemaining(
                    child: ErrorPanel(
                      error: error,
                      onRetry: () => ref.invalidate(productsProvider),
                    ),
                  ),
                  data: (result) {
                    if (result.items.isEmpty) {
                      return const SliverFillRemaining(
                        child: EmptyPanel(
                          icon: Icons.search_off,
                          message: 'Aucun produit ne correspond aux filtres.',
                        ),
                      );
                    }
                    return SliverPadding(
                      padding: const EdgeInsets.fromLTRB(12, 0, 12, 34),
                      sliver: SliverList.separated(
                        itemCount:
                            result.items.length + (result.hasMore ? 1 : 0),
                        separatorBuilder: (_, _) => const SizedBox(height: 9),
                        itemBuilder: (context, index) {
                          if (index == result.items.length) {
                            return const Padding(
                              padding: EdgeInsets.all(20),
                              child: Center(child: CircularProgressIndicator()),
                            );
                          }
                          final item = result.items[index];
                          return _ProductTile(
                            item: item,
                            formatters: formatters,
                            onTap: () =>
                                _showProduct(context, item, formatters),
                          );
                        },
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  void _showProduct(
    BuildContext context,
    StockItem item,
    AppFormatters formatters,
  ) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProductDetails(item: item, formatters: formatters),
    );
  }
}

class _Summary extends StatelessWidget {
  const _Summary({required this.value, required this.formatters});

  final InventorySummary value;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context) {
    final items = [
      ('Unités', formatters.number(value.units), Icons.inventory_2_outlined),
      ('Coût', formatters.money(value.costValue), Icons.account_balance_wallet),
      ('Vente', formatters.money(value.retailValue), Icons.sell_outlined),
      ('Marge', formatters.money(value.potentialMargin), Icons.trending_up),
    ];
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Wrap(
          spacing: 24,
          runSpacing: 14,
          children: [
            ...items.map(
              (item) => SizedBox(
                width: 135,
                child: Row(
                  children: [
                    Icon(item.$3, color: AppTheme.teal),
                    const SizedBox(width: 9),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item.$1,
                            style: const TextStyle(color: Colors.blueGrey),
                          ),
                          Text(
                            item.$2,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.w800),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Chip(
              label: Text('${value.lowStock} faible'),
              avatar: const Icon(Icons.warning_amber, color: Colors.orange),
            ),
            Chip(
              label: Text('${value.outOfStock} rupture'),
              avatar: const Icon(Icons.cancel_outlined, color: Colors.red),
            ),
          ],
        ),
      ),
    );
  }
}

class _ProductTile extends StatelessWidget {
  const _ProductTile({
    required this.item,
    required this.formatters,
    required this.onTap,
  });

  final StockItem item;
  final AppFormatters formatters;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = switch (item.status) {
      'out_of_stock' => Colors.red,
      'low_stock' => Colors.orange,
      _ => AppTheme.teal,
    };
    return Card(
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        leading: ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: SizedBox(
            width: 48,
            height: 48,
            child: Image.network(
              item.imageUrl,
              fit: BoxFit.cover,
              errorBuilder: (_, _, _) => ColoredBox(
                color: color.withValues(alpha: .1),
                child: Icon(Icons.inventory_2_outlined, color: color),
              ),
            ),
          ),
        ),
        title: Text(
          item.productName,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: Text(
          [
            if (item.variationName.isNotEmpty) item.variationName,
            if (item.sku.isNotEmpty) 'SKU ${item.sku}',
            if (item.categoryName.isNotEmpty) item.categoryName,
          ].join(' · '),
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              '${formatters.number(item.stock)} ${item.unit}',
              style: TextStyle(color: color, fontWeight: FontWeight.w900),
            ),
            Text(
              formatters.money(item.sellPrice),
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }
}

class _ProductDetails extends ConsumerWidget {
  const _ProductDetails({required this.item, required this.formatters});

  final StockItem item;
  final AppFormatters formatters;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(productDetailProvider(item.variationId));
    return DraggableScrollableSheet(
      initialChildSize: .82,
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
            onRetry: () =>
                ref.invalidate(productDetailProvider(item.variationId)),
          ),
          data: (data) {
            final stocks = mapList(data['stock_by_location']);
            final sales = mapValue(data['sales_last_30_days']);
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
                  item.productName,
                  style: const TextStyle(
                    color: AppTheme.navy,
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                Text(
                  [
                    item.variationName,
                    item.sku,
                  ].where((value) => value.isNotEmpty).join(' · '),
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
                        _detailValue('Stock', formatters.number(data['stock'])),
                        _detailValue(
                          'Prix de vente',
                          formatters.money(data['sell_price']),
                        ),
                        _detailValue(
                          'Prix d’achat',
                          formatters.money(data['purchase_price']),
                        ),
                        _detailValue(
                          'Ventes 30 j',
                          formatters.money(sales['total']),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                const SectionHeader(title: 'Stock par établissement'),
                ...stocks.map(
                  (stock) => ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.store_outlined),
                    title: Text(stock['location_name']?.toString() ?? 'Site'),
                    trailing: Text(
                      formatters.number(stock['stock']),
                      style: const TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ),
                ),
                if (data['description']?.toString().isNotEmpty == true) ...[
                  const SizedBox(height: 18),
                  const SectionHeader(title: 'Description'),
                  const SizedBox(height: 8),
                  Text(
                    data['description'].toString(),
                    style: TextStyle(color: Colors.blueGrey.shade700),
                  ),
                ],
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _detailValue(String label, String value) {
    return SizedBox(
      width: 135,
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
