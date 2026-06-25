// lib/screens/admin/students_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/app_colors.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/confirm_dialog.dart';
import '../../services/api_service.dart';

class StudentsScreen extends StatefulWidget {
  const StudentsScreen({super.key});

  @override
  State<StudentsScreen> createState() => _StudentsScreenState();
}

class _StudentsScreenState extends State<StudentsScreen> {
  List<dynamic> _students = [];
  List<dynamic> _classrooms = [];
  String _searchQuery = '';
  String _selectedClass = 'Semua';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final studentsList = await ApiService.getAdminStudents();
      final classroomsList = await ApiService.getAdminClassrooms();
      setState(() {
        _students = studentsList;
        _classrooms = classroomsList;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal memuat data siswa: $e')),
        );
      }
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _deleteStudent(dynamic student) async {
    final name = student['name'] ?? '-';
    final id = student['id'] as int;

    final confirm = await showConfirmDialog(
      context,
      title: 'Hapus Siswa',
      message: 'Apakah Anda yakin ingin menghapus siswa $name?',
    );

    if (confirm == true) {
      setState(() {
        _isLoading = true;
      });
      try {
        await ApiService.deleteAdminUser(id);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Siswa $name berhasil dihapus.')),
          );
        }
        _loadData();
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
    final filtered = _students.where((s) {
      final name = (s['name'] ?? '').toString().toLowerCase();
      final profile = s['student_profile'] as Map<String, dynamic>?;
      final nisn = (profile?['nis'] ?? '').toString();
      final classRoomName = (profile?['class_room']?['name'] ?? '').toString();
      
      final matchesSearch = name.contains(_searchQuery.toLowerCase()) || nisn.contains(_searchQuery);
      final matchesClass = _selectedClass == 'Semua' || classRoomName == _selectedClass;
      return matchesSearch && matchesClass;
    }).toList();

    final classNames = ['Semua', ..._classrooms.map((c) => (c['name'] ?? '').toString())];

    return AppScaffold(
      title: 'Kelola Siswa',
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: TextField(
                    decoration: const InputDecoration(
                      hintText: 'Cari siswa (Nama/NISN)...',
                      prefixIcon: Icon(Icons.search),
                    ),
                    onChanged: (val) => setState(() => _searchQuery = val),
                  ),
                ),
                const SizedBox(width: 12),
                if (classNames.length > 1)
                  DropdownButton<String>(
                    value: _selectedClass,
                    items: classNames.map((name) {
                      return DropdownMenuItem<String>(
                        value: name,
                        child: Text(name),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) {
                        setState(() {
                          _selectedClass = val;
                        });
                      }
                    },
                  ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                ElevatedButton.icon(
                  onPressed: () async {
                    final result = await context.push('/admin/student/form');
                    if (result == true) {
                      _loadData();
                    }
                  },
                  icon: const Icon(Icons.add),
                  label: const Text('Tambah Siswa'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Expanded(
              child: _isLoading && _students.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : filtered.isEmpty
                      ? const Center(child: Text('Siswa tidak ditemukan'))
                      : RefreshIndicator(
                          onRefresh: _loadData,
                          child: ListView.builder(
                            itemCount: filtered.length,
                            itemBuilder: (context, index) {
                              final student = filtered[index];
                              final name = student['name'] ?? '-';
                              final profile = student['student_profile'] as Map<String, dynamic>?;
                              final nisn = profile?['nis'] ?? '-';
                              final classRoomName = profile?['class_room']?['name'] ?? '-';

                              return Card(
                                margin: const EdgeInsets.only(bottom: 12),
                                child: ListTile(
                                  title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                  subtitle: Text('NISN: $nisn\nKelas: $classRoomName'),
                                  trailing: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      IconButton(
                                        icon: const Icon(Icons.edit, color: AppColors.electric600),
                                        onPressed: () async {
                                          final result = await context.push('/admin/student/form', extra: student);
                                          if (result == true) {
                                            _loadData();
                                          }
                                        },
                                      ),
                                      IconButton(
                                        icon: const Icon(Icons.delete, color: AppColors.absent),
                                        onPressed: () => _deleteStudent(student),
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
