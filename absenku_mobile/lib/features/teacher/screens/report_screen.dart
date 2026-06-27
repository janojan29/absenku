import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../models/user.dart';
import '../../../models/attendance.dart';

class ReportScreen extends StatefulWidget {
  const ReportScreen({super.key});

  @override
  State<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends State<ReportScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Detail Filter States (Rekap Absen)
  String _detailClassRoomId = '';
  String _detailStatus = ''; // '', 'present', 'late', 'leave', 'sick', 'absent'
  DateTime _detailStartDate = DateTime.now().subtract(const Duration(days: 7));
  DateTime _detailEndDate = DateTime.now();

  // Summary Filter States (Rekap Keterangan)
  String _summaryClassRoomId = '';
  DateTime _summaryStartDate = DateTime.now().subtract(const Duration(days: 7));
  DateTime _summaryEndDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();

        return Scaffold(
          backgroundColor: AppTheme.backgroundLight,
          appBar: PreferredSize(
            preferredSize: const Size.fromHeight(kToolbarHeight + 48),
            child: AppBar(
              title: const Text('Rekap Absensi'),
              bottom: TabBar(
                controller: _tabController,
                indicatorColor: Colors.white,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.white.withValues(alpha: 0.6),
                tabs: const [
                  Tab(text: 'Rekap Absen'),
                  Tab(text: 'Rekap Keterangan'),
                ],
              ),
            ),
          ),
          body: TabBarView(
            controller: _tabController,
            children: [
              _buildDetailTab(db),
              _buildSummaryTab(db),
            ],
          ),
        );
      },
    );
  }

  // --- REKAP ABSEN (DETAIL REPORT) TAB ---
  Widget _buildDetailTab(MockDatabase db) {
    // Generate all attendance records within range (both present, late, leave, and alfa)
    // To represent mock data realistically, we take all students, and for each day in range,
    // we find if there is an attendance record. If not, and it's in the past (excluding Sundays),
    // it defaults to 'absent' (Alfa) or 'unknown' depending on settings.
    List<Map<String, dynamic>> rows = [];
    
    // Loop through each day from start to end
    for (var date = _detailStartDate;
        date.isBefore(_detailEndDate.add(const Duration(days: 1)));
        date = date.add(const Duration(days: 1))) {
      
      // Skip sundays
      if (date.weekday == DateTime.sunday) continue;
      final dateOnly = DateTime(date.year, date.month, date.day);

      for (var student in db.users.where((u) => u.role == 'siswa')) {
        // Filter classroom
        if (_detailClassRoomId.isNotEmpty && student.classRoomId != _detailClassRoomId) {
          continue;
        }

        // Find attendance record
        final att = db.attendance.firstWhere(
          (a) => a.userId == student.id &&
              a.date.year == dateOnly.year &&
              a.date.month == dateOnly.month &&
              a.date.day == dateOnly.day,
          orElse: () => Attendance(id: '', userId: student.id, date: dateOnly, status: 'absent'), // Default to Alfa if past
        );

        // Filter status
        if (_detailStatus.isNotEmpty && att.status != _detailStatus) {
          continue;
        }

        final classRoom = db.classrooms.firstWhere(
          (c) => c.id == student.classRoomId,
          orElse: () => ClassRoom(id: '', name: '-', jurusan: '-'),
        );

        // Find leave request if status is leave/sick
        String leaveNote = '';
        if (att.status == 'leave' || att.status == 'sick') {
          final leave = db.leaveRequests.firstWhere(
            (l) => l.userId == student.id &&
                l.date.year == dateOnly.year &&
                l.date.month == dateOnly.month &&
                l.date.day == dateOnly.day,
            orElse: () => LeaveRequest(id: '', userId: '', type: '', date: dateOnly, reason: '', keterangan: '', status: ''),
          );
          if (leave.id.isNotEmpty) {
            leaveNote = '${leave.reason == "sick" ? "Sakit" : "Izin"} - ${leave.keterangan}';
          }
        }

        rows.add({
          'tanggal': DateFormat('dd/MM/yyyy').format(dateOnly),
          'nama': student.name,
          'kelas': classRoom.name,
          'status': att.status,
          'masuk': att.checkInAt != null ? DateFormat('HH:mm').format(att.checkInAt!) : '—',
          'pulang': att.checkOutAt != null ? DateFormat('HH:mm').format(att.checkOutAt!) : '—',
          'keterangan': leaveNote,
        });
      }
    }

    // Sort by date descending
    rows.sort((a, b) {
      final dateA = DateFormat('dd/MM/yyyy').parse(a['tanggal']);
      final dateB = DateFormat('dd/MM/yyyy').parse(b['tanggal']);
      return dateB.compareTo(dateA);
    });

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Filter card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Filter Rekap Absen', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      // Class filter
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          initialValue: _detailClassRoomId,
                          decoration: const InputDecoration(labelText: 'Kelas', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                          items: [
                            const DropdownMenuItem(value: '', child: Text('Semua Kelas')),
                            ...db.classrooms.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name))),
                          ],
                          onChanged: (val) => setState(() => _detailClassRoomId = val ?? ''),
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Status filter
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          initialValue: _detailStatus,
                          decoration: const InputDecoration(labelText: 'Status', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                          items: const [
                            DropdownMenuItem(value: '', child: Text('Semua Status')),
                            DropdownMenuItem(value: 'present', child: Text('Hadir')),
                            DropdownMenuItem(value: 'late', child: Text('Terlambat')),
                            DropdownMenuItem(value: 'leave', child: Text('Izin')),
                            DropdownMenuItem(value: 'sick', child: Text('Sakit')),
                            DropdownMenuItem(value: 'absent', child: Text('Alfa')),
                          ],
                          onChanged: (val) => setState(() => _detailStatus = val ?? ''),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  // Date range picker
                  Row(
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: () async {
                            final selected = await showDatePicker(
                              context: context,
                              initialDate: _detailStartDate,
                              firstDate: DateTime(2025),
                              lastDate: DateTime(2030),
                            );
                            if (selected != null) {
                              setState(() => _detailStartDate = selected);
                            }
                          },
                          child: InputDecorator(
                            decoration: const InputDecoration(labelText: 'Dari Tanggal', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                            child: Text(DateFormat('dd/MM/yyyy').format(_detailStartDate), style: const TextStyle(fontSize: 12)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: InkWell(
                          onTap: () async {
                            final selected = await showDatePicker(
                              context: context,
                              initialDate: _detailEndDate,
                              firstDate: DateTime(2025),
                              lastDate: DateTime(2030),
                            );
                            if (selected != null) {
                              setState(() => _detailEndDate = selected);
                            }
                          },
                          child: InputDecorator(
                            decoration: const InputDecoration(labelText: 'Sampai Tanggal', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                            child: Text(DateFormat('dd/MM/yyyy').format(_detailEndDate), style: const TextStyle(fontSize: 12)),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () {
                            setState(() {
                              _detailClassRoomId = '';
                              _detailStatus = '';
                              _detailStartDate = DateTime.now().subtract(const Duration(days: 7));
                              _detailEndDate = DateTime.now();
                            });
                          },
                          child: const Text('RESET'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          // Export buttons
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _showExportAlert('Excel'),
                  icon: const Icon(Icons.download, size: 16),
                  label: const Text('Ekspor Excel', style: TextStyle(fontSize: 12)),
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _showExportAlert('PDF'),
                  icon: const Icon(Icons.picture_as_pdf, size: 16),
                  label: const Text('Ekspor PDF', style: TextStyle(fontSize: 12)),
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Results list
          Text('Hasil Rekap (${rows.length})', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 8),
          if (rows.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: Text('Tidak ada data rekap absensi.', style: TextStyle(color: AppTheme.textMuted))),
              ),
            )
          else
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: rows.length,
              itemBuilder: (context, index) {
                final row = rows[index];
                
                Color statusColor;
                String statusText;
                switch (row['status']) {
                  case 'present':
                    statusColor = AppTheme.statusPresent;
                    statusText = 'Hadir';
                    break;
                  case 'late':
                    statusColor = AppTheme.statusLate;
                    statusText = 'Terlambat';
                    break;
                  case 'leave':
                    statusColor = AppTheme.statusLeave;
                    statusText = 'Izin';
                    break;
                  case 'sick':
                    statusColor = AppTheme.statusLeave;
                    statusText = 'Sakit';
                    break;
                  case 'absent':
                    statusColor = AppTheme.statusAbsent;
                    statusText = 'Alfa';
                    break;
                  default:
                    statusColor = Colors.grey;
                    statusText = row['status'].toUpperCase();
                }

                return Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(row['nama'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: statusColor.withValues(alpha: 0.1),
                                border: Border.all(color: statusColor.withValues(alpha: 0.3)),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                statusText,
                                style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 10),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Kelas: ${row['kelas']} · Tanggal: ${row['tanggal']}',
                          style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                        ),
                        Text(
                          'Jam Masuk: ${row['masuk']} · Jam Pulang: ${row['pulang']}',
                          style: const TextStyle(fontSize: 12, color: AppTheme.textDark),
                        ),
                        if (row['keterangan'] != '')
                          Padding(
                            padding: const EdgeInsets.only(top: 4.0),
                            child: Text(
                              'Info Izin: ${row['keterangan']}',
                              style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: AppTheme.textMuted),
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

  // --- REKAP KETERANGAN (SUMMARY TOTALS) TAB ---
  Widget _buildSummaryTab(MockDatabase db) {
    // Generate summarized data for all students in the classroom
    List<Map<String, dynamic>> summaryRows = [];

    final students = db.users.where((u) => u.role == 'siswa').toList();

    for (var student in students) {
      if (_summaryClassRoomId.isNotEmpty && student.classRoomId != _summaryClassRoomId) {
        continue;
      }

      int present = 0;
      int late = 0;
      int leave = 0;
      int absent = 0;

      // Scan days in range
      for (var date = _summaryStartDate;
          date.isBefore(_summaryEndDate.add(const Duration(days: 1)));
          date = date.add(const Duration(days: 1))) {
        
        if (date.weekday == DateTime.sunday) continue;
        final dateOnly = DateTime(date.year, date.month, date.day);

        final att = db.attendance.firstWhere(
          (a) => a.userId == student.id &&
              a.date.year == dateOnly.year &&
              a.date.month == dateOnly.month &&
              a.date.day == dateOnly.day,
          orElse: () => Attendance(id: '', userId: student.id, date: dateOnly, status: 'absent'), // default to Alfa if past
        );

        if (att.status == 'present') present++;
        if (att.status == 'late') late++;
        if (att.status == 'leave' || att.status == 'sick') leave++;
        if (att.status == 'absent') absent++;
      }

      final classRoom = db.classrooms.firstWhere(
        (c) => c.id == student.classRoomId,
        orElse: () => ClassRoom(id: '', name: '-', jurusan: '-'),
      );

      summaryRows.add({
        'nama': student.name,
        'kelas': classRoom.name,
        'jurusan': classRoom.jurusan,
        'present': present,
        'late': late,
        'leave': leave,
        'absent': absent,
      });
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Filter Card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Filter Rekap Keterangan', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: _summaryClassRoomId,
                    decoration: const InputDecoration(labelText: 'Kelas', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                    items: [
                      const DropdownMenuItem(value: '', child: Text('Semua Kelas')),
                      ...db.classrooms.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name))),
                    ],
                    onChanged: (val) => setState(() => _summaryClassRoomId = val ?? ''),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: () async {
                            final selected = await showDatePicker(
                              context: context,
                              initialDate: _summaryStartDate,
                              firstDate: DateTime(2025),
                              lastDate: DateTime(2030),
                            );
                            if (selected != null) {
                              setState(() => _summaryStartDate = selected);
                            }
                          },
                          child: InputDecorator(
                            decoration: const InputDecoration(labelText: 'Dari Tanggal', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                            child: Text(DateFormat('dd/MM/yyyy').format(_summaryStartDate), style: const TextStyle(fontSize: 12)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: InkWell(
                          onTap: () async {
                            final selected = await showDatePicker(
                              context: context,
                              initialDate: _summaryEndDate,
                              firstDate: DateTime(2025),
                              lastDate: DateTime(2030),
                            );
                            if (selected != null) {
                              setState(() => _summaryEndDate = selected);
                            }
                          },
                          child: InputDecorator(
                            decoration: const InputDecoration(labelText: 'Sampai Tanggal', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                            child: Text(DateFormat('dd/MM/yyyy').format(_summaryEndDate), style: const TextStyle(fontSize: 12)),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () {
                            setState(() {
                              _summaryClassRoomId = '';
                              _summaryStartDate = DateTime.now().subtract(const Duration(days: 7));
                              _summaryEndDate = DateTime.now();
                            });
                          },
                          child: const Text('RESET'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          // Results list
          Text('Ringkasan Akumulasi (${summaryRows.length} Siswa)', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 8),
          if (summaryRows.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: Text('Tidak ada data.', style: TextStyle(color: AppTheme.textMuted))),
              ),
            )
          else
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: summaryRows.length,
              itemBuilder: (context, index) {
                final row = summaryRows[index];

                return Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(row['nama'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        Text(
                          'Kelas: ${row['kelas']} · Jurusan: ${row['jurusan']}',
                          style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            _buildSummaryCountItem('Hadir', row['present'], AppTheme.statusPresent),
                            _buildSummaryCountItem('Telat', row['late'], AppTheme.statusLate),
                            _buildSummaryCountItem('Izin', row['leave'], AppTheme.statusLeave),
                            _buildSummaryCountItem('Alfa', row['absent'], AppTheme.statusAbsent),
                          ],
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

  Widget _buildSummaryCountItem(String label, int count, Color color) {
    return Container(
      width: 70,
      padding: const EdgeInsets.symmetric(vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.06),
        border: Border.all(color: color.withValues(alpha: 0.2)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Text(label, style: TextStyle(fontSize: 10, color: color, fontWeight: FontWeight.bold)),
          const SizedBox(height: 2),
          Text('$count', style: TextStyle(fontSize: 16, color: color, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  void _showExportAlert(String type) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text('Ekspor Rekap $type'),
          content: Text('Laporan rekap absensi berhasil di-ekspor ke format $type dan tersimpan di folder Unduhan.'),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(context);
              },
              child: const Text('OK'),
            ),
          ],
        );
      },
    );
  }
}
