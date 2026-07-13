// File ini berisi layar pengelolaan data guru.
// Admin dapat mengatur data guru dan informasi terkait melalui tampilan yang disediakan di sini.

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../models/user.dart';
import '../../../core/widgets/custom_expand_menu.dart';

class TeacherManagementScreen extends StatefulWidget {
  const TeacherManagementScreen({super.key});

  @override
  State<TeacherManagementScreen> createState() => _TeacherManagementScreenState();
}

class _TeacherManagementScreenState extends State<TeacherManagementScreen> {
  String _searchQuery = '';
  final _searchController = TextEditingController();
  bool _loading = true;
  int _currentPage = 1;
  static const int _itemsPerPage = 20;

  // Form controllers
  final _nameController = TextEditingController();
  final _nipController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordConfirmController = TextEditingController();
  final _whatsappController = TextEditingController();
  final _subjectController = TextEditingController();
  final _waliKelasController = TextEditingController();
  String _selectedRole = 'guru';

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
    await MockDatabase().fetchTeachers();
    if (mounted) setState(() => _loading = false);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _nameController.dispose();
    _nipController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    _whatsappController.dispose();
    _subjectController.dispose();
    _waliKelasController.dispose();
    super.dispose();
  }

  void _showAddEditDialog(MockDatabase db, [User? teacher]) {
    final isEdit = teacher != null;
    if (isEdit) {
      _nameController.text = teacher.name;
      _nipController.text = teacher.nip ?? '';
      _whatsappController.text = teacher.whatsappNumber ?? '';
      _subjectController.text = teacher.subject ?? '';
      _waliKelasController.text = teacher.waliKelas ?? '';
      _selectedRole = (teacher.role == 'guru_walikelas') 
          ? 'guru_walikelas' 
          : (teacher.role == 'petugas_piket' ? 'petugas_piket' : 'guru');
      _passwordController.clear();
      _passwordConfirmController.clear();
    } else {
      _nameController.clear();
      _nipController.clear();
      _whatsappController.clear();
      _subjectController.clear();
      _waliKelasController.clear();
      _passwordController.clear();
      _passwordConfirmController.clear();
      _selectedRole = 'guru';
    }

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(isEdit ? 'Edit Guru' : 'Tambah Guru Baru'),
              content: SingleChildScrollView(
                child: Form(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Role select
                      CustomExpandMenu(
                        title: 'Pilih Role Guru',
                        subtitle: _selectedRole == 'guru'
                            ? 'Guru Biasa'
                            : _selectedRole == 'guru_walikelas'
                                ? 'Guru Walikelas'
                                : 'Petugas Piket',
                        items: const [
                          {'value': 'guru', 'label': 'Guru'},
                          {'value': 'guru_walikelas', 'label': 'Guru Walikelas'},
                          {'value': 'petugas_piket', 'label': 'Petugas Piket'},
                        ],
                        selectedValue: _selectedRole,
                        onChanged: (val) {
                          setDialogState(() => _selectedRole = val);
                        },
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _nameController,
                        decoration: const InputDecoration(labelText: 'Nama Lengkap'),
                        inputFormatters: [
                          FilteringTextInputFormatter.allow(RegExp(r"[a-zA-Z\s.,'-]")),
                          LengthLimitingTextInputFormatter(255),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _nipController,
                        decoration: const InputDecoration(labelText: 'NIP / ID Petugas'),
                        keyboardType: TextInputType.number,
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          LengthLimitingTextInputFormatter(50),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _whatsappController,
                        decoration: const InputDecoration(labelText: 'No. WhatsApp (08...)'),
                        keyboardType: TextInputType.phone,
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          LengthLimitingTextInputFormatter(30),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _subjectController,
                        decoration: const InputDecoration(labelText: 'Mata Pelajaran (Opsional)'),
                        inputFormatters: [
                          LengthLimitingTextInputFormatter(150),
                        ],
                      ),
                      if (_selectedRole == 'guru_walikelas') ...[
                        const SizedBox(height: 12),
                        TextField(
                          controller: _waliKelasController,
                          decoration: const InputDecoration(labelText: 'Wali Kelas (contoh: X TSM 1)'),
                          inputFormatters: [
                            LengthLimitingTextInputFormatter(100),
                          ],
                        ),
                      ],
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
                    final name = _nameController.text.trim();
                    final nip = _nipController.text.trim();
                    final wa = _whatsappController.text.trim();
                    final subj = _subjectController.text.trim();
                    final wk = _waliKelasController.text.trim();
                    final pwd = _passwordController.text;
                    final pwdConfirm = _passwordConfirmController.text;

                    if (name.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Nama Lengkap wajib diisi!')),
                      );
                      return;
                    }
                    if (name.length > 255) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Nama Lengkap maksimal 255 karakter!')),
                      );
                      return;
                    }
                    if (!RegExp(r"^[a-zA-Z\s.,'\-]+$").hasMatch(name)) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Nama Lengkap hanya boleh berisi huruf, spasi, dan tanda baca nama!')),
                      );
                      return;
                    }

                    if (nip.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('NIP / ID Petugas wajib diisi!')),
                      );
                      return;
                    }
                    if (nip.length > 50) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('NIP / ID Petugas maksimal 50 karakter!')),
                      );
                      return;
                    }
                    if (!RegExp(r"^[0-9]+$").hasMatch(nip)) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('NIP / ID Petugas harus berupa angka!')),
                      );
                      return;
                    }

                    if (wa.isNotEmpty) {
                      if (!RegExp(r'^(08|\+62|62)[0-9]+$').hasMatch(wa)) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Nomor WhatsApp harus diawali dengan 08, 62, atau +62 dan hanya berisi angka!')),
                        );
                        return;
                      }
                      if (wa.length > 30) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Nomor WhatsApp maksimal 30 karakter!')),
                        );
                        return;
                      }
                    }

                    if (subj.isNotEmpty && subj.length > 150) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Mata Pelajaran maksimal 150 karakter!')),
                      );
                      return;
                    }

                    if (_selectedRole == 'guru_walikelas') {
                      if (wk.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Keterangan wali kelas wajib diisi!')),
                        );
                        return;
                      }
                      if (wk.length > 100) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Keterangan wali kelas maksimal 100 karakter!')),
                        );
                        return;
                      }
                    }

                    if (!isEdit) {
                      if (pwd.isEmpty || pwdConfirm.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Password wajib diisi!')),
                        );
                        return;
                      }
                      if (pwd.length < 8) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Password minimal 8 karakter!')),
                        );
                        return;
                      }
                      if (pwd != pwdConfirm) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Password dan konfirmasi password tidak cocok!')),
                        );
                        return;
                      }
                    } else {
                      if (pwd.isNotEmpty) {
                        if (pwd.length < 8) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Password minimal 8 karakter!')),
                          );
                          return;
                        }
                        if (pwd != pwdConfirm) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Password dan konfirmasi password tidak cocok!')),
                          );
                          return;
                        }
                      }
                    }

                    try {
                      if (isEdit) {
                        await db.updateTeacher(
                          id: teacher.id,
                          name: _nameController.text,
                          teacherRole: _selectedRole,
                          nip: _nipController.text,
                          subject: _subjectController.text.isNotEmpty ? _subjectController.text : null,
                          waliKelas: _selectedRole == 'guru_walikelas' ? _waliKelasController.text : null,
                          password: _passwordController.text.isNotEmpty ? _passwordController.text : null,
                          passwordConfirmation: _passwordConfirmController.text.isNotEmpty ? _passwordConfirmController.text : null,
                          whatsappNumber: _whatsappController.text.isNotEmpty ? _whatsappController.text : null,
                        );
                      } else {
                        await db.addTeacher(
                          name: _nameController.text,
                          teacherRole: _selectedRole,
                          password: _passwordController.text,
                          passwordConfirmation: _passwordConfirmController.text,
                          nip: _nipController.text,
                          subject: _subjectController.text.isNotEmpty ? _subjectController.text : null,
                          waliKelas: _selectedRole == 'guru_walikelas' ? _waliKelasController.text : null,
                          whatsappNumber: _whatsappController.text.isNotEmpty ? _whatsappController.text : null,
                        );
                      }

                      if (context.mounted) {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(isEdit ? 'Data guru diperbarui!' : 'Guru baru ditambahkan!'),
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

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        if (_loading) return const Center(child: CircularProgressIndicator());

        final db = MockDatabase();
        final teachers = db.users.where((u) => u.role == 'guru' || u.role == 'guru_walikelas' || u.role == 'petugas_piket').toList();

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
                            'Guru tidak ditemukan.',
                            style: TextStyle(color: AppTheme.textMuted),
                          ),
                        )
                      : Builder(
                          builder: (context) {
                            final startIndex = (_currentPage - 1) * _itemsPerPage;
                            int endIndex = startIndex + _itemsPerPage;
                            if (endIndex > filteredTeachers.length) {
                              endIndex = filteredTeachers.length;
                            }
                            final paginatedTeachers = startIndex < filteredTeachers.length 
                                ? filteredTeachers.sublist(startIndex, endIndex)
                                : <User>[];

                            return ListView.builder(
                              itemCount: paginatedTeachers.length,
                              itemBuilder: (context, index) {
                                final teacher = paginatedTeachers[index];

                            return Card(
                              margin: const EdgeInsets.only(bottom: 10),
                              child: ListTile(
                                leading: CircleAvatar(
                                  radius: 20,
                                  backgroundColor: AppTheme.primaryNavy.withValues(alpha: 0.08),
                                  child: Text(
                                    teacher.name.isNotEmpty ? teacher.name.substring(0, 1).toUpperCase() : '?',
                                    style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primaryNavy),
                                  ),
                                ),
                                title: Text(teacher.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                subtitle: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'NIP: ${teacher.nip ?? "-"} · ${teacher.email} · WA: ${teacher.whatsappNumber ?? "-"}',
                                      style: const TextStyle(fontSize: 11),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      'Role: ${teacher.role == "guru_walikelas" ? "Guru Wali Kelas" : (teacher.role == "petugas_piket" ? "Petugas Piket" : "Guru Mapel")} · Mapel: ${teacher.subject ?? "-"} ${teacher.role == "guru_walikelas" && teacher.waliKelas != null ? "(${teacher.waliKelas})" : ""}',
                                      style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
                                    ),
                                  ],
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
                                              title: const Text('Hapus Guru'),
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
                                                        const SnackBar(content: Text('Guru terhapus!')),
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
              if (filteredTeachers.length > _itemsPerPage)
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
                        'Halaman $_currentPage dari ${(filteredTeachers.length / _itemsPerPage).ceil()}',
                        style: const TextStyle(fontSize: 13),
                      ),
                      IconButton(
                        icon: const Icon(Icons.chevron_right),
                        onPressed: _currentPage < (filteredTeachers.length / _itemsPerPage).ceil()
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
