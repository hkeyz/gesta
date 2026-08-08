class ApiException implements Exception {
  const ApiException(
    this.message, {
    this.statusCode,
    this.code,
    this.isNetworkError = false,
  });

  final String message;
  final int? statusCode;
  final String? code;
  final bool isNetworkError;

  @override
  String toString() => message;
}
