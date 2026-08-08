double numberValue(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

int intValue(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

Map<String, dynamic> mapValue(dynamic value) {
  return value is Map ? Map<String, dynamic>.from(value) : {};
}

List<Map<String, dynamic>> mapList(dynamic value) {
  return value is List ? value.map(mapValue).toList() : [];
}

class PageResult<T> {
  const PageResult({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    this.isLoadingMore = false,
  });

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;
  final bool isLoadingMore;

  bool get hasMore => currentPage < lastPage;

  PageResult<T> append(PageResult<T> next) {
    return PageResult(
      items: [...items, ...next.items],
      currentPage: next.currentPage,
      lastPage: next.lastPage,
      total: next.total,
    );
  }

  PageResult<T> loadingMore(bool value) {
    return PageResult(
      items: items,
      currentPage: currentPage,
      lastPage: lastPage,
      total: total,
      isLoadingMore: value,
    );
  }
}

class BusinessContext {
  const BusinessContext({
    required this.businessName,
    required this.userName,
    required this.currencySymbol,
    required this.currencyCode,
    required this.precision,
    required this.locations,
    required this.features,
    required this.dashboardRefreshSeconds,
    required this.activityRefreshSeconds,
  });

  final String businessName;
  final String userName;
  final String currencySymbol;
  final String currencyCode;
  final int precision;
  final List<LocationItem> locations;
  final Map<String, bool> features;
  final int dashboardRefreshSeconds;
  final int activityRefreshSeconds;

  factory BusinessContext.fromJson(Map<String, dynamic> json) {
    final business = mapValue(json['business']);
    final user = mapValue(json['user']);
    final currency = mapValue(business['currency']);
    final realtime = mapValue(json['realtime']);
    final rawFeatures = mapValue(json['features']);

    return BusinessContext(
      businessName: business['name']?.toString() ?? 'Gesta',
      userName:
          user['full_name']?.toString() ??
          user['username']?.toString() ??
          'Utilisateur',
      currencySymbol: currency['symbol']?.toString() ?? '',
      currencyCode: currency['code']?.toString() ?? '',
      precision: intValue(currency['precision']),
      locations: mapList(json['locations']).map(LocationItem.fromJson).toList(),
      features: rawFeatures.map(
        (key, value) => MapEntry(key, value == true || value == 1),
      ),
      dashboardRefreshSeconds: intValue(
        realtime['dashboard_refresh_seconds'],
      ).clamp(10, 300),
      activityRefreshSeconds: intValue(
        realtime['activity_refresh_seconds'],
      ).clamp(5, 300),
    );
  }
}

class LocationItem {
  const LocationItem({required this.id, required this.name});

  final int id;
  final String name;

  factory LocationItem.fromJson(Map<String, dynamic> json) {
    return LocationItem(
      id: intValue(json['id']),
      name: json['name']?.toString() ?? 'Site',
    );
  }
}

class DashboardData {
  const DashboardData({
    required this.metrics,
    required this.trend,
    required this.topProducts,
    required this.recentTransactions,
    required this.openRegisters,
    required this.alerts,
    required this.range,
  });

  final Map<String, dynamic> metrics;
  final List<Map<String, dynamic>> trend;
  final List<Map<String, dynamic>> topProducts;
  final List<TransactionItem> recentTransactions;
  final List<Map<String, dynamic>> openRegisters;
  final Map<String, dynamic> alerts;
  final Map<String, dynamic> range;

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    return DashboardData(
      metrics: mapValue(json['metrics']),
      trend: mapList(json['trend']),
      topProducts: mapList(json['top_products']),
      recentTransactions: mapList(
        json['recent_transactions'],
      ).map(TransactionItem.fromJson).toList(),
      openRegisters: mapList(json['open_registers']),
      alerts: mapValue(json['alerts']),
      range: mapValue(json['range']),
    );
  }
}

class TransactionItem {
  const TransactionItem({
    required this.id,
    required this.type,
    required this.reference,
    required this.status,
    required this.paymentStatus,
    required this.amount,
    required this.occurredAt,
    required this.updatedAt,
    required this.contact,
    required this.location,
    required this.user,
  });

  final int id;
  final String type;
  final String reference;
  final String status;
  final String paymentStatus;
  final double amount;
  final DateTime? occurredAt;
  final DateTime? updatedAt;
  final String contact;
  final String location;
  final String user;

  factory TransactionItem.fromJson(Map<String, dynamic> json) {
    final contact = json['contact'];
    final location = json['location'];
    final user = json['user'];

    return TransactionItem(
      id: intValue(json['id']),
      type: json['type']?.toString() ?? 'transaction',
      reference: json['reference']?.toString() ?? '#${json['id']}',
      status: json['status']?.toString() ?? '',
      paymentStatus: json['payment_status']?.toString() ?? '',
      amount: numberValue(json['amount']),
      occurredAt: DateTime.tryParse(json['occurred_at']?.toString() ?? ''),
      updatedAt: DateTime.tryParse(json['updated_at']?.toString() ?? ''),
      contact: contact is Map
          ? contact['name']?.toString() ?? ''
          : contact?.toString() ?? '',
      location: location is Map
          ? location['name']?.toString() ?? ''
          : location?.toString() ?? '',
      user: user is Map
          ? user['name']?.toString() ?? ''
          : user?.toString() ?? '',
    );
  }
}

class ActivityItem {
  const ActivityItem({
    required this.transaction,
    required this.category,
    required this.title,
    required this.description,
  });

  final TransactionItem transaction;
  final String category;
  final String title;
  final String description;

  factory ActivityItem.fromJson(Map<String, dynamic> json) {
    return ActivityItem(
      transaction: TransactionItem.fromJson(json),
      category: json['category']?.toString() ?? 'transaction',
      title: json['title']?.toString() ?? 'Activité',
      description: json['description']?.toString() ?? '',
    );
  }
}

class InventorySummary {
  const InventorySummary({
    required this.units,
    required this.costValue,
    required this.retailValue,
    required this.potentialMargin,
    required this.lowStock,
    required this.outOfStock,
  });

  final double units;
  final double costValue;
  final double retailValue;
  final double potentialMargin;
  final int lowStock;
  final int outOfStock;

  factory InventorySummary.fromJson(Map<String, dynamic> json) {
    return InventorySummary(
      units: numberValue(json['units_in_stock']),
      costValue: numberValue(json['cost_value']),
      retailValue: numberValue(json['retail_value']),
      potentialMargin: numberValue(json['potential_margin']),
      lowStock: intValue(json['low_stock_count']),
      outOfStock: intValue(json['out_of_stock_count']),
    );
  }
}

class StockItem {
  const StockItem({
    required this.productId,
    required this.variationId,
    required this.productName,
    required this.variationName,
    required this.sku,
    required this.stock,
    required this.unit,
    required this.status,
    required this.sellPrice,
    required this.purchasePrice,
    required this.categoryName,
    required this.subCategoryName,
    required this.imageUrl,
  });

  final int productId;
  final int variationId;
  final String productName;
  final String variationName;
  final String sku;
  final double stock;
  final String unit;
  final String status;
  final double sellPrice;
  final double purchasePrice;
  final String categoryName;
  final String subCategoryName;
  final String imageUrl;

  factory StockItem.fromJson(Map<String, dynamic> json) {
    return StockItem(
      productId: intValue(json['product_id']),
      variationId: intValue(json['variation_id']),
      productName: json['product_name']?.toString() ?? 'Produit',
      variationName: json['variation_name']?.toString() ?? '',
      sku: json['sku']?.toString() ?? '',
      stock: numberValue(json['stock']),
      unit: json['unit']?.toString() ?? '',
      status: json['stock_status']?.toString() ?? '',
      sellPrice: numberValue(json['sell_price']),
      purchasePrice: numberValue(json['purchase_price']),
      categoryName: json['category_name']?.toString() ?? '',
      subCategoryName: json['sub_category_name']?.toString() ?? '',
      imageUrl: json['image_url']?.toString() ?? '',
    );
  }
}

class CategoryItem {
  const CategoryItem({
    required this.id,
    required this.name,
    required this.children,
  });

  final int id;
  final String name;
  final List<CategoryItem> children;

  factory CategoryItem.fromJson(Map<String, dynamic> json) {
    return CategoryItem(
      id: intValue(json['id']),
      name: json['name']?.toString() ?? 'Catégorie',
      children: mapList(json['children']).map(CategoryItem.fromJson).toList(),
    );
  }
}

class ContactItem {
  const ContactItem({
    required this.id,
    required this.type,
    required this.name,
    required this.personName,
    required this.mobile,
    required this.email,
    required this.balance,
    required this.address,
  });

  final int id;
  final String type;
  final String name;
  final String personName;
  final String mobile;
  final String email;
  final double balance;
  final List<String> address;

  factory ContactItem.fromJson(Map<String, dynamic> json) {
    return ContactItem(
      id: intValue(json['id']),
      type: json['type']?.toString() ?? 'customer',
      name: json['name']?.toString() ?? 'Contact',
      personName: json['person_name']?.toString() ?? '',
      mobile: json['mobile']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      balance: numberValue(json['balance']),
      address: json['address'] is List
          ? List<String>.from(
              (json['address'] as List).map((value) => value.toString()),
            )
          : const [],
    );
  }
}
