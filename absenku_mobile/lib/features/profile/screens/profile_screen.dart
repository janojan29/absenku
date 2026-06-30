import 'package:flutter/material.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';

class ProfileScreen extends StatefulWidget {
  final bool forceChangePassword;

  const ProfileScreen({super.key, this.forceChangePassword = false});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _oldPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool _isLoading = false;
  bool _obscureOld = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;

  final _phoneController = TextEditingController();
  bool _isPhoneLoading = false;

  @override
  void dispose() {
    _oldPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    final user = MockDatabase().currentUser;
    if (user != null && user.whatsappNumber != null) {
      _phoneController.text = user.whatsappNumber!;
    }
  }

  Future<void> _submitUpdatePhone() async {
    final phone = _phoneController.text.trim();
    if (phone.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nomor WhatsApp wajib diisi.')));
      return;
    }
    if (!RegExp(r'^08[0-9]+$').hasMatch(phone)) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nomor WhatsApp harus diawali dengan 08 dan hanya berisi angka.')));
      return;
    }
    if (phone.length > 30) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nomor WhatsApp maksimal 30 karakter.')));
      return;
    }
    setState(() => _isPhoneLoading = true);
    try {
      await MockDatabase().updatePhone(phone);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nomor HP berhasil diperbarui.')));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceAll('Exception:', ''))));
      }
    } finally {
      if (mounted) {
        setState(() => _isPhoneLoading = false);
      }
    }
  }

  Future<void> _submitChangePassword() async {
    if (!_formKey.currentState!.validate()) return;

    if (_newPasswordController.text != _confirmPasswordController.text) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Konfirmasi password baru tidak cocok!')));
      return;
    }

    setState(() => _isLoading = true);
    try {
      await MockDatabase().changePassword(
        _oldPasswordController.text,
        _newPasswordController.text,
        _confirmPasswordController.text,
        whatsappNumber: widget.forceChangePassword ? _phoneController.text.trim() : null,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password berhasil diubah!')));
        if (widget.forceChangePassword) {
          // the app.dart wrapper will automatically navigate away since mustChangePassword is false now
        } else {
          Navigator.pop(context);
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceAll('Exception:', ''))));
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final db = MockDatabase();
    final user = db.currentUser;

    if (user == null) return const Scaffold(body: Center(child: Text('Not logged in')));

    return PopScope(
      canPop: !widget.forceChangePassword,
      child: Scaffold(
        appBar: AppBar(
          title: Text(widget.forceChangePassword ? 'Wajib Ganti Password' : 'Profil Pengguna'),
          automaticallyImplyLeading: !widget.forceChangePassword,
          elevation: 0,
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (widget.forceChangePassword) ...[
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.amber[50],
                    border: Border.all(color: Colors.amber[200]!),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.warning_amber_rounded, color: Colors.amber[800]),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'Demi keamanan, Anda wajib mengganti password default sebelum dapat menggunakan aplikasi.',
                          style: TextStyle(color: Colors.amber[900], fontSize: 13),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
              ],
              
              // Profile Info Card
              if (!widget.forceChangePassword)
                Card(
                  elevation: 2,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 40,
                          backgroundColor: AppTheme.primaryNavy.withValues(alpha: 0.1),
                          child: Text(
                            user.name.isNotEmpty ? user.name[0].toUpperCase() : '?',
                            style: const TextStyle(fontSize: 32, fontWeight: FontWeight.bold, color: AppTheme.primaryNavy),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(user.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 4),
                        Text(user.role.toUpperCase(), style: const TextStyle(color: AppTheme.primaryBlue, fontWeight: FontWeight.w600)),
                        const Divider(height: 32),
                        _buildInfoRow('Email', user.email),
                        if (user.nis != null) _buildInfoRow('NISN', user.nis!),
                        if (user.nip != null) _buildInfoRow('NIP', user.nip!),
                        if (user.classRoomName != null) _buildInfoRow('Kelas', user.classRoomName!),
                        if (user.whatsappNumber != null && user.whatsappNumber!.isNotEmpty) _buildInfoRow('No HP', user.whatsappNumber!),
                      ],
                    ),
                  ),
                ),
              
              const SizedBox(height: 24),
              
              if (!widget.forceChangePassword && (user.role == 'siswa' || user.role == 'guru' || user.role == 'guru_walikelas')) ...[
                Card(
                  elevation: 2,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          'Ubah Nomor WhatsApp',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                controller: _phoneController,
                                keyboardType: TextInputType.phone,
                                decoration: const InputDecoration(
                                  labelText: 'No WhatsApp Baru',
                                  prefixIcon: Icon(Icons.phone),
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            ElevatedButton(
                              onPressed: _isPhoneLoading ? null : _submitUpdatePhone,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.accentBlue,
                                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              child: _isPhoneLoading
                                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Text('SIMPAN'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Pastikan diawali dengan 08 dan hanya angka.',
                          style: TextStyle(fontSize: 12, color: AppTheme.textMuted),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 24),
              ],
              
              // Password Change Form
              if (user.role == 'admin' || user.role == 'petugas_piket')
                Card(
                  elevation: 2,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Center(
                      child: Text(
                        user.role == 'admin'
                            ? 'Password untuk akun Administrator hanya dapat diubah melalui database seeder atau hubungi operator.'
                            : 'Password untuk akun Petugas Piket hanya dapat diubah oleh Admin.',
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: AppTheme.textMuted),
                      ),
                    ),
                  ),
                )
              else
                Card(
                  elevation: 2,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          'Ganti Password',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 16),
                        if (widget.forceChangePassword) ...[
                          TextFormField(
                            controller: _phoneController,
                            keyboardType: TextInputType.phone,
                            decoration: const InputDecoration(
                              labelText: 'Nomor WhatsApp',
                              prefixIcon: Icon(Icons.phone),
                              helperText: 'Pastikan diawali dengan 08/angka.',
                            ),
                            validator: (v) {
                              final isPhoneEmpty = v == null || v.trim().isEmpty;
                              final alreadyHasPhone = user.whatsappNumber != null && user.whatsappNumber!.isNotEmpty;

                              if (!alreadyHasPhone && isPhoneEmpty) {
                                return 'Nomor WhatsApp wajib diisi';
                              }

                              if (!isPhoneEmpty) {
                                final phoneVal = v.trim();
                                if (!RegExp(r'^08[0-9]+$').hasMatch(phoneVal)) {
                                  return 'Nomor WhatsApp harus diawali dengan 08 dan hanya berisi angka';
                                }
                                if (phoneVal.length > 30) {
                                  return 'Nomor WhatsApp maksimal 30 karakter';
                                }
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),
                        ],
                        TextFormField(
                          controller: _oldPasswordController,
                          obscureText: _obscureOld,
                          decoration: InputDecoration(
                            labelText: 'Password Lama',
                            prefixIcon: const Icon(Icons.lock_outline),
                            suffixIcon: IconButton(
                              icon: Icon(_obscureOld ? Icons.visibility_off : Icons.visibility),
                              onPressed: () => setState(() => _obscureOld = !_obscureOld),
                            ),
                          ),
                          validator: (v) => v!.isEmpty ? 'Wajib diisi' : null,
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _newPasswordController,
                          obscureText: _obscureNew,
                          decoration: InputDecoration(
                            labelText: 'Password Baru',
                            prefixIcon: const Icon(Icons.lock_reset),
                            suffixIcon: IconButton(
                              icon: Icon(_obscureNew ? Icons.visibility_off : Icons.visibility),
                              onPressed: () => setState(() => _obscureNew = !_obscureNew),
                            ),
                          ),
                          validator: (v) {
                            if (v!.isEmpty) return 'Wajib diisi';
                            if (v.length < 8) return 'Minimal 8 karakter';
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _confirmPasswordController,
                          obscureText: _obscureConfirm,
                          decoration: InputDecoration(
                            labelText: 'Konfirmasi Password Baru',
                            prefixIcon: const Icon(Icons.lock_reset),
                            suffixIcon: IconButton(
                              icon: Icon(_obscureConfirm ? Icons.visibility_off : Icons.visibility),
                              onPressed: () => setState(() => _obscureConfirm = !_obscureConfirm),
                            ),
                          ),
                          validator: (v) {
                            if (v!.isEmpty) return 'Wajib diisi';
                            if (v != _newPasswordController.text) {
                              return 'Konfirmasi password tidak cocok';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 24),
                        ElevatedButton(
                          onPressed: _isLoading ? null : _submitChangePassword,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.accentBlue,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          child: _isLoading
                              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : const Text('SIMPAN PASSWORD BARU', style: TextStyle(fontWeight: FontWeight.bold)),
                        ),
                        if (widget.forceChangePassword) ...[
                          const SizedBox(height: 12),
                          TextButton(
                            onPressed: () {
                              db.logout();
                            },
                            child: const Text('Logout', style: TextStyle(color: Colors.red)),
                          ),
                        ]
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(label, style: const TextStyle(color: AppTheme.textMuted)),
          ),
          Expanded(
            child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600, color: AppTheme.textDark)),
          ),
        ],
      ),
    );
  }
}
