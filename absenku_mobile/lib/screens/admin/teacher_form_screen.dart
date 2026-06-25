// lib/screens/admin/teacher_form_screen.dart
import 'package:flutter/material.dart';
import '../../widgets/app_scaffold.dart';
import '../../services/api_service.dart';

class TeacherFormScreen extends StatefulWidget {
  final dynamic teacher;

  const TeacherFormScreen({super.key, this.teacher});

  @override
  State<TeacherFormScreen> createState() => _TeacherFormScreenState();
}

class _TeacherFormScreenState extends State<TeacherFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _nipController;
  late TextEditingController _whatsappController;
  late TextEditingController _subjectController;
  late TextEditingController _waliKelasController;
  late TextEditingController _passwordController;
  late TextEditingController _passwordConfirmController;

  String _teacherRole = 'guru';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    final t = widget.teacher;
    _nameController = TextEditingController(text: t?['name']?.toString());
    
    final details = t?['teacher'] as Map<String, dynamic>?;
    _nipController = TextEditingController(text: details?['nip']?.toString());
    _whatsappController = TextEditingController(text: t?['whatsapp_number']?.toString());
    _subjectController = TextEditingController(text: details?['subject']?.toString());
    _waliKelasController = TextEditingController(text: details?['wali_kelas']?.toString());
    
    _passwordController = TextEditingController();
    _passwordConfirmController = TextEditingController();
    
    final rolesList = t?['roles'] as List<dynamic>?;
    if (rolesList != null && rolesList.contains('guru_walikelas')) {
      _teacherRole = 'guru_walikelas';
    } else {
      _teacherRole = 'guru';
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _nipController.dispose();
    _whatsappController.dispose();
    _subjectController.dispose();
    _waliKelasController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    super.dispose();
  }

  void _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isLoading = true;
    });
    try {
      if (widget.teacher == null) {
        // Create teacher
        await ApiService.createAdminTeacher({
          'teacher_role': _teacherRole,
          'name': _nameController.text,
          'password': _passwordController.text,
          'password_confirmation': _passwordConfirmController.text,
          'nip': _nipController.text,
          'subject': _subjectController.text,
          'wali_kelas': _teacherRole == 'guru_walikelas' ? _waliKelasController.text : null,
          'whatsapp_number': _whatsappController.text,
        });
      } else {
        // Update teacher
        final id = widget.teacher['id'] as int;
        await ApiService.updateAdminTeacher(id, {
          'teacher_role': _teacherRole,
          'nip': _nipController.text,
          'subject': _subjectController.text,
          'wali_kelas': _teacherRole == 'guru_walikelas' ? _waliKelasController.text : null,
          'whatsapp_number': _whatsappController.text,
        });
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Guru ${widget.teacher == null ? 'ditambahkan' : 'diperbarui'} berhasil!')),
        );
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
        );
      }
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.teacher != null;

    return AppScaffold(
      title: isEdit ? 'Edit Guru' : 'Tambah Guru',
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Card(
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  DropdownButtonFormField<String>(
                    initialValue: _teacherRole,
                    decoration: const InputDecoration(labelText: 'Tipe Guru'),
                    items: const [
                      DropdownMenuItem(value: 'guru', child: Text('Guru Biasa')),
                      DropdownMenuItem(value: 'guru_walikelas', child: Text('Guru Wali Kelas')),
                    ],
                    onChanged: (val) {
                      if (val != null) {
                        setState(() {
                          _teacherRole = val;
                        });
                      }
                    },
                  ),
                  const SizedBox(height: 16),
                  if (!isEdit) ...[
                    TextFormField(
                      controller: _nameController,
                      decoration: const InputDecoration(labelText: 'Nama Guru (Gelar Lengkap)'),
                      validator: (v) => v == null || v.isEmpty ? 'Nama wajib diisi' : null,
                    ),
                    const SizedBox(height: 16),
                  ],
                  TextFormField(
                    controller: _nipController,
                    decoration: const InputDecoration(labelText: 'NIP'),
                    keyboardType: TextInputType.number,
                    validator: (v) => v == null || v.isEmpty ? 'NIP wajib diisi' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _whatsappController,
                    decoration: const InputDecoration(labelText: 'Nomor WhatsApp'),
                    keyboardType: TextInputType.phone,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _subjectController,
                    decoration: const InputDecoration(labelText: 'Mata Pelajaran Utama'),
                    validator: (v) => v == null || v.isEmpty ? 'Mata pelajaran wajib diisi' : null,
                  ),
                  const SizedBox(height: 16),
                  if (_teacherRole == 'guru_walikelas') ...[
                    TextFormField(
                      controller: _waliKelasController,
                      decoration: const InputDecoration(labelText: 'Nama Kelas Wali (misal: XII RPL 1)'),
                      validator: (v) => v == null || v.isEmpty ? 'Keterangan wali kelas wajib diisi' : null,
                    ),
                    const SizedBox(height: 16),
                  ],
                  if (!isEdit) ...[
                    TextFormField(
                      controller: _passwordController,
                      decoration: const InputDecoration(labelText: 'Password'),
                      obscureText: true,
                      validator: (v) => v == null || v.isEmpty ? 'Password wajib diisi' : null,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _passwordConfirmController,
                      decoration: const InputDecoration(labelText: 'Konfirmasi Password'),
                      obscureText: true,
                      validator: (v) {
                        if (v == null || v.isEmpty) return 'Konfirmasi password wajib diisi';
                        if (v != _passwordController.text) return 'Password tidak cocok';
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                  ],
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _isLoading ? null : _save,
                      child: _isLoading ? const CircularProgressIndicator(color: Colors.white) : const Text('Simpan Data Guru'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
