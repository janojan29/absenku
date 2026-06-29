import 'package:flutter/material.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import 'student_management_screen.dart';
import 'teacher_management_screen.dart';
import '../../profile/screens/profile_screen.dart';

class AdminMainScreen extends StatefulWidget {
  const AdminMainScreen({super.key});

  @override
  State<AdminMainScreen> createState() => _AdminMainScreenState();
}

class _AdminMainScreenState extends State<AdminMainScreen> {
  int _currentIndex = 0;

  late final List<Widget> _pages;

  @override
  void initState() {
    super.initState();
    _pages = [
      const StudentManagementScreen(),
      const TeacherManagementScreen(),
      const _ClassRoomTab(),
      const _SettingsTab(),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();

        return Scaffold(
          backgroundColor: AppTheme.backgroundLight,
          appBar: AppBar(
            title: Text(
              _currentIndex == 0
                  ? 'Kelola Siswa'
                  : _currentIndex == 1
                      ? 'Kelola Guru Piket'
                      : _currentIndex == 2
                          ? 'Kelola Kelas'
                          : 'Pengaturan Sekolah',
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.person),
                tooltip: 'Profil',
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
                },
              ),
              IconButton(
                icon: const Icon(Icons.logout),
                tooltip: 'Logout',
                onPressed: () {
                  db.logout();
                },
              ),
            ],
          ),
          body: IndexedStack(
            index: _currentIndex,
            children: _pages,
          ),
          bottomNavigationBar: BottomNavigationBar(
            currentIndex: _currentIndex,
            onTap: (index) {
              setState(() {
                _currentIndex = index;
              });
            },
            selectedItemColor: AppTheme.accentBlue,
            unselectedItemColor: AppTheme.textMuted,
            showUnselectedLabels: true,
            type: BottomNavigationBarType.fixed,
            items: const [
              BottomNavigationBarItem(
                icon: Icon(Icons.people_outline),
                activeIcon: Icon(Icons.people),
                label: 'Siswa',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.supervisor_account_outlined),
                activeIcon: Icon(Icons.supervisor_account),
                label: 'Guru',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.meeting_room_outlined),
                activeIcon: Icon(Icons.meeting_room),
                label: 'Kelas',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.settings_outlined),
                activeIcon: Icon(Icons.settings),
                label: 'Pengaturan',
              ),
            ],
          ),
        );
      },
    );
  }
}

// --- CLASSROOM MANAGEMENT TAB ---
class _ClassRoomTab extends StatefulWidget {
  const _ClassRoomTab();

  @override
  State<_ClassRoomTab> createState() => _ClassRoomTabState();
}

class _ClassRoomTabState extends State<_ClassRoomTab> {
  String _searchQuery = '';
  final _searchController = TextEditingController();
  final _classNameController = TextEditingController();
  final _classJurusanController = TextEditingController();
  bool _loading = true;
  int _currentPage = 1;
  static const int _itemsPerPage = 20;

  @override
  void initState() {
    super.initState();
    _loadClassrooms();
  }

  Future<void> _loadClassrooms() async {
    await MockDatabase().fetchClassrooms();
    if (mounted) setState(() => _loading = false);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _classNameController.dispose();
    _classJurusanController.dispose();
    super.dispose();
  }

  void _showAddClassDialog(MockDatabase db) {
    _classNameController.clear();
    _classJurusanController.clear();

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Tambah Kelas Baru'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: _classNameController,
                decoration: const InputDecoration(labelText: 'Nama Kelas', hintText: 'Contoh: XI RPL 1'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _classJurusanController,
                decoration: const InputDecoration(labelText: 'Jurusan / Kompetensi Keahlian', hintText: 'Contoh: Rekayasa Perangkat Lunak'),
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
                if (_classNameController.text.trim().isEmpty || _classJurusanController.text.trim().isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Semua field wajib diisi!')),
                  );
                  return;
                }

                await db.addClassRoom(_classNameController.text, _classJurusanController.text);
                if (context.mounted) {
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Kelas berhasil ditambahkan!')),
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
    if (_loading) return const Center(child: CircularProgressIndicator());

    final db = MockDatabase();
    final filteredClassrooms = db.classrooms.where((c) {
      return c.name.toLowerCase().contains(_searchQuery.toLowerCase()) ||
          c.jurusan.toLowerCase().contains(_searchQuery.toLowerCase());
    }).toList();

    return Padding(
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
                      onChanged: (val) {
                        setState(() {
                          _searchQuery = val;
                          _currentPage = 1;
                        });
                      },
                      decoration: const InputDecoration(
                        hintText: 'Cari nama kelas/jurusan...',
                        prefixIcon: Icon(Icons.search),
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  ElevatedButton.icon(
                    onPressed: () => _showAddClassDialog(db),
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

          // Classroom List
          Expanded(
            child: filteredClassrooms.isEmpty
                ? const Center(child: Text('Kelas tidak ditemukan.', style: TextStyle(color: AppTheme.textMuted)))
                : Builder(
                    builder: (context) {
                      final startIndex = (_currentPage - 1) * _itemsPerPage;
                      int endIndex = startIndex + _itemsPerPage;
                      if (endIndex > filteredClassrooms.length) endIndex = filteredClassrooms.length;
                      final paginatedClassrooms = startIndex < filteredClassrooms.length
                          ? filteredClassrooms.sublist(startIndex, endIndex)
                          : <ClassRoom>[];

                      return ListView.builder(
                        itemCount: paginatedClassrooms.length,
                        itemBuilder: (context, index) {
                          final classroom = paginatedClassrooms[index];

                      return Card(
                        margin: const EdgeInsets.only(bottom: 10),
                        child: ListTile(
                          leading: CircleAvatar(
                            backgroundColor: AppTheme.primaryBlue.withValues(alpha: 0.1),
                            child: const Icon(Icons.meeting_room, color: AppTheme.primaryBlue),
                          ),
                          title: Text(classroom.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          subtitle: Text(
                            classroom.jurusan,
                            style: const TextStyle(fontSize: 11),
                          ),
                          trailing: IconButton(
                            icon: const Icon(Icons.delete_outline, color: AppTheme.statusAbsent, size: 20),
                            onPressed: () {
                              showDialog(
                                context: context,
                                builder: (context) {
                                  return AlertDialog(
                                    title: const Text('Hapus Kelas'),
                                    content: Text('Apakah Anda yakin ingin menghapus kelas ${classroom.name}? (Ini akan menghapus seluruh data siswa di dalamnya).'),
                                    actions: [
                                      TextButton(
                                        onPressed: () => Navigator.pop(context),
                                        child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
                                      ),
                                      ElevatedButton(
                                        onPressed: () async {
                                          await db.deleteClassRoom(classroom.id);
                                          if (context.mounted) {
                                            Navigator.pop(context);
                                            ScaffoldMessenger.of(context).showSnackBar(
                                              const SnackBar(content: Text('Kelas terhapus!')),
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
                          ),
                        ),
                      );
                    },
                  );
                },
              ),
          ),
          
          // Pagination Controls
          if (filteredClassrooms.length > _itemsPerPage)
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
                    'Halaman $_currentPage dari ${(filteredClassrooms.length / _itemsPerPage).ceil()}',
                    style: const TextStyle(fontSize: 13),
                  ),
                  IconButton(
                    icon: const Icon(Icons.chevron_right),
                    onPressed: _currentPage < (filteredClassrooms.length / _itemsPerPage).ceil()
                        ? () => setState(() => _currentPage++)
                        : null,
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

// --- SETTINGS MANAGEMENT TAB ---
class _SettingsTab extends StatefulWidget {
  const _SettingsTab();

  @override
  State<_SettingsTab> createState() => _SettingsTabState();
}

class _SettingsTabState extends State<_SettingsTab> {
  final _formKey = GlobalKey<FormState>();
  final _latController = TextEditingController();
  final _lngController = TextEditingController();
  final _radiusController = TextEditingController();
  final _checkInStartController = TextEditingController();
  final _checkInEndController = TextEditingController();
  final _checkOutStartController = TextEditingController();
  final _checkOutEndController = TextEditingController();
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    await MockDatabase().fetchAdminSettings();
    if (mounted) {
      final db = MockDatabase();
      _latController.text = db.latitude.toString();
      _lngController.text = db.longitude.toString();
      _radiusController.text = db.radiusMeters.toString();
      _checkInStartController.text = db.checkInStart;
      _checkInEndController.text = db.checkInEnd;
      _checkOutStartController.text = db.checkOutStart;
      _checkOutEndController.text = db.checkOutEnd;
      setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _latController.dispose();
    _lngController.dispose();
    _radiusController.dispose();
    _checkInStartController.dispose();
    _checkInEndController.dispose();
    _checkOutStartController.dispose();
    _checkOutEndController.dispose();
    super.dispose();
  }

  void _save(MockDatabase db) async {
    if (!_formKey.currentState!.validate()) return;

    await db.updateSettings(
      latitude: double.parse(_latController.text),
      longitude: double.parse(_lngController.text),
      radiusMeters: int.parse(_radiusController.text),
      checkInStart: _checkInStartController.text,
      checkInEnd: _checkInEndController.text,
      checkOutStart: _checkOutStartController.text,
      checkOutEnd: _checkOutEndController.text,
    );

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pengaturan sekolah berhasil diperbarui!')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    final db = MockDatabase();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Lokasi & Radius Geofence', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _latController,
                            decoration: const InputDecoration(labelText: 'Latitude'),
                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                            validator: (val) => val == null || double.tryParse(val) == null ? 'Nilai tidak valid' : null,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _lngController,
                            decoration: const InputDecoration(labelText: 'Longitude'),
                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                            validator: (val) => val == null || double.tryParse(val) == null ? 'Nilai tidak valid' : null,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _radiusController,
                      decoration: const InputDecoration(labelText: 'Radius Sekolah (meter)'),
                      keyboardType: TextInputType.number,
                      validator: (val) => val == null || int.tryParse(val) == null ? 'Nilai tidak valid' : null,
                    ),
                    const SizedBox(height: 8),
                    TextButton.icon(
                      onPressed: () {
                        setState(() {
                          _latController.text = '-6.2088';
                          _lngController.text = '106.8456';
                        });
                      },
                      icon: const Icon(Icons.gps_fixed, size: 16),
                      label: const Text('Gunakan Koordinat Default'),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Jadwal Kehadiran (Format 24 Jam)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 16),
                    const Text('Waktu Absen Masuk', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _checkInStartController,
                            decoration: const InputDecoration(labelText: 'Mulai', hintText: '06:00'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _checkInEndController,
                            decoration: const InputDecoration(labelText: 'Batas Akhir', hintText: '08:00'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    const Text('Waktu Absen Pulang', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _checkOutStartController,
                            decoration: const InputDecoration(labelText: 'Mulai', hintText: '15:00'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _checkOutEndController,
                            decoration: const InputDecoration(labelText: 'Batas Akhir', hintText: '17:00'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: () => _save(db),
              child: const Text('SIMPAN SEMUA PENGATURAN'),
            ),
          ],
        ),
      ),
    );
  }
}
