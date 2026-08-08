import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/theme/app_theme.dart';
import 'features/auth/application/auth_controller.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/shell/presentation/app_shell.dart';

class GestaApp extends ConsumerWidget {
  const GestaApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);

    return MaterialApp(
      title: 'Gesta Pilot',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      home: auth.when(
        loading: () => const _StartupScreen(),
        error: (error, _) => LoginScreen(startupError: error.toString()),
        data: (state) => state.isAuthenticated
            ? const AppShell()
            : LoginScreen(startupError: state.message),
      ),
    );
  }
}

class _StartupScreen extends StatelessWidget {
  const _StartupScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}
