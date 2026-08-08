import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/theme/app_theme.dart';
import '../application/auth_controller.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key, this.startupError});

  final String? startupError;

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _username = TextEditingController();
  final _password = TextEditingController();
  late final TextEditingController _server;
  bool _hidePassword = true;

  @override
  void initState() {
    super.initState();
    final current = ref.read(authControllerProvider).value;
    _server = TextEditingController(
      text: current?.serverUrl ?? AppConfig.defaultApiUrl,
    );
  }

  @override
  void dispose() {
    _username.dispose();
    _password.dispose();
    _server.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    await ref
        .read(authControllerProvider.notifier)
        .login(
          username: _username.text,
          password: _password.text,
          serverUrl: _server.text,
        );
  }

  @override
  Widget build(BuildContext context) {
    final error =
        ref
            .watch(authControllerProvider)
            .whenOrNull(
              error: (value, _) => value.toString(),
              data: (value) => value.message,
            ) ??
        widget.startupError;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const _Brand(),
                    const SizedBox(height: 36),
                    Text(
                      'Pilotez votre activité',
                      style: Theme.of(context).textTheme.headlineMedium
                          ?.copyWith(
                            color: AppTheme.navy,
                            fontWeight: FontWeight.w800,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Ventes, trésorerie et stock en un coup d’œil.',
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        color: Colors.blueGrey.shade600,
                      ),
                    ),
                    if (error != null && error.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      _ErrorBanner(message: error),
                    ],
                    const SizedBox(height: 24),
                    TextFormField(
                      controller: _username,
                      textInputAction: TextInputAction.next,
                      decoration: const InputDecoration(
                        labelText: 'Nom d’utilisateur',
                        prefixIcon: Icon(Icons.person_outline),
                      ),
                      validator: (value) =>
                          value == null || value.trim().isEmpty
                          ? 'Saisissez votre nom d’utilisateur.'
                          : null,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _password,
                      obscureText: _hidePassword,
                      onFieldSubmitted: (_) => _submit(),
                      decoration: InputDecoration(
                        labelText: 'Mot de passe',
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          onPressed: () =>
                              setState(() => _hidePassword = !_hidePassword),
                          icon: Icon(
                            _hidePassword
                                ? Icons.visibility_outlined
                                : Icons.visibility_off_outlined,
                          ),
                        ),
                      ),
                      validator: (value) => value == null || value.isEmpty
                          ? 'Saisissez votre mot de passe.'
                          : null,
                    ),
                    const SizedBox(height: 14),
                    ExpansionTile(
                      tilePadding: EdgeInsets.zero,
                      title: const Text('Adresse du serveur'),
                      children: [
                        TextFormField(
                          controller: _server,
                          keyboardType: TextInputType.url,
                          decoration: const InputDecoration(
                            hintText: 'https://domaine.tld/api/mobile/v1',
                            prefixIcon: Icon(Icons.dns_outlined),
                          ),
                          validator: (value) {
                            final uri = Uri.tryParse(value?.trim() ?? '');
                            return uri != null &&
                                    (uri.scheme == 'http' ||
                                        uri.scheme == 'https') &&
                                    uri.host.isNotEmpty
                                ? null
                                : 'Saisissez une adresse HTTP(S) valide.';
                          },
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),
                    FilledButton.icon(
                      onPressed: _submit,
                      icon: const Icon(Icons.login),
                      label: const Padding(
                        padding: EdgeInsets.symmetric(vertical: 14),
                        child: Text('Se connecter'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _Brand extends StatelessWidget {
  const _Brand();

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 52,
          height: 52,
          decoration: BoxDecoration(
            color: AppTheme.navy,
            borderRadius: BorderRadius.circular(16),
          ),
          child: const Icon(Icons.insights_rounded, color: Colors.white),
        ),
        const SizedBox(width: 14),
        const Text(
          'GESTA PILOT',
          style: TextStyle(
            color: AppTheme.navy,
            fontSize: 20,
            fontWeight: FontWeight.w900,
            letterSpacing: 1.3,
          ),
        ),
      ],
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          Icon(Icons.error_outline, color: Colors.red.shade700),
          const SizedBox(width: 10),
          Expanded(child: Text(message)),
        ],
      ),
    );
  }
}
