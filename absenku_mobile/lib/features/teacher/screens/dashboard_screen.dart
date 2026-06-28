import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../picket/screens/leave_queue_screen.dart';
import 'report_screen.dart';

class TeacherDashboardScreen extends StatefulWidget {
  const TeacherDashboardScreen({super.key});

  @override
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
  int _currentIndex = 0;

  // Tabs list
  late final List<Widget> _tabs;

  @override
  void initState() {
    super.initState();
    _tabs = [
      const _TodaySummaryTab(),
      const LeaveQueueScreen(),
      const ReportScreen(),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        final user = db.currentUser;
        if (user == null) return const SizedBox();

        return Scaffold(
          backgroundColor: AppTheme.backgroundLight,
          appBar: _currentIndex == 0
              ? AppBar(
                  title: const Text('Dashboard Guru'),
                  actions: [
                    IconButton(
                      icon: const Icon(Icons.logout),
                      tooltip: 'Logout',
                      onPressed: () {
                        db.logout();
                      },
                    ),
                  ],
                )
              : null, // Subscreens have their own app bar or preferred layouts
          body: IndexedStack(
            index: _currentIndex,
            children: _tabs,
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
                icon: Icon(Icons.dashboard_outlined),
                activeIcon: Icon(Icons.dashboard),
                label: 'Ringkasan',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.assignment_outlined),
                activeIcon: Icon(Icons.assignment),
                label: 'Antrian Izin',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.bar_chart_outlined),
                activeIcon: Icon(Icons.bar_chart),
                label: 'Rekap Absen',
              ),
            ],
          ),
        );
      },
    );
  }
}

// Inner Widget for Ringkasan Hari Ini (Today's Summary) Tab
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
    if (_initialLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    final db = MockDatabase();
    final today = DateTime.now();

    // Get counts from API
    final present = db.dashboardCounts['present'] ?? 0;
    final late = db.dashboardCounts['late'] ?? 0;
    final leave = db.dashboardCounts['leave'] ?? 0;
    final unknown = db.dashboardCounts['unknown'] ?? 0;

    // Get students from API, apply search filter
    final allStudents = db.dashboardStudents;
    final filteredStudents = allStudents.where((student) {
      final name = student['name'] as String? ?? '';
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
            // Class Filter Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Ringkasan Hari Ini',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textDark),
                            ),
                            Text(
                              DateFormat('dd MMMM yyyy', 'id_ID').format(today),
                              style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
                            ),
                          ],
                        ),
                        // Dropdown filter
                        SizedBox(
                          width: 140,
                          child: DropdownButtonFormField<String>(
                            initialValue: _selectedClassRoomId,
                            items: [
                              const DropdownMenuItem(value: '', child: Text('Semua Kelas')),
                              ...db.classrooms.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name))),
                            ],
                            onChanged: (value) {
                              setState(() {
                                _selectedClassRoomId = value ?? '';
                              });
                              _loadData(value?.isEmpty == true ? null : value);
                            },
                            decoration: const InputDecoration(
                              contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Statistics Counter Grid
            GridPaper(
              color: Colors.transparent,
              child: GridView.count(
                crossAxisCount: 2,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                childAspectRatio: 1.6,
                children: [
                  _buildStatCard('Hadir', present, AppTheme.statusPresent),
                  _buildStatCard('Terlambat', late, AppTheme.statusLate),
                  _buildStatCard('Izin', leave, AppTheme.statusLeave),
                  _buildStatCard('Belum Absen', unknown, AppTheme.statusAbsent),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Search Bar
            TextField(
              controller: _searchController,
              onChanged: (val) {
                setState(() {
                  _searchQuery = val;
                });
              },
              decoration: const InputDecoration(
                hintText: 'Cari nama siswa...',
                prefixIcon: Icon(Icons.search),
                contentPadding: EdgeInsets.zero,
              ),
            ),
            const SizedBox(height: 16),

            // List Header
            Text(
              'Daftar Kehadiran Siswa (${filteredStudents.length})',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppTheme.textDark),
            ),
            const SizedBox(height: 8),

            // Students Attendance List
            if (filteredStudents.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(24.0),
                  child: Center(
                    child: Text(
                      'Tidak ada data siswa.',
                      style: TextStyle(color: AppTheme.textMuted, fontSize: 13),
                    ),
                  ),
                ),
              )
            else
              ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: filteredStudents.length,
                itemBuilder: (context, index) {
                  final student = filteredStudents[index];
                  final name = student['name'] as String? ?? '-';
                  final className = student['class_room'] as String? ?? '-';
                  final status = student['status'] as String? ?? 'unknown';
                  final statusLabel = student['status_label'] as String? ?? 'Belum Absen';

                  Color statusColor;
                  switch (status) {
                    case 'present':
                      statusColor = AppTheme.statusPresent;
                      break;
                    case 'late':
                      statusColor = AppTheme.statusLate;
                      break;
                    case 'leave':
                    case 'sick':
                      statusColor = AppTheme.statusLeave;
                      break;
                    case 'absent':
                      statusColor = AppTheme.statusAbsent;
                      break;
                    case 'holiday':
                      statusColor = Colors.blue;
                      break;
                    case 'unknown':
                    default:
                      statusColor = Colors.grey;
                  }

                  return Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    child: Padding(
                      padding: const EdgeInsets.all(12.0),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 20,
                            backgroundColor: AppTheme.primaryNavy.withValues(alpha: 0.08),
                            child: Text(
                              name.isNotEmpty ? name.substring(0, 1).toUpperCase() : '?',
                              style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primaryNavy),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  name,
                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                ),
                                Text(
                                  className,
                                  style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: statusColor.withValues(alpha: 0.1),
                              border: Border.all(color: statusColor.withValues(alpha: 0.3)),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              statusLabel,
                              style: TextStyle(
                                color: statusColor,
                                fontWeight: FontWeight.bold,
                                fontSize: 10,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String title, int count, Color color) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.textMuted),
            ),
            const SizedBox(height: 4),
            Text(
              '$count',
              style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: color),
            ),
          ],
        ),
      ),
    );
  }
}
