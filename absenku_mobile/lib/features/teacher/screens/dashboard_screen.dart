import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../picket/screens/leave_queue_screen.dart';
import 'report_screen.dart';
import '../../../models/user.dart';
import '../../../models/attendance.dart';

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

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final db = MockDatabase();
    final today = DateTime.now();
    final todayStart = DateTime(today.year, today.month, today.day);

    // Get all students
    final students = db.users.where((u) => u.role == 'siswa').toList();

    // Filter students by selected classroom & search query
    final filteredStudents = students.where((student) {
      final matchesClass = _selectedClassRoomId.isEmpty || student.classRoomId == _selectedClassRoomId;
      final matchesSearch = student.name.toLowerCase().contains(_searchQuery.toLowerCase()) ||
          (student.nis != null && student.nis!.contains(_searchQuery));
      return matchesClass && matchesSearch;
    }).toList();

    // Calculate Counts for active students
    int present = 0;
    int late = 0;
    int leave = 0;
    int unknown = 0; // Belum absen

    for (var student in filteredStudents) {
      final att = db.attendance.firstWhere(
        (a) => a.userId == student.id &&
            a.date.year == todayStart.year &&
            a.date.month == todayStart.month &&
            a.date.day == todayStart.day,
        orElse: () => Attendance(id: '', userId: student.id, date: todayStart, status: 'unknown'),
      );

      switch (att.status) {
        case 'present':
          present++;
          break;
        case 'late':
          late++;
          break;
        case 'leave':
        case 'sick':
          leave++;
          break;
        case 'absent':
        case 'unknown':
        default:
          unknown++;
          break;
      }
    }

    return SingleChildScrollView(
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
                
                // Get attendance for today
                final att = db.attendance.firstWhere(
                  (a) => a.userId == student.id &&
                      a.date.year == todayStart.year &&
                      a.date.month == todayStart.month &&
                      a.date.day == todayStart.day,
                  orElse: () => Attendance(id: '', userId: student.id, date: todayStart, status: 'unknown'),
                );

                // Get class name
                final classRoom = db.classrooms.firstWhere(
                  (c) => c.id == student.classRoomId,
                  orElse: () => ClassRoom(id: '', name: '-', jurusan: '-'),
                );

                Color statusColor;
                String statusLabel;
                switch (att.status) {
                  case 'present':
                    statusColor = AppTheme.statusPresent;
                    statusLabel = 'Hadir';
                    break;
                  case 'late':
                    statusColor = AppTheme.statusLate;
                    statusLabel = 'Terlambat';
                    break;
                  case 'leave':
                  case 'sick':
                    statusColor = AppTheme.statusLeave;
                    statusLabel = 'Izin';
                    break;
                  case 'absent':
                    statusColor = AppTheme.statusAbsent;
                    statusLabel = 'Alfa';
                    break;
                  case 'unknown':
                  default:
                    statusColor = Colors.grey;
                    statusLabel = 'Belum Absen';
                }

                final checkInStr = att.checkInAt != null ? DateFormat('HH:mm').format(att.checkInAt!) : '—';
                final checkOutStr = att.checkOutAt != null ? DateFormat('HH:mm').format(att.checkOutAt!) : '—';

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
                            student.name.substring(0, 1).toUpperCase(),
                            style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primaryNavy),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                student.name,
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                              ),
                              Text(
                                '${classRoom.name} · NIS: ${student.nis ?? "-"}',
                                style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Masuk: $checkInStr · Pulang: $checkOutStr',
                                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w500, color: AppTheme.textDark),
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
