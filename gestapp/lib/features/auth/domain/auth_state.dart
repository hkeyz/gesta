class AuthState {
  const AuthState({
    required this.isAuthenticated,
    required this.serverUrl,
    this.user = const {},
    this.message,
  });

  final bool isAuthenticated;
  final String serverUrl;
  final Map<String, dynamic> user;
  final String? message;

  factory AuthState.signedOut(String serverUrl, {String? message}) {
    return AuthState(
      isAuthenticated: false,
      serverUrl: serverUrl,
      message: message,
    );
  }

  factory AuthState.signedIn(String serverUrl, Map<String, dynamic> user) {
    return AuthState(isAuthenticated: true, serverUrl: serverUrl, user: user);
  }
}
