// lib/screens/admin/teachers_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/app_colors.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/confirm_dialog.dart';
import '../../services/api_service.dart';

class TeachersScreen extends StatefulWidget {
  const TeachersScreen({super.key});

  @override
  State<TeachersScreen> createState() => _TeachersScreenState();
}

class _TeachersScreenState extends State<TeachersScreen> {
  List<dynamic> _teachers = [];
  String _searchQuery = '';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadTeachers();
  }

  Future<void> _loadTeachers() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final list = await ApiService.getAdminTeachers();
      setState(() {
        _teachers = list;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal memuat data guru: $e')),
        );
      }
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _deleteTeacher(dynamic teacher) async {
    final name = teacher['name'] ?? '-';
    final id = teacher['id'] as int;

    final confirm = await showConfirmDialog(
      context,
      title: 'Hapus Guru',
      message: 'Apakah Anda yakin ingin menghapus guru $name?',
    );

    if (confirm == true) {
      setState(() {
        _isLoading = true;
      });
      try {
        await ApiService.deleteAdminUser(id);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Guru $name berhasil dihapus.')),
          );
        }
        _loadTeachers();
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
          );
        }
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _teachers.where((t) {
      final name = (t['name'] ?? '').toString().toLowerCase();
      final nip = (t['teacher']?['nip'] ?? '').toString();
      final query = _searchQuery.toLowerCase();
      return name.contains(query) || nip.contains(query);
    }).toList();

    return AppScaffold(
      title: 'Kelola Guru',
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            TextField(
              decoration: const InputDecoration(
                hintText: 'Cari guru (Nama/NIP)...',
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (val) => setState(() => _searchQuery = val),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                ElevatedButton.icon(
                  onPressed: () async {
                    final result = await context.push('/admin/teacher/form');
                    if (result == true) {
                      _loadTeachers();
                    }
                  },
                  icon: const Icon(Icons.add),
                  label: const Text('Tambah Guru'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Expanded(
              child: _isLoading && _teachers.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : filtered.isEmpty
                      ? const Center(child: Text('Guru tidak ditemukan'))
                      : RefreshIndicator(
                          onRefresh: _loadTeachers,
                          child: ListView.builder(
                            itemCount: filtered.length,
                            itemBuilder: (context, index) {
                              final teacher = filtered[index];
                              final name = teacher['name'] ?? '-';
                              final nip = teacher['teacher']?['nip'] ?? '-';
                              final subject = teacher['teacher']?['subject'] ?? '-';
                              return Card(
                                margin: const EdgeInsets.only(bottom: 12),
                                child: ListTile(
                                  title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                  subtitle: Text('NIP: $nip\nMata Pelajaran: $subject'),
                                  trailing: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      IconButton(
                                        icon: const Icon(Icons.edit, color: AppColors.electric600),
                                        onPressed: () async {
                                          final result = await context.push('/admin/teacher/form', extra: teacher);
                                          if (result == true) {
                                            _loadTeachers();
                                          }
                                        },
                                      ),
                                      IconButton(
                                        icon: const Icon(Icons.delete, color: AppColors.absent),
                                        onPressed: () => _deleteTeacher(teacher),
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }
}
