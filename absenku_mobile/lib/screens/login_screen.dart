// lib/screens/login_screen.dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../core/app_colors.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _identifierController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscure = true;
  bool _rememberMe = false;

  @override
  void dispose() {
    _identifierController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final success = await auth.login(
      _identifierController.text.trim(),
      _passwordController.text,
    );

    if (mounted) {
      if (success) {
        final role = auth.currentUser!.role;
        if (role == 'admin') {
          context.go('/admin/settings');
        } else if (role == 'guru_walikelas' || role == 'guru') {
          context.go('/teacher/dashboard');
        } else if (role == 'petugas_piket') {
          context.go('/picket/leave');
        } else {
          context.go('/student/attendance');
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('ID/Password salah atau pengguna tidak ditemukan')),
        );
      }
    }
  }

  void _submitDemo(String role, String targetPath) async {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final success = await auth.loginDemo(role);
    if (mounted) {
      if (success) {
        context.go(targetPath);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Gagal masuk demo ($role). Pastikan server backend berjalan '
              'dan seeder database telah dijalankan.'
            ),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);

    return Scaffold(
      backgroundColor: AppColors.space950,
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                constraints: const BoxConstraints(maxWidth: 400),
                padding: const EdgeInsets.all(28.0),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: const [
                    BoxShadow(
                      color: Colors.black26,
                      blurRadius: 10,
                      offset: Offset(0, 4),
                    ),
                  ],
                ),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Absenku',
                        style: TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.bold,
                          color: AppColors.space900,
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Silakan masuk untuk mencatat kehadiran Anda.',
                        style: TextStyle(color: Colors.black54),
                      ),
                      const SizedBox(height: 24),
                      TextFormField(
                        controller: _identifierController,
                        decoration: const InputDecoration(
                          labelText: 'Nama / Email / WhatsApp / NIP',
                          prefixIcon: Icon(Icons.person_outline),
                        ),
                        validator: (value) => value == null || value.isEmpty
                            ? 'Wajib diisi'
                            : null,
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _passwordController,
                        obscureText: _obscure,
                        decoration: InputDecoration(
                          labelText: 'Password',
                          prefixIcon: const Icon(Icons.lock_outline),
                          suffixIcon: IconButton(
                            icon: Icon(_obscure
                                ? Icons.visibility_off_outlined
                                : Icons.visibility_outlined),
                            onPressed: () => setState(() => _obscure = !_obscure),
                          ),
                        ),
                        validator: (value) => value == null || value.length < 4
                            ? 'Password minimal 4 karakter'
                            : null,
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Checkbox(
                            value: _rememberMe,
                            activeColor: AppColors.electric600,
                            onChanged: (val) {
                              setState(() {
                                _rememberMe = val ?? false;
                              });
                            },
                          ),
                          const Text('Ingat Saya'),
                        ],
                      ),
                      const SizedBox(height: 16),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: auth.isLoading ? null : _submit,
                          child: auth.isLoading
                              ? const CircularProgressIndicator(color: Colors.white)
                              : const Text('Masuk'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 32),
              Container(
                constraints: const BoxConstraints(maxWidth: 400),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.space900,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  children: [
                    const Text(
                      'Pilih Akun Demo (Uji Coba)',
                      style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      alignment: WrapAlignment.center,
                      children: [
                        ElevatedButton(
                          onPressed: auth.isLoading
                              ? null
                              : () => _submitDemo('admin', '/admin/settings'),
                          style: ElevatedButton.styleFrom(backgroundColor: AppColors.space800),
                          child: const Text('Admin', style: TextStyle(fontSize: 12)),
                        ),
                        ElevatedButton(
                          onPressed: auth.isLoading
                              ? null
                              : () => _submitDemo('guru_walikelas', '/teacher/dashboard'),
                          style: ElevatedButton.styleFrom(backgroundColor: AppColors.space800),
                          child: const Text('Guru', style: TextStyle(fontSize: 12)),
                        ),
                        ElevatedButton(
                          onPressed: auth.isLoading
                              ? null
                              : () => _submitDemo('petugas_piket', '/picket/leave'),
                          style: ElevatedButton.styleFrom(backgroundColor: AppColors.space800),
                          child: const Text('Piket', style: TextStyle(fontSize: 12)),
                        ),
                        ElevatedButton(
                          onPressed: auth.isLoading
                              ? null
                              : () => _submitDemo('siswa', '/student/attendance'),
                          style: ElevatedButton.styleFrom(backgroundColor: AppColors.space800),
                          child: const Text('Siswa', style: TextStyle(fontSize: 12)),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
