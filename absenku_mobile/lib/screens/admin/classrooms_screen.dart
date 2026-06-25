// lib/screens/admin/classrooms_screen.dart
import 'package:flutter/material.dart';
import '../../core/app_colors.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/confirm_dialog.dart';
import '../../services/api_service.dart';

class ClassroomsScreen extends StatefulWidget {
  const ClassroomsScreen({super.key});

  @override
  State<ClassroomsScreen> createState() => _ClassroomsScreenState();
}

class _ClassroomsScreenState extends State<ClassroomsScreen> {
  List<dynamic> _classrooms = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadClassrooms();
  }

  Future<void> _loadClassrooms() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final list = await ApiService.getAdminClassrooms();
      setState(() {
        _classrooms = list;
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

  void _deleteClass(dynamic classroom) async {
    final name = classroom['name'] ?? '-';
    final id = classroom['id'] as int;
    final messenger = ScaffoldMessenger.of(context);

    final confirm = await showConfirmDialog(
      context,
      title: 'Hapus Kelas',
      message: 'Apakah Anda yakin ingin menghapus kelas $name?',
    );

    if (confirm == true) {
      setState(() {
        _isLoading = true;
      });
      try {
        await ApiService.deleteAdminClassroom(id);
        messenger.showSnackBar(
          SnackBar(content: Text('Kelas $name berhasil dihapus.')),
        );
        _loadClassrooms();
      } catch (e) {
        messenger.showSnackBar(
          SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
        );
        if (mounted) {
          setState(() {
            _isLoading = false;
          });
        }
      }
    }
  }

  void _showFormDialog() {
    final nameController = TextEditingController();
    final majorController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Tambah Kelas'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameController,
                decoration: const InputDecoration(labelText: 'Nama Kelas (misal: XII RPL 1)'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: majorController,
                decoration: const InputDecoration(labelText: 'Jurusan (misal: Rekayasa Perangkat Lunak)'),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Batal'),
            ),
            ElevatedButton(
              onPressed: () async {
                if (nameController.text.isEmpty || majorController.text.isEmpty) return;
                final messenger = ScaffoldMessenger.of(context);
                Navigator.pop(context);
                setState(() {
                  _isLoading = true;
                });
                try {
                  await ApiService.createAdminClassroom(nameController.text, majorController.text);
                  messenger.showSnackBar(
                    const SnackBar(content: Text('Kelas berhasil ditambahkan.')),
                  );
                  _loadClassrooms();
                } catch (e) {
                  messenger.showSnackBar(
                    SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
                  );
                  if (mounted) {
                    setState(() {
                      _isLoading = false;
                    });
                  }
                }
              },
              child: const Text('Simpan'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Kelola Kelas',
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Daftar Kelas', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                ElevatedButton.icon(
                  onPressed: _showFormDialog,
                  icon: const Icon(Icons.add),
                  label: const Text('Tambah'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Expanded(
              child: _isLoading && _classrooms.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : _classrooms.isEmpty
                      ? const Center(child: Text('Kelas belum terdaftar.'))
                      : RefreshIndicator(
                          onRefresh: _loadClassrooms,
                          child: ListView.builder(
                            itemCount: _classrooms.length,
                            itemBuilder: (context, index) {
                              final classroom = _classrooms[index];
                              final name = classroom['name'] ?? '-';
                              final major = classroom['jurusan'] ?? '-';
                              return Card(
                                margin: const EdgeInsets.only(bottom: 12),
                                child: ListTile(
                                  title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                  subtitle: Text(major),
                                  trailing: IconButton(
                                    icon: const Icon(Icons.delete, color: AppColors.absent),
                                    onPressed: () => _deleteClass(classroom),
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
