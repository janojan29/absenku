// File ini berisi dashboard utama guru.
// Di layar ini guru bisa melihat ringkasan absensi, informasi kelas, dan akses ke fitur lain yang relevan.

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../picket/screens/leave_queue_screen.dart';

import '../../../core/widgets/profile_bottom_sheet.dart';
import 'report_screen.dart';
import '../../../core/widgets/custom_expand_menu.dart';
import 'dart:async';

class TeacherDashboardScreen extends StatefulWidget {
  const TeacherDashboardScreen({super.key});

  @override
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
  int _currentIndex = 0;

  // Fungsi untuk merender kerangka utama halaman (AppBar & Bottom Navigation) dan berpindah antar tab
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
          navItems.add(BottomNavigationBarItem(
            icon: Badge(
              label: Text('${db.leavePendingTotal}'),
              isLabelVisible: db.leavePendingTotal > 0,
              child: const Icon(Icons.assignment_outlined),
            ),
            activeIcon: Badge(
              label: Text('${db.leavePendingTotal}'),
              isLabelVisible: db.leavePendingTotal > 0,
              child: const Icon(Icons.assignment),
            ),
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
              InkWell(
                onTap: () => ProfileBottomSheet.show(context, db),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: CircleAvatar(
                    radius: 16,
                    backgroundColor: Colors.white.withValues(alpha: 0.2),
                    child: Text(
                      (db.currentUser?.name ?? 'U')[0].toUpperCase(),
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                  ),
                ),
              ),
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
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  // Fungsi untuk mengambil data statistik absensi dan daftar siswa dari database/server
  Future<void> _loadData({String? classRoomId, String? search, int page = 1}) async {
    await MockDatabase().fetchTeacherDashboard(classRoomId: classRoomId, search: search, page: page);
    if (mounted) {
      setState(() {
        _initialLoading = false;
        _selectedClassRoomId = MockDatabase().dashboardClassRoomId ?? '';
      });
    }
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  // Fungsi utama untuk merender (membangun) tampilan Tab Ringkasan Hari Ini
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
    final filteredStudents = allStudents.toList();

    return RefreshIndicator(
      onRefresh: () => _loadData(classRoomId: _selectedClassRoomId.isEmpty ? 'all' : _selectedClassRoomId, search: _searchQuery),
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
                    ],
                  ),
                  const SizedBox(height: 12),
                  CustomExpandMenu(
                    title: 'Pilih Kelas',
                    subtitle: _selectedClassRoomId.isEmpty
                        ? 'Semua Kelas'
                        : db.classrooms.firstWhere((c) => c.id == _selectedClassRoomId, orElse: () => db.classrooms.first).name,
                    items: [
                      const {'value': '', 'label': 'Semua Kelas'},
                      ...db.classrooms.map((c) => {'value': c.id, 'label': c.name}),
                    ],
                    selectedValue: _selectedClassRoomId,
                    onChanged: (value) {
                      setState(() => _selectedClassRoomId = value);
                      _loadData(classRoomId: value.isEmpty ? 'all' : value, search: _searchQuery);
                    },
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
                _buildStatCard(
                  db.dashboardIsCheckInClosed ? 'Alfa' : 'Belum Absen', 
                  unknown, 
                  db.dashboardIsCheckInClosed ? Colors.red : AppTheme.statusAbsent, 
                  db.dashboardIsCheckInClosed ? Icons.cancel_outlined : Icons.help_outline
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Search
            TextField(
              controller: _searchController,
              style: const TextStyle(fontSize: 14, color: AppTheme.textDark),
              onChanged: (val) {
                setState(() => _searchQuery = val);
                if (_debounce?.isActive ?? false) _debounce!.cancel();
                _debounce = Timer(const Duration(milliseconds: 500), () {
                  _loadData(classRoomId: _selectedClassRoomId.isEmpty ? 'all' : _selectedClassRoomId, search: val);
                });
              },
              decoration: InputDecoration(
                hintText: 'Cari nama siswa...', 
                hintStyle: const TextStyle(color: AppTheme.textMuted, fontSize: 14),
                prefixIcon: const Icon(Icons.search, color: AppTheme.textMuted), 
                filled: true,
                fillColor: Colors.white,
                contentPadding: const EdgeInsets.symmetric(vertical: 14),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.borderLight)),
                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryNavy, width: 2)),
              ),
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
                final id = student['id'];
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

                return InkWell(
                  onTap: () {
                    if (id != null) {
                      _showReportDialog(context, id.toString(), name);
                    }
                  },
                  child: Container(
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
                  ),
                );
              }),
            if (!_initialLoading && db.dashboardStudents.isNotEmpty && db.dashboardLastPage > 1)
              Padding(
                padding: const EdgeInsets.only(top: 16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.chevron_left),
                      onPressed: db.dashboardCurrentPage > 1 
                          ? () => _loadData(classRoomId: _selectedClassRoomId.isEmpty ? 'all' : _selectedClassRoomId, search: _searchQuery, page: db.dashboardCurrentPage - 1) 
                          : null,
                    ),
                    Text('Halaman ${db.dashboardCurrentPage} dari ${db.dashboardLastPage}', style: const TextStyle(fontWeight: FontWeight.bold)),
                    IconButton(
                      icon: const Icon(Icons.chevron_right),
                      onPressed: db.dashboardCurrentPage < db.dashboardLastPage 
                          ? () => _loadData(classRoomId: _selectedClassRoomId.isEmpty ? 'all' : _selectedClassRoomId, search: _searchQuery, page: db.dashboardCurrentPage + 1) 
                          : null,
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }

  // Fungsi pembantu untuk membuat kotak kartu statistik (seperti jumlah Hadir, Terlambat, dll)
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

  // Fungsi untuk memunculkan modal/dialog "Laporkan Keluar" ketika nama siswa diklik
  void _showReportDialog(BuildContext context, String studentProfileId, String studentName) {
    final subjectController = TextEditingController();
    final descriptionController = TextEditingController();
    bool isSubmitting = false;

    showGeneralDialog(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Dismiss',
      barrierColor: Colors.transparent,
      transitionDuration: const Duration(milliseconds: 300),
      pageBuilder: (context, animation, secondaryAnimation) {
        return StatefulBuilder(
          builder: (context, setState) {
            return Stack(
              children: [
                // Animated Backdrop with Blur
                FadeTransition(
                  opacity: animation,
                  child: GestureDetector(
                    onTap: () => Navigator.pop(context),
                    child: Container(
                      color: const Color(0xFF070D1A).withValues(alpha: 0.7),
                      // BackdropFilter is omitted for performance on some mobile devices, 
                      // dark semi-transparent color is usually enough.
                    ),
                  ),
                ),
                // Modal Content
                FadeTransition(
                  opacity: animation,
                  child: ScaleTransition(
                    scale: Tween<double>(begin: 0.95, end: 1.0).animate(
                      CurvedAnimation(parent: animation, curve: Curves.easeOutCubic),
                    ),
                    child: Center(
                      child: SingleChildScrollView(
                        child: Dialog(
                          backgroundColor: Colors.transparent,
                          insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
                          elevation: 0,
                          child: Container(
                            width: double.infinity,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(24),
                              boxShadow: [
                                BoxShadow(color: Colors.black.withValues(alpha: 0.2), blurRadius: 40, offset: const Offset(0, 10)),
                                BoxShadow(color: Colors.red.withValues(alpha: 0.1), blurRadius: 20, offset: const Offset(0, 0)),
                              ],
                            ),
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(24),
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  // Top Gradient Accent
                                  Container(
                                    height: 4,
                                    width: double.infinity,
                                    decoration: const BoxDecoration(
                                      gradient: LinearGradient(
                                        colors: [Color(0xFFEF4444), Color(0xFFF87171), Color(0xFFFB923C)],
                                        begin: Alignment.centerLeft,
                                        end: Alignment.centerRight,
                                      ),
                                    ),
                                  ),
                                  
                                  // Header
                                  Padding(
                                    padding: const EdgeInsets.all(24),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        // Icon
                                        Container(
                                          width: 48,
                                          height: 48,
                                          decoration: BoxDecoration(
                                            gradient: const LinearGradient(
                                              colors: [Color(0xFFEF4444), Color(0xFFDC2626)],
                                              begin: Alignment.topLeft,
                                              end: Alignment.bottomRight,
                                            ),
                                            borderRadius: BorderRadius.circular(16),
                                            boxShadow: [
                                              BoxShadow(color: Colors.red.withValues(alpha: 0.3), blurRadius: 12, offset: const Offset(0, 4)),
                                            ],
                                          ),
                                          child: const Icon(Icons.warning_amber_rounded, color: Colors.white, size: 28),
                                        ),
                                        const SizedBox(width: 16),
                                        // Title & Subtitle
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              const Text('Laporkan Keluar', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A), height: 1.2)),
                                              const SizedBox(height: 4),
                                              Row(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  const Padding(
                                                    padding: EdgeInsets.only(top: 2),
                                                    child: Icon(Icons.chat_bubble_rounded, size: 12, color: Color(0xFF10B981)),
                                                  ),
                                                  const SizedBox(width: 4),
                                                  Expanded(
                                                    child: Text('Notifikasi WA Ortu', style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                                                  ),
                                                ],
                                              ),
                                            ],
                                          ),
                                        ),
                                        // Close Button
                                        GestureDetector(
                                          onTap: () => Navigator.pop(context),
                                          child: Container(
                                            padding: const EdgeInsets.all(6),
                                            decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(10)),
                                            child: Icon(Icons.close, size: 18, color: Colors.grey.shade500),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),

                                  Divider(height: 1, thickness: 1, color: Colors.grey.shade100),

                                  // Body
                                  Padding(
                                    padding: const EdgeInsets.all(24),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        // Student Info
                                        Text('SISWA TERPILIH', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 1.2, color: Colors.grey.shade500)),
                                        const SizedBox(height: 8),
                                        Container(
                                          padding: const EdgeInsets.all(16),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFF8FAFC),
                                            border: Border.all(color: const Color(0xFFE2E8F0)),
                                            borderRadius: BorderRadius.circular(16),
                                          ),
                                          child: Row(
                                            children: [
                                              Container(
                                                width: 44,
                                                height: 44,
                                                decoration: const BoxDecoration(
                                                  gradient: LinearGradient(colors: [Color(0xFF3B82F6), Color(0xFF1D4ED8)], begin: Alignment.topLeft, end: Alignment.bottomRight),
                                                  shape: BoxShape.circle,
                                                ),
                                                alignment: Alignment.center,
                                                child: Text(studentName.isNotEmpty ? studentName[0].toUpperCase() : '?', style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                                              ),
                                              const SizedBox(width: 12),
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    Text(studentName, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)), maxLines: 1, overflow: TextOverflow.ellipsis),
                                                    const SizedBox(height: 2),
                                                    Text('Klik nama lain untuk mengganti', style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
                                                  ],
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                        const SizedBox(height: 20),

                                        // Subject Field
                                        Row(
                                          children: [
                                            const Text('MATA PELAJARAN', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                                            const SizedBox(width: 4),
                                            Text('*wajib', style: TextStyle(fontSize: 10, color: Colors.red.shade500)),
                                          ],
                                        ),
                                        const SizedBox(height: 8),
                                        TextField(
                                          controller: subjectController,
                                          style: const TextStyle(fontSize: 14, color: Color(0xFF0F172A)),
                                          decoration: InputDecoration(
                                            hintText: 'Contoh: Matematika',
                                            hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
                                            prefixIcon: Icon(Icons.book_outlined, color: Colors.grey.shade400, size: 20),
                                            filled: true,
                                            fillColor: Colors.white,
                                            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                                            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                                            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF3B82F6), width: 2)),
                                          ),
                                        ),
                                        const SizedBox(height: 16),

                                        // Description Field
                                        Row(
                                          children: [
                                            const Text('KETERANGAN', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                                            const SizedBox(width: 4),
                                            Text('opsional', style: TextStyle(fontSize: 10, color: Colors.grey.shade400)),
                                          ],
                                        ),
                                        const SizedBox(height: 8),
                                        TextField(
                                          controller: descriptionController,
                                          maxLines: 3,
                                          style: const TextStyle(fontSize: 14, color: Color(0xFF0F172A)),
                                          decoration: InputDecoration(
                                            hintText: 'Siswa izin ke toilet tapi tidak kembali...',
                                            hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
                                            filled: true,
                                            fillColor: Colors.white,
                                            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                                            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                                            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF3B82F6), width: 2)),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  
                                  Divider(height: 1, thickness: 1, color: Colors.grey.shade100),

                                  // Actions
                                  Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                                    child: Row(
                                      children: [
                                        Expanded(
                                          child: TextButton(
                                            onPressed: isSubmitting ? null : () => Navigator.pop(context),
                                            style: TextButton.styleFrom(
                                              padding: const EdgeInsets.symmetric(vertical: 14),
                                              backgroundColor: Colors.grey.shade100,
                                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                            ),
                                            child: Text('Batal', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.grey.shade700)),
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          flex: 2,
                                          child: Container(
                                            decoration: BoxDecoration(
                                              gradient: const LinearGradient(colors: [Color(0xFFEF4444), Color(0xFFDC2626)]),
                                              borderRadius: BorderRadius.circular(12),
                                              boxShadow: [BoxShadow(color: Colors.red.withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 4))],
                                            ),
                                            child: ElevatedButton(
                                              onPressed: isSubmitting
                                                  ? null
                                                  : () async {
                                                      if (subjectController.text.trim().isEmpty) {
                                                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mata Pelajaran wajib diisi')));
                                                        return;
                                                      }
                                                      setState(() => isSubmitting = true);
                                                      try {
                                                        final successMsg = await MockDatabase().reportMissingStudent(
                                                          studentProfileId,
                                                          subjectController.text.trim(),
                                                          description: descriptionController.text.trim(),
                                                        );
                                                        if (context.mounted) {
                                                          Navigator.pop(context);
                                                          ScaffoldMessenger.of(context).showSnackBar(
                                                            SnackBar(
                                                              content: Row(
                                                                children: [
                                                                  const Icon(Icons.info_outline, color: Colors.white),
                                                                  const SizedBox(width: 8),
                                                                  Expanded(child: Text(successMsg)),
                                                                ],
                                                              ),
                                                              backgroundColor: const Color(0xFF10B981),
                                                              behavior: SnackBarBehavior.floating,
                                                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                                              margin: const EdgeInsets.all(16),
                                                            ),
                                                          );
                                                        }
                                                      } catch (e) {
                                                        setState(() => isSubmitting = false);
                                                        if (context.mounted) {
                                                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString()), backgroundColor: Colors.red));
                                                        }
                                                      }
                                                    },
                                              style: ElevatedButton.styleFrom(
                                                backgroundColor: Colors.transparent,
                                                shadowColor: Colors.transparent,
                                                padding: const EdgeInsets.symmetric(vertical: 14),
                                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                              ),
                                              child: isSubmitting 
                                                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                                                : const Row(
                                                    mainAxisAlignment: MainAxisAlignment.center,
                                                    children: [
                                                      Icon(Icons.send_rounded, color: Colors.white, size: 16),
                                                      SizedBox(width: 6),
                                                      Text('Kirim Laporan', style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold)),
                                                    ],
                                                  ),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            );
          }
        );
      },
    );
  }
}
