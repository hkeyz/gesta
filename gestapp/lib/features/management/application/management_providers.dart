import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/management_repository.dart';
import '../domain/models.dart';

final managementRepositoryProvider = Provider<ManagementRepository>(
  (ref) => ManagementRepository(ref.watch(apiClientProvider)),
);

final bootstrapProvider = FutureProvider<BusinessContext>(
  (ref) => ref.watch(managementRepositoryProvider).bootstrap(),
);

class DashboardFilter {
  const DashboardFilter({this.range = 'today', this.locationId});

  final String range;
  final int? locationId;

  DashboardFilter copyWith({
    String? range,
    int? locationId,
    bool clearLocation = false,
  }) {
    return DashboardFilter(
      range: range ?? this.range,
      locationId: clearLocation ? null : locationId ?? this.locationId,
    );
  }
}

class DashboardFilterController extends Notifier<DashboardFilter> {
  @override
  DashboardFilter build() => const DashboardFilter();

  void selectRange(String range) {
    state = state.copyWith(range: range);
  }

  void selectLocation(int? id) {
    state = id == null
        ? state.copyWith(clearLocation: true)
        : state.copyWith(locationId: id);
  }
}

final dashboardFilterProvider =
    NotifierProvider<DashboardFilterController, DashboardFilter>(
      DashboardFilterController.new,
    );

final dashboardProvider = StreamProvider.autoDispose<DashboardData>((
  ref,
) async* {
  final repository = ref.watch(managementRepositoryProvider);
  final filter = ref.watch(dashboardFilterProvider);
  final context = await ref.watch(bootstrapProvider.future);

  while (true) {
    yield await repository.dashboard(
      range: filter.range,
      locationId: filter.locationId,
    );
    await Future<void>.delayed(
      Duration(seconds: context.dashboardRefreshSeconds),
    );
  }
});

class ActivityFeedState {
  const ActivityFeedState({
    required this.items,
    this.hasMore = true,
    this.isLoadingMore = false,
  });

  final List<ActivityItem> items;
  final bool hasMore;
  final bool isLoadingMore;

  ActivityFeedState copyWith({
    List<ActivityItem>? items,
    bool? hasMore,
    bool? isLoadingMore,
  }) {
    return ActivityFeedState(
      items: items ?? this.items,
      hasMore: hasMore ?? this.hasMore,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
    );
  }
}

class ActivityFeedController extends AsyncNotifier<ActivityFeedState> {
  Timer? _timer;

  @override
  Future<ActivityFeedState> build() async {
    final repository = ref.watch(managementRepositoryProvider);
    final locationId = ref.watch(dashboardFilterProvider).locationId;
    final context = await ref.watch(bootstrapProvider.future);
    final items = await repository.activities(locationId: locationId);
    _timer?.cancel();
    _timer = Timer.periodic(
      Duration(seconds: context.activityRefreshSeconds),
      (_) => unawaited(refreshNew()),
    );
    ref.onDispose(() => _timer?.cancel());
    return ActivityFeedState(items: items, hasMore: items.length >= 40);
  }

  Future<void> refreshNew() async {
    final current = state.value;
    if (current == null || current.items.isEmpty) {
      ref.invalidateSelf();
      return;
    }
    final newest = current.items.first.transaction;
    try {
      final incoming = await ref
          .read(managementRepositoryProvider)
          .activities(
            locationId: ref.read(dashboardFilterProvider).locationId,
            since: newest.updatedAt ?? newest.occurredAt,
            afterId: newest.id,
          );
      if (incoming.isEmpty) return;
      final byId = {
        for (final item in current.items) item.transaction.id: item,
        for (final item in incoming) item.transaction.id: item,
      };
      final merged = byId.values.toList()
        ..sort((a, b) => _activityDate(b).compareTo(_activityDate(a)));
      state = AsyncData(current.copyWith(items: merged));
    } catch (_) {
      // The connection banner reports the failure while current data stays in memory.
    }
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null ||
        current.items.isEmpty ||
        !current.hasMore ||
        current.isLoadingMore) {
      return;
    }
    state = AsyncData(current.copyWith(isLoadingMore: true));
    final oldest = current.items.last.transaction;
    try {
      final older = await ref
          .read(managementRepositoryProvider)
          .activities(
            locationId: ref.read(dashboardFilterProvider).locationId,
            before: oldest.updatedAt ?? oldest.occurredAt,
            beforeId: oldest.id,
          );
      state = AsyncData(
        current.copyWith(
          items: [...current.items, ...older],
          hasMore: older.length >= 40,
          isLoadingMore: false,
        ),
      );
    } catch (_) {
      state = AsyncData(current.copyWith(isLoadingMore: false));
    }
  }

  DateTime _activityDate(ActivityItem item) {
    return item.transaction.updatedAt ??
        item.transaction.occurredAt ??
        DateTime.fromMillisecondsSinceEpoch(0);
  }
}

final activityProvider =
    AsyncNotifierProvider<ActivityFeedController, ActivityFeedState>(
      ActivityFeedController.new,
    );

class TransactionTypeController extends Notifier<String> {
  @override
  String build() => 'sell';

  void select(String value) => state = value;
}

final transactionTypeProvider =
    NotifierProvider<TransactionTypeController, String>(
      TransactionTypeController.new,
    );

class OperationFilters {
  const OperationFilters({
    this.search = '',
    this.status,
    this.paymentStatus,
    this.from,
    this.to,
  });

  final String search;
  final String? status;
  final String? paymentStatus;
  final DateTime? from;
  final DateTime? to;

  int get activeCount =>
      [status, paymentStatus, from, to].where((value) => value != null).length;

  OperationFilters copyWith({
    String? search,
    String? status,
    String? paymentStatus,
    DateTime? from,
    DateTime? to,
    bool clearStatus = false,
    bool clearPayment = false,
    bool clearDates = false,
  }) {
    return OperationFilters(
      search: search ?? this.search,
      status: clearStatus ? null : status ?? this.status,
      paymentStatus: clearPayment ? null : paymentStatus ?? this.paymentStatus,
      from: clearDates ? null : from ?? this.from,
      to: clearDates ? null : to ?? this.to,
    );
  }
}

class OperationFiltersController extends Notifier<OperationFilters> {
  @override
  OperationFilters build() => const OperationFilters();

  void search(String value) => state = state.copyWith(search: value);

  void apply(OperationFilters value) => state = value;

  void clear() => state = const OperationFilters();
}

final operationFiltersProvider =
    NotifierProvider<OperationFiltersController, OperationFilters>(
      OperationFiltersController.new,
    );

class TransactionsController
    extends AsyncNotifier<PageResult<TransactionItem>> {
  @override
  Future<PageResult<TransactionItem>> build() {
    ref.watch(dashboardFilterProvider);
    ref.watch(transactionTypeProvider);
    ref.watch(operationFiltersProvider);
    ref.watch(bootstrapProvider);
    return _fetch(1);
  }

  Future<PageResult<TransactionItem>> _fetch(int page) async {
    final repository = ref.read(managementRepositoryProvider);
    final location = ref.read(dashboardFilterProvider).locationId;
    final selectedType = ref.read(transactionTypeProvider);
    final filters = ref.read(operationFiltersProvider);
    final context = await ref.read(bootstrapProvider.future);
    final allowedTypes = [
      if (context.features['sales'] ?? false) 'sell',
      if (context.features['sales'] ?? false) 'sell_return',
      if (context.features['purchases'] ?? false) 'purchase',
      if (context.features['expenses'] ?? false) 'expense',
    ];
    final type = allowedTypes.contains(selectedType)
        ? selectedType
        : allowedTypes.first;
    return repository.transactions(
      type: type,
      locationId: location,
      search: filters.search,
      status: filters.status,
      paymentStatus: filters.paymentStatus,
      from: filters.from,
      to: filters.to,
      page: page,
    );
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.isLoadingMore) return;
    state = AsyncData(current.loadingMore(true));
    try {
      final next = await _fetch(current.currentPage + 1);
      state = AsyncData(current.append(next));
    } catch (_) {
      state = AsyncData(current.loadingMore(false));
    }
  }
}

final transactionsProvider =
    AsyncNotifierProvider<TransactionsController, PageResult<TransactionItem>>(
      TransactionsController.new,
    );

final transactionDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) {
      return ref.watch(managementRepositoryProvider).transactionDetail(id);
    });

final inventorySummaryProvider = FutureProvider.autoDispose<InventorySummary>((
  ref,
) {
  final repository = ref.watch(managementRepositoryProvider);
  final filter = ref.watch(dashboardFilterProvider);
  return repository.inventorySummary(locationId: filter.locationId);
});

final lowStockProvider = FutureProvider.autoDispose<List<StockItem>>((ref) {
  final repository = ref.watch(managementRepositoryProvider);
  final filter = ref.watch(dashboardFilterProvider);
  return repository.lowStock(locationId: filter.locationId);
});

class ProductFilters {
  const ProductFilters({this.search = '', this.categoryId, this.stockStatus});

  final String search;
  final int? categoryId;
  final String? stockStatus;

  ProductFilters copyWith({
    String? search,
    int? categoryId,
    String? stockStatus,
    bool clearCategory = false,
    bool clearStatus = false,
  }) {
    return ProductFilters(
      search: search ?? this.search,
      categoryId: clearCategory ? null : categoryId ?? this.categoryId,
      stockStatus: clearStatus ? null : stockStatus ?? this.stockStatus,
    );
  }
}

class ProductFiltersController extends Notifier<ProductFilters> {
  @override
  ProductFilters build() => const ProductFilters();

  void search(String value) => state = state.copyWith(search: value);

  void category(int? value) => state = value == null
      ? state.copyWith(clearCategory: true)
      : state.copyWith(categoryId: value);

  void stockStatus(String? value) => state = value == null
      ? state.copyWith(clearStatus: true)
      : state.copyWith(stockStatus: value);
}

final productFiltersProvider =
    NotifierProvider<ProductFiltersController, ProductFilters>(
      ProductFiltersController.new,
    );

class ProductsController extends AsyncNotifier<PageResult<StockItem>> {
  @override
  Future<PageResult<StockItem>> build() {
    ref.watch(productFiltersProvider);
    ref.watch(dashboardFilterProvider);
    return _fetch(1);
  }

  Future<PageResult<StockItem>> _fetch(int page) {
    final filters = ref.read(productFiltersProvider);
    final locationId = ref.read(dashboardFilterProvider).locationId;
    return ref
        .read(managementRepositoryProvider)
        .products(
          locationId: locationId,
          search: filters.search,
          categoryId: filters.categoryId,
          stockStatus: filters.stockStatus,
          page: page,
        );
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.isLoadingMore) return;
    state = AsyncData(current.loadingMore(true));
    try {
      state = AsyncData(current.append(await _fetch(current.currentPage + 1)));
    } catch (_) {
      state = AsyncData(current.loadingMore(false));
    }
  }
}

final productsProvider =
    AsyncNotifierProvider<ProductsController, PageResult<StockItem>>(
      ProductsController.new,
    );

final productCategoriesProvider = FutureProvider<List<CategoryItem>>(
  (ref) => ref.watch(managementRepositoryProvider).categories(),
);

final productDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, variationId) {
      final locationId = ref.watch(dashboardFilterProvider).locationId;
      return ref
          .watch(managementRepositoryProvider)
          .productDetail(variationId: variationId, locationId: locationId);
    });

class ContactFilters {
  const ContactFilters({this.search = '', this.type});

  final String search;
  final String? type;

  ContactFilters copyWith({
    String? search,
    String? type,
    bool clearType = false,
  }) {
    return ContactFilters(
      search: search ?? this.search,
      type: clearType ? null : type ?? this.type,
    );
  }
}

class ContactFiltersController extends Notifier<ContactFilters> {
  @override
  ContactFilters build() => const ContactFilters();

  void search(String value) => state = state.copyWith(search: value);

  void type(String? value) => state = value == null
      ? state.copyWith(clearType: true)
      : state.copyWith(type: value);
}

final contactFiltersProvider =
    NotifierProvider<ContactFiltersController, ContactFilters>(
      ContactFiltersController.new,
    );

class ContactsController extends AsyncNotifier<PageResult<ContactItem>> {
  @override
  Future<PageResult<ContactItem>> build() {
    ref.watch(contactFiltersProvider);
    return _fetch(1);
  }

  Future<PageResult<ContactItem>> _fetch(int page) {
    final filters = ref.read(contactFiltersProvider);
    return ref
        .read(managementRepositoryProvider)
        .contacts(type: filters.type, search: filters.search, page: page);
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.isLoadingMore) return;
    state = AsyncData(current.loadingMore(true));
    try {
      state = AsyncData(current.append(await _fetch(current.currentPage + 1)));
    } catch (_) {
      state = AsyncData(current.loadingMore(false));
    }
  }
}

final contactsProvider =
    AsyncNotifierProvider<ContactsController, PageResult<ContactItem>>(
      ContactsController.new,
    );

final contactDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>(
      (ref, id) => ref.watch(managementRepositoryProvider).contactDetail(id),
    );

final openCashRegistersProvider =
    StreamProvider.autoDispose<List<Map<String, dynamic>>>((ref) async* {
      final repository = ref.watch(managementRepositoryProvider);
      final filter = ref.watch(dashboardFilterProvider);
      final context = await ref.watch(bootstrapProvider.future);

      while (true) {
        yield await repository.openCashRegisters(locationId: filter.locationId);
        await Future<void>.delayed(
          Duration(seconds: context.dashboardRefreshSeconds),
        );
      }
    });

final cashRegisterDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>(
      (ref, id) =>
          ref.watch(managementRepositoryProvider).cashRegisterDetail(id),
    );
