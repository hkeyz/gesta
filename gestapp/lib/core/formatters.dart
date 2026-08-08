import 'package:intl/intl.dart';

import '../features/management/domain/models.dart';

class AppFormatters {
  AppFormatters(BusinessContext context)
    : _currency = NumberFormat.currency(
        locale: 'fr_FR',
        symbol: context.currencySymbol.isNotEmpty
            ? context.currencySymbol
            : context.currencyCode,
        decimalDigits: context.precision,
      ),
      _number = NumberFormat.decimalPattern('fr_FR'),
      _date = DateFormat('dd/MM HH:mm');

  final NumberFormat _currency;
  final NumberFormat _number;
  final DateFormat _date;

  String money(dynamic value) => _currency.format(numberValue(value));

  String number(dynamic value) => _number.format(numberValue(value));

  String date(DateTime? value) {
    return value == null ? '—' : _date.format(value.toLocal());
  }
}
