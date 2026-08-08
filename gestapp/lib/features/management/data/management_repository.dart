import '../../../core/network/api_client.dart';
import '../domain/models.dart';

class ManagementRepository {
  ManagementRepository(this._client);

  final ApiClient _client;

  Future<BusinessContext> bootstrap() async {
    final response = await _client.get('/bootstrap');
    return BusinessContext.fromJson(_data(response));
  }

  Future<DashboardData> dashboard({
    required String range,
    int? locationId,
  }) async {
    final response = await _client.get(
      '/dashboard',
      query: _query({'range': range, 'location_id': locationId}),
    );
    return DashboardData.fromJson(_data(response));
  }

  Future<List<ActivityItem>> activities({
    int? locationId,
    DateTime? since,
    int afterId = 0,
    DateTime? before,
    int beforeId = 0,
  }) async {
    final response = await _client.get(
      '/activities',
      query: _query({
        'location_id': locationId,
        'since': since?.toIso8601String(),
        'after_id': since == null ? null : afterId,
        'before': before?.toIso8601String(),
        'before_id': before == null ? null : beforeId,
        'limit': 40,
      }),
    );
    final payload = _data(response);
    return mapList(payload['items']).map(ActivityItem.fromJson).toList();
  }

  Future<PageResult<TransactionItem>> transactions({
    required String type,
    int? locationId,
    String? search,
    String? status,
    String? paymentStatus,
    DateTime? from,
    DateTime? to,
    int page = 1,
  }) async {
    final endpoint = switch (type) {
      'purchase' => '/purchases',
      'expense' => '/expenses',
      _ => '/sales',
    };
    final response = await _client.get(
      endpoint,
      query: _query({
        'location_id': locationId,
        'search': search,
        'status': status,
        'payment_status': paymentStatus,
        'from': from?.toIso8601String(),
        'to': to?.toIso8601String(),
        'page': page,
        'per_page': 25,
      }),
    );
    return _page(response, TransactionItem.fromJson);
  }

  Future<Map<String, dynamic>> transactionDetail(int id) async {
    final response = await _client.get('/transactions/$id');
    return _data(response);
  }

  Future<InventorySummary> inventorySummary({int? locationId}) async {
    final response = await _client.get(
      '/inventory/summary',
      query: _query({'location_id': locationId}),
    );
    return InventorySummary.fromJson(_data(response));
  }

  Future<List<StockItem>> lowStock({int? locationId}) async {
    final response = await _client.get(
      '/inventory/low-stock',
      query: _query({'location_id': locationId, 'per_page': 50}),
    );
    return mapList(response['data']).map(StockItem.fromJson).toList();
  }

  Future<PageResult<StockItem>> products({
    int? locationId,
    String? search,
    int? categoryId,
    String? stockStatus,
    int page = 1,
  }) async {
    final response = await _client.get(
      '/inventory/products',
      query: _query({
        'location_id': locationId,
        'search': search,
        'category_id': categoryId,
        'stock_status': stockStatus,
        'page': page,
        'per_page': 25,
      }),
    );
    return _page(response, StockItem.fromJson);
  }

  Future<List<CategoryItem>> categories() async {
    final response = await _client.get('/inventory/categories');
    return mapList(response['data']).map(CategoryItem.fromJson).toList();
  }

  Future<Map<String, dynamic>> productDetail({
    required int variationId,
    int? locationId,
  }) async {
    final response = await _client.get(
      '/inventory/products/$variationId',
      query: _query({'location_id': locationId}),
    );
    return _data(response);
  }

  Future<PageResult<ContactItem>> contacts({
    String? type,
    String? search,
    int page = 1,
  }) async {
    final response = await _client.get(
      '/contacts',
      query: _query({
        'contact_type': type,
        'search': search,
        'page': page,
        'per_page': 25,
      }),
    );
    return _page(response, ContactItem.fromJson);
  }

  Future<Map<String, dynamic>> contactDetail(int id) async {
    final response = await _client.get('/contacts/$id');
    return _data(response);
  }

  Future<List<Map<String, dynamic>>> openCashRegisters({
    int? locationId,
  }) async {
    final response = await _client.get(
      '/cash-registers/open',
      query: _query({'location_id': locationId}),
    );
    return mapList(response['data']);
  }

  Future<Map<String, dynamic>> cashRegisterDetail(int id) async {
    final response = await _client.get('/cash-registers/$id');
    return _data(response);
  }

  PageResult<T> _page<T>(
    Map<String, dynamic> response,
    T Function(Map<String, dynamic>) convert,
  ) {
    final pagination = mapValue(mapValue(response['meta'])['pagination']);
    return PageResult(
      items: mapList(response['data']).map(convert).toList(),
      currentPage: intValue(pagination['current_page']),
      lastPage: intValue(pagination['last_page']),
      total: intValue(pagination['total']),
    );
  }

  Map<String, dynamic> _data(Map<String, dynamic> response) {
    return mapValue(response['data']);
  }

  Map<String, dynamic> _query(Map<String, dynamic> input) {
    return Map.fromEntries(
      input.entries.where(
        (entry) => entry.value != null && entry.value.toString().isNotEmpty,
      ),
    );
  }
}
