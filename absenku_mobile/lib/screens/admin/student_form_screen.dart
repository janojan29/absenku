// lib/screens/admin/student_form_screen.dart
import 'package:flutter/material.dart';
import '../../widgets/app_scaffold.dart';
import '../../services/api_service.dart';

class StudentFormScreen extends StatefulWidget {
  final dynamic student;

  const StudentFormScreen({super.key, this.student});

  @override
  State<StudentFormScreen> createState() => _StudentFormScreenState();
}

class _StudentFormScreenState extends State<StudentFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _nisnController;
  late TextEditingController _whatsappController;
  late TextEditingController _parentWaController;
  late TextEditingController _passwordController;
  late TextEditingController _passwordConfirmController;
  
  int? _selectedClassRoomId;
  List<dynamic> _classrooms = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    final s = widget.student;
    _nameController = TextEditingController(text: s?['name']?.toString());
    
    final profile = s?['student_profile'] as Map<String, dynamic>?;
    _nisnController = TextEditingController(text: profile?['nis']?.toString());
    _whatsappController = TextEditingController(text: s?['whatsapp_number']?.toString());
    _parentWaController = TextEditingController(text: profile?['parent_phone_wa']?.toString());
    _passwordController = TextEditingController();
    _passwordConfirmController = TextEditingController();
    
    _selectedClassRoomId = profile?['class_room']?['id'] as int?;
    _loadClassrooms();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _nisnController.dispose();
    _whatsappController.dispose();
    _parentWaController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    super.dispose();
  }

  Future<void> _loadClassrooms() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final list = await ApiService.getAdminClassrooms();
      setState(() {
        _classrooms = list;
        if (_selectedClassRoomId == null && _classrooms.isNotEmpty) {
          _selectedClassRoomId = _classrooms[0]['id'] as int;
        }
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal memuat kelas: $e')),
        );
      }
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isLoading = true;
    });
    try {
      final selectedClass = _classrooms.firstWhere((c) => c['id'] == _selectedClassRoomId);
      final major = selectedClass['jurusan'] ?? '';

      if (widget.student == null) {
        // Create student
        await ApiService.createAdminStudent({
          'name': _nameController.text,
          'password': _passwordController.text,
          'password_confirmation': _passwordConfirmController.text,
          'jurusan': major,
          'class_room_id': _selectedClassRoomId,
          'nis': _nisnController.text,
          'parent_phone_wa': _parentWaController.text,
          'whatsapp_number': _whatsappController.text,
        });
      } else {
        // Update student
        final id = widget.student['id'] as int;
        await ApiService.updateAdminStudent(id, {
          'jurusan': major,
          'class_room_id': _selectedClassRoomId,
          'nis': _nisnController.text,
          'parent_phone_wa': _parentWaController.text,
        });
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Siswa ${widget.student == null ? 'ditambahkan' : 'diperbarui'} berhasil!')),
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
    final isEdit = widget.student != null;

    return AppScaffold(
      title: isEdit ? 'Edit Siswa' : 'Tambah Siswa',
      child: _isLoading && _classrooms.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (!isEdit) ...[
                          TextFormField(
                            controller: _nameController,
                            decoration: const InputDecoration(labelText: 'Nama Lengkap'),
                            validator: (v) => v == null || v.isEmpty ? 'Nama wajib diisi' : null,
                          ),
                          const SizedBox(height: 16),
                        ],
                        TextFormField(
                          controller: _nisnController,
                          decoration: const InputDecoration(labelText: 'NISN'),
                          keyboardType: TextInputType.number,
                          validator: (v) => v == null || v.isEmpty ? 'NISN wajib diisi' : null,
                        ),
                        const SizedBox(height: 16),
                        if (!isEdit) ...[
                          TextFormField(
                            controller: _whatsappController,
                            decoration: const InputDecoration(labelText: 'Nomor WhatsApp'),
                            keyboardType: TextInputType.phone,
                          ),
                          const SizedBox(height: 16),
                        ],
                        TextFormField(
                          controller: _parentWaController,
                          decoration: const InputDecoration(labelText: 'Nomor WA Orang Tua'),
                          keyboardType: TextInputType.phone,
                        ),
                        const SizedBox(height: 16),
                        if (_classrooms.isNotEmpty)
                          DropdownButtonFormField<int>(
                            initialValue: _selectedClassRoomId,
                            decoration: const InputDecoration(labelText: 'Kelas'),
                            items: _classrooms.map<DropdownMenuItem<int>>((c) {
                              return DropdownMenuItem<int>(
                                value: c['id'] as int,
                                child: Text(c['name'] as String),
                              );
                            }).toList(),
                            onChanged: (val) {
                              if (val != null) {
                                setState(() {
                                  _selectedClassRoomId = val;
                                });
                              }
                            },
                          ),
                        const SizedBox(height: 16),
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
                            child: _isLoading ? const CircularProgressIndicator(color: Colors.white) : const Text('Simpan Data Siswa'),
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
