import 'package:flutter/material.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  int _currentStep = 1; // 1: Request, 2: OTP, 3: Reset
  bool _isLoading = false;
  String? _errorMessage;

  // Step 1
  final _emailController = TextEditingController();
  final _fullNameController = TextEditingController();
  final _identifierController = TextEditingController();

  // Step 2
  final _otpController = TextEditingController();
  int? _userId;

  // Step 3
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  String? _resetToken;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _fullNameController.dispose();
    _identifierController.dispose();
    _otpController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  void _showError(String message) {
    setState(() {
      _errorMessage = message.replaceAll('Exception: ', '');
    });
  }

  Future<void> _submitRequest() async {
    if (_emailController.text.isEmpty || _fullNameController.text.isEmpty || _identifierController.text.isEmpty) {
      _showError('Semua kolom harus diisi.');
      return;
    }
    setState(() { _isLoading = true; _errorMessage = null; });
    try {
      final res = await MockDatabase().requestPasswordReset(
        _emailController.text,
        _fullNameController.text,
        _identifierController.text,
      );
      if (mounted) {
        setState(() {
          _userId = res['user_id'];
          _currentStep = 2;
        });
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'OTP dikirim')));
      }
    } catch (e) {
      _showError(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _submitOtp() async {
    if (_otpController.text.length != 6) {
      _showError('OTP harus 6 digit.');
      return;
    }
    setState(() { _isLoading = true; _errorMessage = null; });
    try {
      final res = await MockDatabase().verifyPasswordResetOtp(_userId!, _otpController.text);
      if (mounted) {
        setState(() {
          _resetToken = res['reset_token'];
          _currentStep = 3;
        });
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'Verifikasi berhasil')));
      }
    } catch (e) {
      _showError(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _submitReset() async {
    if (_passwordController.text.isEmpty || _confirmPasswordController.text.isEmpty) {
      _showError('Password tidak boleh kosong.');
      return;
    }
    if (_passwordController.text != _confirmPasswordController.text) {
      _showError('Password konfirmasi tidak cocok.');
      return;
    }
    setState(() { _isLoading = true; _errorMessage = null; });
    try {
      final res = await MockDatabase().submitNewPassword(
        _userId!,
        _resetToken!,
        _passwordController.text,
        _confirmPasswordController.text,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'Password diperbarui')));
        Navigator.pop(context); // Back to login
      }
    } catch (e) {
      _showError(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: AppBar(
        title: const Text('Lupa Password'),
        backgroundColor: Colors.white,
        foregroundColor: AppTheme.primaryNavy,
        elevation: 1,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppTheme.borderLight),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    _currentStep == 1 ? 'Identifikasi Akun' : _currentStep == 2 ? 'Verifikasi OTP' : 'Buat Password Baru',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.primaryNavy),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _currentStep == 1
                        ? 'Masukkan data akun Anda yang terdaftar.'
                        : _currentStep == 2
                            ? 'Masukkan 6 digit OTP yang dikirim ke WhatsApp Anda.'
                            : 'Silakan buat password baru untuk akun Anda.',
                    style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),

                  if (_errorMessage != null) ...[
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(color: Colors.red[50], border: Border.all(color: Colors.red[200]!), borderRadius: BorderRadius.circular(12)),
                      child: Row(children: [
                        Icon(Icons.error_outline, color: Colors.red[700], size: 20),
                        const SizedBox(width: 10),
                        Expanded(child: Text(_errorMessage!, style: TextStyle(color: Colors.red[700], fontWeight: FontWeight.w500, fontSize: 13))),
                      ]),
                    ),
                    const SizedBox(height: 16),
                  ],

                  if (_currentStep == 1) ...[
                    TextField(
                      controller: _emailController,
                      decoration: const InputDecoration(labelText: 'Email Aktif', prefixIcon: Icon(Icons.email_outlined)),
                      keyboardType: TextInputType.emailAddress,
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _fullNameController,
                      decoration: const InputDecoration(labelText: 'Nama Lengkap Sesuai Akun', prefixIcon: Icon(Icons.person_outline)),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _identifierController,
                      decoration: const InputDecoration(labelText: 'NISN / NIP', prefixIcon: Icon(Icons.badge_outlined)),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _submitRequest,
                        style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryBlue, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                        child: _isLoading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5)) : const Text('KIRIM OTP'),
                      ),
                    ),
                  ] else if (_currentStep == 2) ...[
                    TextField(
                      controller: _otpController,
                      decoration: const InputDecoration(labelText: '6 Digit OTP', prefixIcon: Icon(Icons.message_outlined)),
                      keyboardType: TextInputType.number,
                      maxLength: 6,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 24, letterSpacing: 8, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _submitOtp,
                        style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryBlue, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                        child: _isLoading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5)) : const Text('VERIFIKASI OTP'),
                      ),
                    ),
                  ] else if (_currentStep == 3) ...[
                    TextField(
                      controller: _passwordController,
                      obscureText: _obscurePassword,
                      decoration: InputDecoration(
                        labelText: 'Password Baru',
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: Icon(_obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                          onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _confirmPasswordController,
                      obscureText: _obscureConfirmPassword,
                      decoration: InputDecoration(
                        labelText: 'Konfirmasi Password Baru',
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: Icon(_obscureConfirmPassword ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                          onPressed: () => setState(() => _obscureConfirmPassword = !_obscureConfirmPassword),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _submitReset,
                        style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryBlue, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                        child: _isLoading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5)) : const Text('SIMPAN PASSWORD'),
                      ),
                    ),
                  ]
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
