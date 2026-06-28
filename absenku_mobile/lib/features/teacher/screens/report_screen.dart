import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';

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

  // Loaded data
  bool _loadingDetail = true;
  bool _loadingSummary = true;
  List<dynamic> _detailRows = [];
  List<dynamic> _summaryRows = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      if (_tabController.indexIsChanging) return;
      if (_tabController.index == 0) {
        _loadDetailReport();
      } else {
        _loadSummaryReport();
      }
    });

    _loadDetailReport();
    _loadSummaryReport();
  }

  Future<void> _loadDetailReport() async {
    setState(() {
      _loadingDetail = true;
    });

    final db = MockDatabase();
    if (db.classrooms.isEmpty) {
      await db.fetchClassrooms();
    }

    final startStr = DateFormat('yyyy-MM-dd').format(_detailStartDate);
    final endStr = DateFormat('yyyy-MM-dd').format(_detailEndDate);

    final result = await db.fetchTeacherReport(
      classRoomId: _detailClassRoomId,
      startDate: startStr,
      endDate: endStr,
      status: _detailStatus,
    );

    if (mounted) {
      setState(() {
        _detailRows = result;
        _loadingDetail = false;
      });
    }
  }

  Future<void> _loadSummaryReport() async {
    setState(() {
      _loadingSummary = true;
    });

    final db = MockDatabase();
    if (db.classrooms.isEmpty) {
      await db.fetchClassrooms();
    }

    final startStr = DateFormat('yyyy-MM-dd').format(_summaryStartDate);
    final endStr = DateFormat('yyyy-MM-dd').format(_summaryEndDate);

    // Summary tab shows totals, so we do not pass status parameter to fetch everything in range
    final result = await db.fetchTeacherReport(
      classRoomId: _summaryClassRoomId,
      startDate: startStr,
      endDate: endStr,
    );

    // Group the rows by student Name
    final Map<String, Map<String, dynamic>> studentSummary = {};

    for (final row in result) {
      final String name = row['Nama'] as String? ?? '';
      final String className = row['Kelas'] as String? ?? '-';
      final String jurusan = row['Jurusan'] as String? ?? '-';
      final String status = row['Status'] as String? ?? '';

      if (!studentSummary.containsKey(name)) {
        studentSummary[name] = {
          'nama': name,
          'kelas': className,
          'jurusan': jurusan,
          'present': 0,
          'late': 0,
          'leave': 0,
          'absent': 0,
        };
      }

      final summary = studentSummary[name]!;
      if (status.contains('Hadir')) {
        summary['present'] = (summary['present'] as int) + 1;
      } else if (status.contains('Terlambat')) {
        summary['late'] = (summary['late'] as int) + 1;
      } else if (status.contains('Izin') || status.contains('Sakit')) {
        summary['leave'] = (summary['leave'] as int) + 1;
      } else if (status.contains('Alfa')) {
        summary['absent'] = (summary['absent'] as int) + 1;
      }
    }

    if (mounted) {
      setState(() {
        _summaryRows = studentSummary.values.toList();
        _loadingSummary = false;
      });
    }
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
                          onChanged: (val) {
                            setState(() => _detailClassRoomId = val ?? '');
                            _loadDetailReport();
                          },
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
                          onChanged: (val) {
                            setState(() => _detailStatus = val ?? '');
                            _loadDetailReport();
                          },
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
                              _loadDetailReport();
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
                              _loadDetailReport();
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
                            _loadDetailReport();
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
          Text('Hasil Rekap (${_detailRows.length})', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 8),
          if (_loadingDetail)
            const Center(child: Padding(
              padding: EdgeInsets.all(24.0),
              child: CircularProgressIndicator(),
            ))
          else if (_detailRows.isEmpty)
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
              itemCount: _detailRows.length,
              itemBuilder: (context, index) {
                final row = _detailRows[index] as Map<String, dynamic>;
                
                final String statusText = row['Status'] as String? ?? '-';
                Color statusColor;
                if (statusText.contains('Hadir')) {
                  statusColor = AppTheme.statusPresent;
                } else if (statusText.contains('Terlambat')) {
                  statusColor = AppTheme.statusLate;
                } else if (statusText.contains('Izin') || statusText.contains('Sakit')) {
                  statusColor = AppTheme.statusLeave;
                } else if (statusText.contains('Alfa')) {
                  statusColor = AppTheme.statusAbsent;
                } else {
                  statusColor = Colors.grey;
                }

                final tanggal = row['Tanggal'] != null 
                    ? DateFormat('dd/MM/yyyy').format(DateTime.parse(row['Tanggal'])) 
                    : '-';

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
                            Text(row['Nama'] as String? ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
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
                          'Kelas: ${row['Kelas'] ?? "-"} · Tanggal: $tanggal',
                          style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                        ),
                        Text(
                          'Jam Masuk: ${row['Masuk'] ?? "-"} · Jam Pulang: ${row['Pulang'] ?? "-"}',
                          style: const TextStyle(fontSize: 12, color: AppTheme.textDark),
                        ),
                        if (row['Keterangan Izin'] != null && row['Keterangan Izin'] != '-')
                          Padding(
                            padding: const EdgeInsets.only(top: 4.0),
                            child: Text(
                              'Info Izin: ${row['Keterangan Izin']}',
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
                    onChanged: (val) {
                      setState(() => _summaryClassRoomId = val ?? '');
                      _loadSummaryReport();
                    },
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
                              _loadSummaryReport();
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
                              _loadSummaryReport();
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
                            _loadSummaryReport();
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
          Text('Ringkasan Akumulasi (${_summaryRows.length} Siswa)', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 8),
          if (_loadingSummary)
            const Center(child: Padding(
              padding: EdgeInsets.all(24.0),
              child: CircularProgressIndicator(),
            ))
          else if (_summaryRows.isEmpty)
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
              itemCount: _summaryRows.length,
              itemBuilder: (context, index) {
                final row = _summaryRows[index];

                return Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(row['nama'] as String? ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        Text(
                          'Kelas: ${row['kelas'] ?? "-"} · Jurusan: ${row['jurusan'] ?? "-"}',
                          style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            _buildSummaryCountItem('Hadir', row['present'] as int, AppTheme.statusPresent),
                            _buildSummaryCountItem('Telat', row['late'] as int, AppTheme.statusLate),
                            _buildSummaryCountItem('Izin', row['leave'] as int, AppTheme.statusLeave),
                            _buildSummaryCountItem('Alfa', row['absent'] as int, AppTheme.statusAbsent),
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
