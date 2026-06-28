import 'package:flutter/material.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../models/user.dart';

class StudentManagementScreen extends StatefulWidget {
  const StudentManagementScreen({super.key});

  @override
  State<StudentManagementScreen> createState() => _StudentManagementScreenState();
}

class _StudentManagementScreenState extends State<StudentManagementScreen> {
  String _selectedClassRoomId = '';
  String _searchQuery = '';
  final _searchController = TextEditingController();
  final List<String> _selectedStudentIds = [];
  bool _loading = true;

  // Form controllers
  final _nameController = TextEditingController();
  final _nisController = TextEditingController();
  final _emailController = TextEditingController();
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
    _emailController.dispose();
    super.dispose();
  }

  void _showAddEditDialog(MockDatabase db, [User? student]) {
    final isEdit = student != null;
    if (isEdit) {
      _nameController.text = student.name;
      _nisController.text = student.nis ?? '';
      _emailController.text = student.email;
      _formClassRoomId = student.classRoomId ?? '';
    } else {
      _nameController.clear();
      _nisController.clear();
      _emailController.clear();
      _formClassRoomId = db.classrooms.isNotEmpty ? db.classrooms.first.id : '';
    }

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(isEdit ? 'Edit Siswa' : 'Tambah Siswa'),
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
                        decoration: const InputDecoration(labelText: 'NIS'),
                        keyboardType: TextInputType.number,
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _emailController,
                        decoration: const InputDecoration(labelText: 'Email'),
                        keyboardType: TextInputType.emailAddress,
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
                        _emailController.text.trim().isEmpty ||
                        _formClassRoomId.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Semua field wajib diisi!')),
                      );
                      return;
                    }

                    if (isEdit) {
                      await db.updateStudent(
                        student.id,
                        _nameController.text,
                        _nisController.text,
                        _formClassRoomId,
                        _emailController.text,
                      );
                    } else {
                      await db.addStudent(
                        _nameController.text,
                        _nisController.text,
                        _formClassRoomId,
                        _emailController.text,
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
                    await db.bulkUpdateClass(_selectedStudentIds, bulkTargetClassId);
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
                      const SizedBox(width: 8),
                      OutlinedButton.icon(
                        onPressed: () => _showImportExcelDialog(db),
                        icon: const Icon(Icons.upload_file, size: 16, color: Colors.green),
                        label: const Text('Import Excel', style: TextStyle(fontSize: 12, color: Colors.green)),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          side: const BorderSide(color: Colors.green),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

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
                                subtitle: Text(
                                  'NIS: ${student.nis ?? "-"} · Kelas: ${classRoom.name} · ${student.email}',
                                  style: const TextStyle(fontSize: 11),
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

  void _showImportExcelDialog(MockDatabase db) {
    String? selectedFileName;
    
    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Row(
                children: [
                  Icon(Icons.upload_file, color: Colors.green),
                  SizedBox(width: 8),
                  Text('Impor Data Siswa'),
                ],
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Text(
                      'Panduan Impor',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      '1. Pilih file template Excel/CSV yang sesuai.\n'
                      '2. Pastikan kolom nama, nisn, kelas, dan jurusan diisi.\n'
                      '3. Klik Impor Data untuk memproses data siswa.',
                      style: TextStyle(fontSize: 11, color: AppTheme.textMuted, height: 1.4),
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.05),
                        border: Border.all(color: Colors.black.withValues(alpha: 0.1)),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Format Kolom:', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                          SizedBox(height: 2),
                          Text('nama | nisn | kelas | jurusan | nohp orangtua | no hp siswa', style: TextStyle(fontSize: 9, fontFamily: 'monospace')),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text('Pilih Mock File untuk Di-impor:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      hint: const Text('Pilih file Excel...', style: TextStyle(fontSize: 12)),
                      items: const [
                        DropdownMenuItem(value: 'rpl_1', child: Text('data_siswa_XI_RPL_1.xlsx', style: TextStyle(fontSize: 12))),
                        DropdownMenuItem(value: 'tsm_1', child: Text('data_siswa_XI_TSM_1.xlsx', style: TextStyle(fontSize: 12))),
                        DropdownMenuItem(value: 'baru', child: Text('data_siswa_baru.csv', style: TextStyle(fontSize: 12))),
                      ],
                      onChanged: (val) {
                        setDialogState(() {
                          selectedFileName = val;
                        });
                      },
                      decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
                ),
                ElevatedButton(
                  onPressed: selectedFileName == null
                      ? null
                      : () async {
                          Navigator.pop(context);
                          // Show loading overlay
                          showDialog(
                            context: context,
                            barrierDismissible: false,
                            builder: (context) => const Center(
                              child: Card(
                                child: Padding(
                                  padding: EdgeInsets.all(24.0),
                                  child: Column(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(Colors.green)),
                                      SizedBox(height: 16),
                                      Text('Memproses file impor...', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          );

                          // Simulate network delay
                          await Future.delayed(const Duration(milliseconds: 1200));
                          
                          if (selectedFileName == 'rpl_1') {
                            await db.addStudent('Dina Mariana', '12353', 'c1', 'dina@gmail.com');
                            await db.addStudent('Farhan Saputra', '12354', 'c1', 'farhan@gmail.com');
                          } else if (selectedFileName == 'tsm_1') {
                            await db.addStudent('Gilang Ramadhan', '12355', 'c2', 'gilang@gmail.com');
                            await db.addStudent('Hendra Wijaya', '12356', 'c2', 'hendra@gmail.com');
                          } else if (selectedFileName == 'baru') {
                            await db.addStudent('Indah Permata', '12357', 'c3', 'indah@gmail.com');
                            await db.addStudent('Joko Susilo', '12358', 'c3', 'joko@gmail.com');
                          }

                          if (context.mounted) {
                            Navigator.pop(context); // Pop loading dialog
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                backgroundColor: Colors.green.shade700,
                                content: const Text('Data siswa berhasil di-impor dari Excel!'),
                              ),
                            );
                          }
                        },
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                  child: const Text('IMPOR DATA'),
                ),
              ],
            );
          },
        );
      },
    );
  }
}
