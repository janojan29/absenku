import 'package:flutter/material.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../models/user.dart';

class TeacherManagementScreen extends StatefulWidget {
  const TeacherManagementScreen({super.key});

  @override
  State<TeacherManagementScreen> createState() => _TeacherManagementScreenState();
}

class _TeacherManagementScreenState extends State<TeacherManagementScreen> {
  String _searchQuery = '';
  final _searchController = TextEditingController();

  // Form controllers
  final _nameController = TextEditingController();
  final _nipController = TextEditingController();
  final _emailController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    _nameController.dispose();
    _nipController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  void _showAddEditDialog(MockDatabase db, [User? teacher]) {
    final isEdit = teacher != null;
    if (isEdit) {
      _nameController.text = teacher.name;
      _nipController.text = teacher.nip ?? '';
      _emailController.text = teacher.email;
    } else {
      _nameController.clear();
      _nipController.clear();
      _emailController.clear();
    }

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(isEdit ? 'Edit Guru Piket' : 'Tambah Guru Piket'),
          content: SingleChildScrollView(
            child: Form(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextField(
                    controller: _nameController,
                    decoration: const InputDecoration(labelText: 'Nama Lengkap'),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _nipController,
                    decoration: const InputDecoration(labelText: 'NIP'),
                    keyboardType: TextInputType.number,
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _emailController,
                    decoration: const InputDecoration(labelText: 'Email'),
                    keyboardType: TextInputType.emailAddress,
                  ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
            ),
            ElevatedButton(
              onPressed: () async {
                if (_nameController.text.trim().isEmpty ||
                    _nipController.text.trim().isEmpty ||
                    _emailController.text.trim().isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Semua field wajib diisi!')),
                  );
                  return;
                }

                if (isEdit) {
                  await db.updateTeacher(
                    teacher.id,
                    _nameController.text,
                    _nipController.text,
                    _emailController.text,
                  );
                } else {
                  await db.addTeacher(
                    _nameController.text,
                    _nipController.text,
                    _emailController.text,
                  );
                }

                if (context.mounted) {
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(isEdit ? 'Data guru piket diperbarui!' : 'Guru piket baru ditambahkan!'),
                    ),
                  );
                }
              },
              child: const Text('SIMPAN'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        final teachers = db.users.where((u) => u.role == 'guru_piket').toList();

        // Apply filters
        final filteredTeachers = teachers.where((teacher) {
          final matchesSearch = teacher.name.toLowerCase().contains(_searchQuery.toLowerCase()) ||
              (teacher.nip != null && teacher.nip!.contains(_searchQuery));
          return matchesSearch;
        }).toList();

        return Scaffold(
          backgroundColor: AppTheme.backgroundLight,
          body: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Filter Card
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12.0),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _searchController,
                            onChanged: (val) => setState(() => _searchQuery = val),
                            decoration: const InputDecoration(
                              hintText: 'Cari nama/NIP guru...',
                              prefixIcon: Icon(Icons.search),
                              contentPadding: EdgeInsets.zero,
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        ElevatedButton.icon(
                          onPressed: () => _showAddEditDialog(db),
                          icon: const Icon(Icons.add, size: 16),
                          label: const Text('Tambah', style: TextStyle(fontSize: 12)),
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Teachers List
                Expanded(
                  child: filteredTeachers.isEmpty
                      ? const Center(
                          child: Text(
                            'Guru piket tidak ditemukan.',
                            style: TextStyle(color: AppTheme.textMuted),
                          ),
                        )
                      : ListView.builder(
                          itemCount: filteredTeachers.length,
                          itemBuilder: (context, index) {
                            final teacher = filteredTeachers[index];

                            return Card(
                              margin: const EdgeInsets.only(bottom: 10),
                              child: ListTile(
                                leading: CircleAvatar(
                                  radius: 20,
                                  backgroundColor: AppTheme.primaryNavy.withValues(alpha: 0.08),
                                  child: Text(
                                    teacher.name.substring(0, 1).toUpperCase(),
                                    style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primaryNavy),
                                  ),
                                ),
                                title: Text(teacher.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                subtitle: Text(
                                  'NIP: ${teacher.nip ?? "-"} · ${teacher.email}',
                                  style: const TextStyle(fontSize: 11),
                                ),
                                trailing: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.edit_outlined, color: AppTheme.accentBlue, size: 20),
                                      onPressed: () => _showAddEditDialog(db, teacher),
                                      constraints: const BoxConstraints(),
                                      padding: EdgeInsets.zero,
                                    ),
                                    const SizedBox(width: 8),
                                    IconButton(
                                      icon: const Icon(Icons.delete_outline, color: AppTheme.statusAbsent, size: 20),
                                      onPressed: () {
                                        showDialog(
                                          context: context,
                                          builder: (context) {
                                            return AlertDialog(
                                              title: const Text('Hapus Guru Piket'),
                                              content: Text('Apakah Anda yakin ingin menghapus data ${teacher.name}?'),
                                              actions: [
                                                TextButton(
                                                  onPressed: () => Navigator.pop(context),
                                                  child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
                                                ),
                                                ElevatedButton(
                                                  onPressed: () async {
                                                    await db.deleteTeacher(teacher.id);
                                                    if (context.mounted) {
                                                      Navigator.pop(context);
                                                      ScaffoldMessenger.of(context).showSnackBar(
                                                        const SnackBar(content: Text('Guru piket terhapus!')),
                                                      );
                                                    }
                                                  },
                                                  style: ElevatedButton.styleFrom(backgroundColor: AppTheme.statusAbsent),
                                                  child: const Text('HAPUS'),
                                                ),
                                              ],
                                            );
                                          },
                                        );
                                      },
                                      constraints: const BoxConstraints(),
                                      padding: EdgeInsets.zero,
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
