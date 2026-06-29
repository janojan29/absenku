import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../picket/screens/leave_queue_screen.dart';
import '../../profile/screens/profile_screen.dart';
import 'report_screen.dart';

class TeacherDashboardScreen extends StatefulWidget {
  const TeacherDashboardScreen({super.key});

  @override
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        final user = db.currentUser;
        if (user == null) return const SizedBox();

        final List<Widget> tabs = [
          const _TodaySummaryTab(),
        ];
        final List<BottomNavigationBarItem> navItems = [
          const BottomNavigationBarItem(
            icon: Icon(Icons.dashboard_outlined),
            activeIcon: Icon(Icons.dashboard),
            label: 'Ringkasan',
          ),
        ];

        if (user.role == 'petugas_piket') {
          tabs.add(const LeaveQueueScreen());
          navItems.add(const BottomNavigationBarItem(
            icon: Icon(Icons.assignment_outlined),
            activeIcon: Icon(Icons.assignment),
            label: 'Antrian Izin',
          ));
        }

        if (user.role == 'petugas_piket' || user.role == 'guru_walikelas') {
          tabs.add(const ReportScreen());
          navItems.add(const BottomNavigationBarItem(
            icon: Icon(Icons.bar_chart_outlined),
            activeIcon: Icon(Icons.bar_chart),
            label: 'Rekap Absen',
          ));
        }

        // Adjust index if out of bounds
        int activeIndex = _currentIndex;
        if (activeIndex >= tabs.length) {
          activeIndex = 0;
        }

        String title = 'Dashboard Guru';
        if (activeIndex == 0) {
          title = 'Dashboard Guru';
        } else {
          final label = navItems[activeIndex].label;
          if (label == 'Antrian Izin') {
            title = 'Antrian Izin';
          } else if (label == 'Rekap Absen') {
            title = 'Rekap Absensi';
          }
        }

        return Scaffold(
          backgroundColor: AppTheme.backgroundLight,
          appBar: AppBar(
            title: Text(title),
            actions: [
              IconButton(
                icon: const Icon(Icons.person),
                tooltip: 'Profil',
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
                },
              ),
              IconButton(icon: const Icon(Icons.logout), tooltip: 'Logout', onPressed: () => db.logout()),
            ],
          ),
          body: IndexedStack(index: activeIndex, children: tabs),
          bottomNavigationBar: navItems.length > 1
              ? BottomNavigationBar(
                  currentIndex: activeIndex,
                  onTap: (index) => setState(() => _currentIndex = index),
                  selectedItemColor: AppTheme.accentBlue,
                  unselectedItemColor: AppTheme.textMuted,
                  showUnselectedLabels: true,
                  type: BottomNavigationBarType.fixed,
                  items: navItems,
                )
              : null,
        );
      },
    );
  }
}

class _TodaySummaryTab extends StatefulWidget {
  const _TodaySummaryTab();

  @override
  State<_TodaySummaryTab> createState() => _TodaySummaryTabState();
}

class _TodaySummaryTabState extends State<_TodaySummaryTab> {
  String _selectedClassRoomId = '';
  String _searchQuery = '';
  final _searchController = TextEditingController();
  bool _initialLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData([String? classRoomId]) async {
    await MockDatabase().fetchTeacherDashboard(classRoomId: classRoomId);
    if (mounted) {
      setState(() {
        _initialLoading = false;
        _selectedClassRoomId = MockDatabase().dashboardClassRoomId ?? '';
      });
    }
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_initialLoading) return const Center(child: CircularProgressIndicator());

    final db = MockDatabase();
    final today = DateTime.now();
    final present = db.dashboardCounts['present'] ?? 0;
    final late = db.dashboardCounts['late'] ?? 0;
    final leave = db.dashboardCounts['leave'] ?? 0;
    final unknown = db.dashboardCounts['unknown'] ?? 0;

    final allStudents = db.dashboardStudents;
    final filteredStudents = allStudents.where((s) {
      final name = s['name'] as String? ?? '';
      return name.toLowerCase().contains(_searchQuery.toLowerCase());
    }).toList();

    return RefreshIndicator(
      onRefresh: () => _loadData(_selectedClassRoomId.isEmpty ? null : _selectedClassRoomId),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header + Class filter
            Container(
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          const Text('Ringkasan Hari Ini', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textDark)),
                          Text(DateFormat('dd MMMM yyyy', 'id_ID').format(today), style: const TextStyle(fontSize: 12, color: AppTheme.textMuted)),
                        ]),
                      ),
                      SizedBox(
                        width: 140,
                        child: DropdownButtonFormField<String>(
                          initialValue: _selectedClassRoomId,
                          isExpanded: true,
                          items: [
                            const DropdownMenuItem(value: '', child: Text('Semua Kelas', style: TextStyle(fontSize: 12))),
                            ...db.classrooms.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name, style: const TextStyle(fontSize: 12)))),
                          ],
                          onChanged: (value) {
                            setState(() => _selectedClassRoomId = value ?? '');
                            _loadData(value?.isEmpty == true ? null : value);
                          },
                          decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4)),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Stats grid
            GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              childAspectRatio: 1.6,
              children: [
                _buildStatCard('Hadir', present, AppTheme.statusPresent, Icons.check_circle_outline),
                _buildStatCard('Terlambat', late, AppTheme.statusLate, Icons.access_time),
                _buildStatCard('Izin', leave, AppTheme.statusLeave, Icons.description_outlined),
                _buildStatCard('Belum Absen', unknown, AppTheme.statusAbsent, Icons.help_outline),
              ],
            ),
            const SizedBox(height: 20),

            // Search
            TextField(
              controller: _searchController,
              onChanged: (val) => setState(() => _searchQuery = val),
              decoration: const InputDecoration(hintText: 'Cari nama siswa...', prefixIcon: Icon(Icons.search), contentPadding: EdgeInsets.zero),
            ),
            const SizedBox(height: 16),

            Text('Daftar Kehadiran Siswa (${filteredStudents.length})',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppTheme.textDark)),
            const SizedBox(height: 8),

            // Student list
            if (filteredStudents.isEmpty)
              Container(
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
                padding: const EdgeInsets.all(24),
                child: const Center(child: Text('Tidak ada data siswa.', style: TextStyle(color: AppTheme.textMuted))),
              )
            else
              ...filteredStudents.map((student) {
                final name = student['name'] as String? ?? '-';
                final className = student['class_room'] as String? ?? '-';
                final status = student['status'] as String? ?? 'unknown';
                final statusLabel = student['status_label'] as String? ?? 'Belum Absen';
                final checkIn = student['check_in_at'] as String?;
                final checkOut = student['check_out_at'] as String?;

                Color statusColor;
                switch (status) {
                  case 'present': statusColor = AppTheme.statusPresent; break;
                  case 'late': statusColor = AppTheme.statusLate; break;
                  case 'leave': case 'sick': statusColor = AppTheme.statusLeave; break;
                  case 'absent': statusColor = AppTheme.statusAbsent; break;
                  default: statusColor = Colors.grey;
                }

                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppTheme.borderLight)),
                  padding: const EdgeInsets.all(12),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 18,
                        backgroundColor: AppTheme.primaryNavy.withValues(alpha: 0.08),
                        child: Text(name.isNotEmpty ? name.substring(0, 1).toUpperCase() : '?',
                            style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primaryNavy, fontSize: 13)),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                          Text(className, style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                          if (checkIn != null || checkOut != null)
                            Text('Masuk: ${checkIn ?? "-"} · Pulang: ${checkOut ?? "-"}',
                                style: const TextStyle(fontSize: 10, color: AppTheme.textMuted)),
                        ]),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: statusColor.withValues(alpha: 0.1),
                          border: Border.all(color: statusColor.withValues(alpha: 0.3)),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(statusLabel, style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 10)),
                      ),
                    ],
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String title, int count, Color color, IconData icon) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Row(children: [
            Icon(icon, color: color, size: 16),
            const SizedBox(width: 6),
            Text(title, style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: color)),
          ]),
          const SizedBox(height: 6),
          Text('$count', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: color)),
        ],
      ),
    );
  }
}
