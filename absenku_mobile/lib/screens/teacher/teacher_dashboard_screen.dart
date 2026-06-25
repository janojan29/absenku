// lib/screens/teacher/teacher_dashboard_screen.dart
import 'package:flutter/material.dart';
import '../../core/app_colors.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/stat_card.dart';
import '../../widgets/status_badge.dart';
import '../../services/api_service.dart';

class TeacherDashboardScreen extends StatefulWidget {
  const TeacherDashboardScreen({super.key});

  @override
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
  int? _selectedClassRoomId;
  List<dynamic> _classrooms = [];
  List<dynamic> _students = [];
  Map<String, dynamic> _counts = {
    'present': 0,
    'late': 0,
    'leave': 0,
    'unknown': 0,
  };
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final data = await ApiService.getTeacherDashboard(classRoomId: _selectedClassRoomId);
      setState(() {
        _counts = data['counts'] as Map<String, dynamic>? ?? _counts;
        _students = data['students'] as List<dynamic>? ?? [];
        _classrooms = data['classrooms'] as List<dynamic>? ?? [];
        
        if (_selectedClassRoomId == null && data['class_room_id'] != null) {
          _selectedClassRoomId = data['class_room_id'] as int;
        }
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

  @override
  Widget build(BuildContext context) {
    final presentCount = _counts['present'] ?? 0;
    final lateCount = _counts['late'] ?? 0;
    final leaveCount = _counts['leave'] ?? 0;
    final unknownCount = _counts['unknown'] ?? 0;

    return AppScaffold(
      title: 'Dashboard Guru',
      child: _isLoading && _classrooms.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Monitoring Kelas',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
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
                          ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      childAspectRatio: 1.5,
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                      children: [
                        StatCard(label: 'Hadir', value: presentCount, valueColor: AppColors.present),
                        StatCard(label: 'Terlambat', value: lateCount, valueColor: AppColors.lateStatus),
                        StatCard(label: 'Belum Absen', value: unknownCount, valueColor: AppColors.space500),
                        StatCard(label: 'Izin/Sakit', value: leaveCount, valueColor: AppColors.leave),
                      ],
                    ),
                    const SizedBox(height: 24),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Daftar Absensi Siswa (${_classrooms.firstWhere((c) => c['id'] == _selectedClassRoomId, orElse: () => {'name': ''})['name']})',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 16),
                            _isLoading
                                ? const Padding(
                                    padding: EdgeInsets.symmetric(vertical: 24.0),
                                    child: Center(child: CircularProgressIndicator()),
                                  )
                                : _students.isEmpty
                                    ? const Padding(
                                        padding: EdgeInsets.symmetric(vertical: 24.0),
                                        child: Center(child: Text('Tidak ada siswa di kelas ini')),
                                      )
                                    : SingleChildScrollView(
                                        scrollDirection: Axis.horizontal,
                                        child: DataTable(
                                          columns: const [
                                            DataColumn(label: Text('Nama Siswa')),
                                            DataColumn(label: Text('Status')),
                                            DataColumn(label: Text('Keterangan')),
                                          ],
                                          rows: _students.map<DataRow>((student) {
                                            final name = student['name'] ?? '-';
                                            final status = student['status'] ?? 'unknown';
                                            final keterangan = student['keterangan'] ?? '-';
                                            return DataRow(
                                              cells: [
                                                DataCell(Text(name)),
                                                DataCell(StatusBadge(status: status)),
                                                DataCell(Text(keterangan, style: const TextStyle(fontSize: 12))),
                                              ],
                                            );
                                          }).toList(),
                                        ),
                                      ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
