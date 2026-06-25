// lib/screens/teacher/teacher_report_screen.dart
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../../core/app_colors.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/status_badge.dart';
import '../../services/api_service.dart';

class TeacherReportScreen extends StatefulWidget {
  const TeacherReportScreen({super.key});

  @override
  State<TeacherReportScreen> createState() => _TeacherReportScreenState();
}

class _TeacherReportScreenState extends State<TeacherReportScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  int? _selectedClassRoomId;
  List<dynamic> _classrooms = [];
  List<dynamic> _rows = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
    });
    try {
      if (_classrooms.isEmpty) {
        final dashboardData = await ApiService.getTeacherDashboard();
        _classrooms = dashboardData['classrooms'] as List<dynamic>? ?? [];
        if (_selectedClassRoomId == null && dashboardData['class_room_id'] != null) {
          _selectedClassRoomId = dashboardData['class_room_id'] as int;
        }
      }

      final reportData = await ApiService.getTeacherAttendanceReport(
        classRoomId: _selectedClassRoomId,
      );
      setState(() {
        _rows = reportData['rows'] as List<dynamic>? ?? [];
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
        );
      }
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _exportReport(String format) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        content: Row(
          children: [
            const CircularProgressIndicator(),
            const SizedBox(width: 20),
            Text('Mengekspor laporan $format...'),
          ],
        ),
      ),
    );

    try {
      final ext = format.toLowerCase() == 'excel' ? 'excel' : 'pdf';
      final query = _selectedClassRoomId != null ? '?class_room_id=$_selectedClassRoomId' : '';
      final url = Uri.parse('${ApiService.baseUrl}/teacher/reports/attendance/$ext$query');
      final response = await http.get(
        url,
        headers: {
          'Authorization': 'Bearer ${ApiService.token}',
          'Accept': 'application/json',
        },
      );

      if (mounted) {
        Navigator.pop(context);
        if (response.statusCode == 200) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Laporan $format berhasil diekspor!')),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Gagal mengekspor laporan $format.')),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error saat mengekspor: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final leaveRequests = _rows.where((r) => r['Status Izin'] != '-').toList();

    return AppScaffold(
      title: 'Laporan Kelas',
      child: Column(
        children: [
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                if (_classrooms.isNotEmpty)
                  DropdownButton<int>(
                    value: _selectedClassRoomId,
                    items: _classrooms.map<DropdownMenuItem<int>>((c) {
                      return DropdownMenuItem<int>(
                        value: c['id'] as int,
                        child: Text(c['name'] as String),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) {
                        setState(() {
                          _selectedClassRoomId = val;
                        });
                        _loadData();
                      }
                    },
                  )
                else
                  const SizedBox(),
                Row(
                  children: [
                    ElevatedButton.icon(
                      onPressed: () => _exportReport('Excel'),
                      icon: const Icon(Icons.file_download, size: 16),
                      label: const Text('Excel', style: TextStyle(fontSize: 12)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green[700],
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton.icon(
                      onPressed: () => _exportReport('PDF'),
                      icon: const Icon(Icons.picture_as_pdf, size: 16),
                      label: const Text('PDF', style: TextStyle(fontSize: 12)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.red[700],
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          TabBar(
            controller: _tabController,
            tabs: const [
              Tab(text: 'Rekap Absen'),
              Tab(text: 'Rekap Keterangan'),
            ],
            labelColor: AppColors.electric600,
            unselectedLabelColor: AppColors.space500,
            indicatorColor: AppColors.electric600,
          ),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _isLoading && _rows.isEmpty
                    ? const Center(child: CircularProgressIndicator())
                    : RefreshIndicator(
                        onRefresh: _loadData,
                        child: SingleChildScrollView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.all(16.0),
                          child: Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Rekap Kehadiran Harian',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                  ),
                                  const SizedBox(height: 16),
                                  _rows.isEmpty
                                      ? const Padding(
                                          padding: EdgeInsets.symmetric(vertical: 24),
                                          child: Center(child: Text('Tidak ada catatan kehadiran.', style: TextStyle(color: Colors.black45))),
                                        )
                                      : ListView.separated(
                                          shrinkWrap: true,
                                          physics: const NeverScrollableScrollPhysics(),
                                          itemCount: _rows.length,
                                          separatorBuilder: (context, index) => const Divider(color: AppColors.space200),
                                          itemBuilder: (context, index) {
                                            final row = _rows[index];
                                            final name = row['Nama'] ?? '-';
                                            final statusStr = (row['Status'] ?? 'unknown').toString();
                                            final isLate = statusStr.toLowerCase().contains('terlambat');
                                            final isPresent = statusStr.toLowerCase().contains('hadir');
                                            final isAbsent = statusStr.toLowerCase().contains('alfa') || statusStr.toLowerCase().contains('absent');
                                            final isLeave = statusStr.toLowerCase().contains('izin') || statusStr.toLowerCase().contains('sakit');
                                            
                                            final status = isLate
                                                ? 'late'
                                                : isPresent
                                                    ? 'present'
                                                    : isAbsent
                                                        ? 'absent'
                                                        : isLeave
                                                            ? 'leave'
                                                            : 'unknown';

                                            return ListTile(
                                              title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                              subtitle: Text('Masuk: ${row['Masuk']} - Pulang: ${row['Pulang']}'),
                                              trailing: StatusBadge(status: status, label: statusStr),
                                            );
                                          },
                                        ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                _isLoading && _rows.isEmpty
                    ? const Center(child: CircularProgressIndicator())
                    : RefreshIndicator(
                        onRefresh: _loadData,
                        child: SingleChildScrollView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.all(16.0),
                          child: Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Rekap Izin / Sakit',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                  ),
                                  const SizedBox(height: 16),
                                  leaveRequests.isEmpty
                                      ? const Center(
                                          child: Padding(
                                            padding: EdgeInsets.symmetric(vertical: 24),
                                            child: Text('Tidak ada pengajuan izin di kelas ini', style: TextStyle(color: Colors.black45)),
                                          ),
                                        )
                                      : ListView.separated(
                                          shrinkWrap: true,
                                          physics: const NeverScrollableScrollPhysics(),
                                          itemCount: leaveRequests.length,
                                          separatorBuilder: (context, index) => const Divider(color: AppColors.space200),
                                          itemBuilder: (context, index) {
                                            final leave = leaveRequests[index];
                                            final name = leave['Nama'] ?? '-';
                                            final date = leave['Tanggal'] ?? '-';
                                            final type = leave['Jenis Izin'] ?? '-';
                                            final reason = leave['Alasan Izin'] ?? '-';
                                            final note = leave['Keterangan Izin'] ?? '-';
                                            final status = (leave['Status Izin'] ?? 'pending').toString();

                                            return ListTile(
                                              title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                              subtitle: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  const SizedBox(height: 4),
                                                  Text('Tanggal: $date • Jenis: $type'),
                                                  const SizedBox(height: 2),
                                                  Text('Alasan: $reason\nKeterangan: $note', style: const TextStyle(fontStyle: FontStyle.italic)),
                                                ],
                                              ),
                                              trailing: StatusBadge(status: status),
                                            );
                                          },
                                        ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
