// ignore_for_file: deprecated_member_use
import 'package:flutter/material.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../models/user.dart';
import '../../../services/api_client.dart';
import 'package:file_picker/file_picker.dart';
import '../../../core/widgets/custom_expand_menu.dart';
import '../../../core/utils/download_file.dart';

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
  int _currentPage = 1;
  static const int _itemsPerPage = 20;

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
    _searchController.addListener(() {
      setState(() {
        _currentPage = 1;
      });
    });
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
                      CustomExpandMenu(
                        title: 'Pilih Kelas',
                        subtitle: _formClassRoomId.isEmpty
                            ? 'Belum ada kelas dipilih'
                            : db.classrooms.firstWhere((c) => c.id == _formClassRoomId, orElse: () => db.classrooms.first).name,
                        items: db.classrooms.map((c) => {'value': c.id, 'label': c.name}).toList(),
                        selectedValue: _formClassRoomId,
                        onChanged: (val) {
                          setDialogState(() => _formClassRoomId = val);
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
                  CustomExpandMenu(
                    title: 'Pilih Kelas Tujuan',
                    subtitle: bulkTargetClassId.isEmpty
                        ? 'Belum ada kelas dipilih'
                        : db.classrooms.firstWhere((c) => c.id == bulkTargetClassId, orElse: () => db.classrooms.first).name,
                    items: db.classrooms.map((c) => {'value': c.id, 'label': c.name}).toList(),
                    selectedValue: bulkTargetClassId,
                    onChanged: (val) {
                      setDialogState(() => bulkTargetClassId = val);
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
                  CustomExpandMenu(
                    title: 'Pilih Kelas Dihapus',
                    subtitle: bulkDeleteClassId.isEmpty
                        ? 'Belum ada kelas dipilih'
                        : db.classrooms.firstWhere((c) => c.id == bulkDeleteClassId, orElse: () => db.classrooms.first).name,
                    items: db.classrooms.map((c) => {'value': c.id, 'label': c.name}).toList(),
                    selectedValue: bulkDeleteClassId,
                    onChanged: (val) {
                      setDialogState(() => bulkDeleteClassId = val);
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
  void _showImportDialog(MockDatabase db) {
    String? selectedFileName;
    List<int>? selectedFileBytes;

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Impor Data Siswa'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Panduan Impor', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const Text('Ikuti format kolom agar data masuk tanpa error.', style: TextStyle(fontSize: 12, color: AppTheme.textMuted)),
                    const SizedBox(height: 16),
                    _buildGuideStep('1', 'Unduh template', 'Gunakan file template agar header kolom sesuai.'),
                    const SizedBox(height: 8),
                    _buildGuideStep('2', 'Isi data siswa', 'Pastikan kolom kelas dan jurusan sesuai data kelas di sistem.'),
                    const SizedBox(height: 8),
                    _buildGuideStep('3', 'Unggah dan impor', 'Password siswa otomatis: siswa123.'),
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey[50],
                        border: Border.all(color: Colors.grey[300]!, style: BorderStyle.solid),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Format kolom', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.black87)),
                          SizedBox(height: 4),
                          Text('nama | nisn | kelas | jurusan | nohp orangtua | no hp siswa', style: TextStyle(fontFamily: 'monospace', fontSize: 10, color: Colors.black87)),
                          SizedBox(height: 8),
                          Text('Catatan: kolom boleh kosong untuk nomor HP, tetapi nama, nisn, kelas, jurusan wajib.', style: TextStyle(fontSize: 10, color: AppTheme.textMuted)),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text('File Excel *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        ElevatedButton(
                          onPressed: () async {
                            final result = await FilePicker.platform.pickFiles(
                              type: FileType.custom,
                              allowedExtensions: ['xlsx', 'xls', 'csv'],
                              withData: true,
                            );
                            if (result != null && result.files.single.bytes != null) {
                              setDialogState(() {
                                selectedFileName = result.files.single.name;
                                selectedFileBytes = result.files.single.bytes;
                              });
                            }
                          },
                          child: const Text('Pilih File'),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(selectedFileName ?? 'Belum ada file dipilih.', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted), overflow: TextOverflow.ellipsis),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () async {
                    try {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mengunduh template...')));
                      await downloadAndOpenFile(
                        dio: ApiClient().dio,
                        url: '/admin/students/import/template',
                        fileName: 'template_import_siswa.xlsx',
                      );
                      
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).hideCurrentSnackBar();
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Unduhan selesai!')));
                      }
                    } catch (e) {
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal mengunduh: $e')));
                      }
                    }
                  },
                  child: const Text('UNDUH TEMPLATE', style: TextStyle(color: AppTheme.accentBlue)),
                ),
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
                ),
                ElevatedButton(
                  onPressed: selectedFileBytes == null ? null : () async {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Sedang mengimport data...')));
                    try {
                      final message = await db.importStudents(selectedFileBytes!, selectedFileName!);
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
                        await _loadData();
                      }
                    } catch (e) {
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceAll('Exception:', ''))));
                      }
                    }
                  },
                  child: const Text('IMPOR DATA'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  Widget _buildGuideStep(String step, String title, String subtitle) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 24,
          height: 24,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: AppTheme.accentBlue.withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: Text(step, style: const TextStyle(color: AppTheme.accentBlue, fontWeight: FontWeight.bold, fontSize: 12)),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.textDark)),
              Text(subtitle, style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
            ],
          ),
        ),
      ],
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
                    child: Column(
                      children: [
                        TextField(
                          controller: _searchController,
                          onChanged: (val) => setState(() => _searchQuery = val),
                          decoration: const InputDecoration(
                            hintText: 'Cari nama/NIS...',
                            prefixIcon: Icon(Icons.search),
                            contentPadding: EdgeInsets.zero,
                          ),
                        ),
                        const SizedBox(height: 12),
                        CustomExpandMenu(
                          title: 'Filter Kelas',
                          subtitle: _selectedClassRoomId.isEmpty ? 'Semua Kelas' : db.classrooms.firstWhere((c) => c.id == _selectedClassRoomId, orElse: () => db.classrooms.first).name,
                          items: [
                            const {'value': '', 'label': 'Semua Kelas'},
                            ...db.classrooms.map((c) => {'value': c.id, 'label': c.name}),
                          ],
                          selectedValue: _selectedClassRoomId,
                          onChanged: (val) {
                            setState(() {
                              _selectedClassRoomId = val;
                              _currentPage = 1;
                            });
                          },
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
                      onPressed: () => _showImportDialog(MockDatabase()),
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
                      : Builder(
                          builder: (context) {
                            final startIndex = (_currentPage - 1) * _itemsPerPage;
                            int endIndex = startIndex + _itemsPerPage;
                            if (endIndex > filteredStudents.length) {
                              endIndex = filteredStudents.length;
                            }
                            final paginatedStudents = startIndex < filteredStudents.length 
                                ? filteredStudents.sublist(startIndex, endIndex)
                                : <User>[];

                            return ListView.builder(
                              itemCount: paginatedStudents.length,
                              itemBuilder: (context, index) {
                                final student = paginatedStudents[index];
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
                        );
                  },
                ),
              ),
              
              // Pagination Controls
              if (filteredStudents.length > _itemsPerPage)
                Padding(
                  padding: const EdgeInsets.only(top: 8.0),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.chevron_left),
                        onPressed: _currentPage > 1
                            ? () => setState(() => _currentPage--)
                            : null,
                      ),
                      Text(
                        'Halaman $_currentPage dari ${(filteredStudents.length / _itemsPerPage).ceil()}',
                        style: const TextStyle(fontSize: 13),
                      ),
                      IconButton(
                        icon: const Icon(Icons.chevron_right),
                        onPressed: _currentPage < (filteredStudents.length / _itemsPerPage).ceil()
                            ? () => setState(() => _currentPage++)
                            : null,
                      ),
                    ],
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
