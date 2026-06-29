import 'package:flutter/material.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../models/user.dart';
import 'package:file_picker/file_picker.dart';

class StudentManagementScreen extends StatefulWidget {
  const StudentManagementScreen({super.key});

  @override
  State<StudentManagementScreen> createState() => _StudentManagementScreenState();
}

class _StudentManagementScreenState extends State<StudentManagementScreen> {
  String _selectedClassRoomId = '';
  String _searchQuery = '';
  final _searchController = TextEditingController();
  Set<String> _selectedStudentIds = {};
  bool _loading = true;

  // Form controllers
  final _nameController = TextEditingController();
  final _nisController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordConfirmController = TextEditingController();
  final _whatsappController = TextEditingController();
  final _parentWhatsappController = TextEditingController();
  String _formClassRoomId = '';

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    final db = MockDatabase();
    await Future.wait([db.fetchStudents(), db.fetchClassrooms()]);
    if (mounted) setState(() => _loading = false);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _nameController.dispose();
    _nisController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    _whatsappController.dispose();
    _parentWhatsappController.dispose();
    super.dispose();
  }

  void _showAddEditDialog(MockDatabase db, [User? student]) {
    final isEdit = student != null;
    if (isEdit) {
      _nameController.text = student.name;
      _nisController.text = student.nis ?? '';
      _whatsappController.text = student.whatsappNumber ?? '';
      _parentWhatsappController.text = student.parentPhoneWa ?? '';
      _formClassRoomId = student.classRoomId ?? '';
      _passwordController.clear();
      _passwordConfirmController.clear();
    } else {
      _nameController.clear();
      _nisController.clear();
      _whatsappController.clear();
      _parentWhatsappController.clear();
      _formClassRoomId = db.classrooms.isNotEmpty ? db.classrooms.first.id : '';
      _passwordController.clear();
      _passwordConfirmController.clear();
    }

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(isEdit ? 'Edit Siswa' : 'Tambah Siswa Baru'),
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
                        controller: _nisController,
                        decoration: const InputDecoration(labelText: 'NISN'),
                        keyboardType: TextInputType.number,
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                        initialValue: _formClassRoomId.isEmpty ? null : _formClassRoomId,
                        decoration: const InputDecoration(labelText: 'Kelas'),
                        items: db.classrooms.map((c) {
                          return DropdownMenuItem(value: c.id, child: Text(c.name));
                        }).toList(),
                        onChanged: (val) {
                          setDialogState(() {
                            _formClassRoomId = val ?? '';
                          });
                        },
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _whatsappController,
                        decoration: const InputDecoration(labelText: 'No. WhatsApp Siswa (08...)'),
                        keyboardType: TextInputType.phone,
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _parentWhatsappController,
                        decoration: const InputDecoration(labelText: 'No. WhatsApp Orang Tua (08...)'),
                        keyboardType: TextInputType.phone,
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _passwordController,
                        decoration: InputDecoration(labelText: 'Password', hintText: isEdit ? '(Kosongkan jika tidak ingin mengubah)' : null),
                        obscureText: true,
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _passwordConfirmController,
                        decoration: InputDecoration(labelText: 'Konfirmasi Password', hintText: isEdit ? '(Kosongkan jika tidak ingin mengubah)' : null),
                        obscureText: true,
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
                        _nisController.text.trim().isEmpty ||
                        _formClassRoomId.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Nama, NISN, dan Kelas wajib diisi!')),
                      );
                      return;
                    }

                    if (!isEdit) {
                      if (_passwordController.text.isEmpty || _passwordConfirmController.text.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Password wajib diisi!')),
                        );
                        return;
                      }
                      if (_passwordController.text != _passwordConfirmController.text) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Password dan konfirmasi password tidak cocok!')),
                        );
                        return;
                      }
                    }

                    try {
                      if (isEdit) {
                        await db.updateStudent(
                          id: student.id,
                          name: _nameController.text,
                          nis: _nisController.text,
                          classRoomId: _formClassRoomId,
                          password: _passwordController.text.isNotEmpty ? _passwordController.text : null,
                          passwordConfirmation: _passwordConfirmController.text.isNotEmpty ? _passwordConfirmController.text : null,
                          whatsappNumber: _whatsappController.text.isNotEmpty ? _whatsappController.text : null,
                          parentPhoneWa: _parentWhatsappController.text.isNotEmpty ? _parentWhatsappController.text : null,
                        );
                      } else {
                        await db.addStudent(
                          name: _nameController.text,
                          nis: _nisController.text,
                          classRoomId: _formClassRoomId,
                          password: _passwordController.text,
                          passwordConfirmation: _passwordConfirmController.text,
                          whatsappNumber: _whatsappController.text.isNotEmpty ? _whatsappController.text : null,
                          parentPhoneWa: _parentWhatsappController.text.isNotEmpty ? _parentWhatsappController.text : null,
                        );
                      }

                      if (context.mounted) {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(isEdit ? 'Data siswa diperbarui!' : 'Siswa baru ditambahkan!'),
                          ),
                        );
                      }
                    } catch (e) {
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text(e.toString().replaceAll('Exception:', ''))),
                        );
                      }
                    }
                  },
                  child: const Text('SIMPAN'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _showBulkClassDialog(MockDatabase db) {
    if (_selectedStudentIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pilih minimal 1 siswa terlebih dahulu.')),
      );
      return;
    }

    String bulkTargetClassId = db.classrooms.isNotEmpty ? db.classrooms.first.id : '';

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Ubah Kelas Massal'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Pindahkan ${_selectedStudentIds.length} siswa terpilih ke kelas:',
                    style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: bulkTargetClassId.isEmpty ? null : bulkTargetClassId,
                    items: db.classrooms.map((c) {
                      return DropdownMenuItem(value: c.id, child: Text(c.name));
                    }).toList(),
                    onChanged: (val) {
                      setDialogState(() {
                        bulkTargetClassId = val ?? '';
                      });
                    },
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
                ),
                ElevatedButton(
                  onPressed: () async {
                    if (bulkTargetClassId.isEmpty) return;
                    await db.bulkUpdateClass(_selectedStudentIds.toList(), bulkTargetClassId);
                    setState(() {
                      _selectedStudentIds.clear();
                    });
                    if (context.mounted) {
                      Navigator.pop(context);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Berhasil memindahkan siswa terpilih!')),
                      );
                    }
                  },
                  child: const Text('TERAPKAN'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _showBulkDeleteDialog(MockDatabase db) {
    String bulkDeleteClassId = db.classrooms.isNotEmpty ? db.classrooms.first.id : '';

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Hapus Massal Kelas'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'PERINGATAN: Tindakan ini akan menghapus SELURUH siswa dan riwayat absensi di kelas terpilih.',
                    style: TextStyle(fontSize: 12, color: AppTheme.statusAbsent, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: bulkDeleteClassId.isEmpty ? null : bulkDeleteClassId,
                    items: db.classrooms.map((c) {
                      return DropdownMenuItem(value: c.id, child: Text(c.name));
                    }).toList(),
                    onChanged: (val) {
                      setDialogState(() {
                        bulkDeleteClassId = val ?? '';
                      });
                    },
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
                ),
                ElevatedButton(
                  onPressed: () async {
                    if (bulkDeleteClassId.isEmpty) return;
                    await db.bulkDeleteByClass(bulkDeleteClassId);
                    if (context.mounted) {
                      Navigator.pop(context);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Berhasil menghapus seluruh siswa kelas tersebut!')),
                      );
                    }
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: AppTheme.statusAbsent),
                  child: const Text('HAPUS SEMUA'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        if (_loading) return const Center(child: CircularProgressIndicator());

        final db = MockDatabase();
        final students = db.users.where((u) => u.role == 'siswa').toList();

        // Apply filters
        final filteredStudents = students.where((student) {
          final matchesClass = _selectedClassRoomId.isEmpty || student.classRoomId == _selectedClassRoomId;
          final matchesSearch = student.name.toLowerCase().contains(_searchQuery.toLowerCase()) ||
              (student.nis != null && student.nis!.contains(_searchQuery));
          return matchesClass && matchesSearch;
        }).toList();

        return Scaffold(
          backgroundColor: AppTheme.backgroundLight,
          body: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Filter Panel Card
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
                              hintText: 'Cari nama/NIS...',
                              prefixIcon: Icon(Icons.search),
                              contentPadding: EdgeInsets.zero,
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        SizedBox(
                          width: 120,
                          child: DropdownButtonFormField<String>(
                            initialValue: _selectedClassRoomId,
                            items: [
                              const DropdownMenuItem(value: '', child: Text('Semua')),
                              ...db.classrooms.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name))),
                            ],
                            onChanged: (val) => setState(() => _selectedClassRoomId = val ?? ''),
                            decoration: const InputDecoration(
                              contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),

                // Action Buttons Row (Add, Bulk Change, Bulk Delete, Import)
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      ElevatedButton.icon(
                        onPressed: () => _showAddEditDialog(db),
                        icon: const Icon(Icons.add, size: 16),
                        label: const Text('Tambah', style: TextStyle(fontSize: 12)),
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        ),
                      ),
                      const SizedBox(width: 8),
                      OutlinedButton.icon(
                        onPressed: () => _showBulkClassDialog(db),
                        icon: const Icon(Icons.drive_file_rename_outline, size: 16),
                        label: Text(
                          'Pindah Kelas (${_selectedStudentIds.length})',
                          style: const TextStyle(fontSize: 12),
                        ),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        ),
                      ),
                      const SizedBox(width: 8),
                      OutlinedButton.icon(
                        onPressed: () => _showBulkDeleteDialog(db),
                        icon: const Icon(Icons.delete_sweep, size: 16, color: AppTheme.statusAbsent),
                        label: const Text('Hapus Kelas', style: TextStyle(fontSize: 12, color: AppTheme.statusAbsent)),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          side: const BorderSide(color: AppTheme.statusAbsent),
                        ),
                      ),

                    ],
                  ),
                ),
                const SizedBox(height: 12),
                
                // Select All and Export/Import
                Row(
                  children: [
                    Checkbox(
                      value: filteredStudents.isNotEmpty && _selectedStudentIds.length == filteredStudents.length,
                      onChanged: (val) {
                        setState(() {
                          if (val == true) {
                            _selectedStudentIds = filteredStudents.map((s) => s.id).toSet();
                          } else {
                            _selectedStudentIds.clear();
                          }
                        });
                      },
                    ),
                    const Text('Pilih Semua', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                    const Spacer(),
                    TextButton.icon(
                      onPressed: () async {
                        try {
                          final result = await FilePicker.platform.pickFiles(
                            type: FileType.custom,
                            allowedExtensions: ['xlsx', 'xls', 'csv'],
                            withData: true,
                          );
                          
                          if (result != null && result.files.single.bytes != null) {
                            if (!context.mounted) return;
                            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Sedang mengimport data...')));
                            
                            final db = MockDatabase();
                            final message = await db.importStudents(result.files.single.bytes!, result.files.single.name);
                            
                            if (!context.mounted) return;
                            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
                            
                            // Reload data
                            await _loadData();
                          }
                        } catch (e) {
                          if (!context.mounted) return;
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceAll('Exception:', ''))));
                        }
                      },
                      icon: const Icon(Icons.upload_file, size: 16),
                      label: const Text('Import', style: TextStyle(fontSize: 12)),
                    ),
                  ],
                ),
                const SizedBox(height: 4),

                // Student List
                Expanded(
                  child: filteredStudents.isEmpty
                      ? const Center(
                          child: Text(
                            'Siswa tidak ditemukan.',
                            style: TextStyle(color: AppTheme.textMuted),
                          ),
                        )
                      : ListView.builder(
                          itemCount: filteredStudents.length,
                          itemBuilder: (context, index) {
                            final student = filteredStudents[index];
                            final classRoom = db.classrooms.firstWhere(
                              (c) => c.id == student.classRoomId,
                              orElse: () => ClassRoom(id: '', name: '-', jurusan: '-'),
                            );

                            final isChecked = _selectedStudentIds.contains(student.id);

                            return Card(
                              margin: const EdgeInsets.only(bottom: 10),
                              child: ListTile(
                                leading: Checkbox(
                                  value: isChecked,
                                  onChanged: (val) {
                                    setState(() {
                                      if (val == true) {
                                        _selectedStudentIds.add(student.id);
                                      } else {
                                        _selectedStudentIds.remove(student.id);
                                      }
                                    });
                                  },
                                ),
                                title: Text(student.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                subtitle: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'NISN: ${student.nis ?? "-"} · Kelas: ${classRoom.name} (${student.jurusan ?? classRoom.jurusan}) · ${student.email}',
                                      style: const TextStyle(fontSize: 11),
                                    ),
                                    if (student.whatsappNumber != null && student.whatsappNumber!.isNotEmpty)
                                      Text(
                                        'WA Siswa: ${student.whatsappNumber} · WA Ortu: ${student.parentPhoneWa ?? "-"}',
                                        style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
                                      ),
                                  ],
                                ),
                                trailing: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.edit_outlined, color: AppTheme.accentBlue, size: 20),
                                      onPressed: () => _showAddEditDialog(db, student),
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
                                              title: const Text('Hapus Siswa'),
                                              content: Text('Apakah Anda yakin ingin menghapus data ${student.name}?'),
                                              actions: [
                                                TextButton(
                                                  onPressed: () => Navigator.pop(context),
                                                  child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
                                                ),
                                                ElevatedButton(
                                                  onPressed: () async {
                                                    await db.deleteStudent(student.id);
                                                    if (context.mounted) {
                                                      Navigator.pop(context);
                                                      ScaffoldMessenger.of(context).showSnackBar(
                                                        const SnackBar(content: Text('Siswa terhapus!')),
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
