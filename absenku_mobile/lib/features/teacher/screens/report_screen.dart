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

  String _detailClassRoomId = '';
  String _detailStatus = '';
  DateTime _detailStartDate = DateTime.now().subtract(const Duration(days: 7));
  DateTime _detailEndDate = DateTime.now();

  String _summaryClassRoomId = '';
  DateTime _summaryStartDate = DateTime.now().subtract(const Duration(days: 7));
  DateTime _summaryEndDate = DateTime.now();

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
    setState(() => _loadingDetail = true);
    final db = MockDatabase();
    if (db.classrooms.isEmpty) await db.fetchClassrooms();
    final result = await db.fetchTeacherReport(
      classRoomId: _detailClassRoomId,
      startDate: DateFormat('yyyy-MM-dd').format(_detailStartDate),
      endDate: DateFormat('yyyy-MM-dd').format(_detailEndDate),
      status: _detailStatus,
    );
    if (mounted) setState(() { _detailRows = result; _loadingDetail = false; });
  }

  Future<void> _loadSummaryReport() async {
    setState(() => _loadingSummary = true);
    final db = MockDatabase();
    if (db.classrooms.isEmpty) await db.fetchClassrooms();
    final result = await db.fetchTeacherReport(
      classRoomId: _summaryClassRoomId,
      startDate: DateFormat('yyyy-MM-dd').format(_summaryStartDate),
      endDate: DateFormat('yyyy-MM-dd').format(_summaryEndDate),
    );
    final Map<String, Map<String, dynamic>> studentSummary = {};
    for (final row in result) {
      final String name = row['Nama'] as String? ?? '';
      final String className = row['Kelas'] as String? ?? '-';
      final String jurusan = row['Jurusan'] as String? ?? '-';
      final String status = row['Status'] as String? ?? '';
      if (!studentSummary.containsKey(name)) {
        studentSummary[name] = {'nama': name, 'kelas': className, 'jurusan': jurusan, 'present': 0, 'late': 0, 'leave': 0, 'absent': 0};
      }
      final s = studentSummary[name]!;
      if (status.contains('Hadir')) {
        s['present'] = (s['present'] as int) + 1;
      } else if (status.contains('Terlambat')) {
        s['late'] = (s['late'] as int) + 1;
      } else if (status.contains('Izin') || status.contains('Sakit')) {
        s['leave'] = (s['leave'] as int) + 1;
      } else if (status.contains('Alfa')) {
        s['absent'] = (s['absent'] as int) + 1;
      }
    }
    if (mounted) setState(() { _summaryRows = studentSummary.values.toList(); _loadingSummary = false; });
  }

  @override
  void dispose() { _tabController.dispose(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        return Column(
          children: [
            // Tab bar
            Container(
              color: AppTheme.primaryNavy,
              child: TabBar(
                controller: _tabController,
                indicatorColor: Colors.white,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.white.withValues(alpha: 0.6),
                tabs: const [Tab(text: 'Rekap Absen'), Tab(text: 'Rekap Keterangan')],
              ),
            ),
            Expanded(
              child: TabBarView(
                controller: _tabController,
                children: [_buildDetailTab(db), _buildSummaryTab(db)],
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildDetailTab(MockDatabase db) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        // Filters
        Container(
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('Filter Rekap Absen', style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: DropdownButtonFormField<String>(
                initialValue: _detailClassRoomId, isExpanded: true,
                decoration: const InputDecoration(labelText: 'Kelas', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                items: [const DropdownMenuItem(value: '', child: Text('Semua Kelas')), ...db.classrooms.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))],
                onChanged: (val) { setState(() => _detailClassRoomId = val ?? ''); _loadDetailReport(); },
              )),
              const SizedBox(width: 12),
              Expanded(child: DropdownButtonFormField<String>(
                initialValue: _detailStatus, isExpanded: true,
                decoration: const InputDecoration(labelText: 'Status', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
                items: const [
                  DropdownMenuItem(value: '', child: Text('Semua Status')),
                  DropdownMenuItem(value: 'present', child: Text('Hadir')),
                  DropdownMenuItem(value: 'late', child: Text('Terlambat')),
                  DropdownMenuItem(value: 'leave', child: Text('Izin')),
                  DropdownMenuItem(value: 'sick', child: Text('Sakit')),
                  DropdownMenuItem(value: 'absent', child: Text('Alfa')),
                ],
                onChanged: (val) { setState(() => _detailStatus = val ?? ''); _loadDetailReport(); },
              )),
            ]),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: _buildDatePicker('Dari Tanggal', _detailStartDate, (d) { setState(() => _detailStartDate = d); _loadDetailReport(); })),
              const SizedBox(width: 12),
              Expanded(child: _buildDatePicker('Sampai Tanggal', _detailEndDate, (d) { setState(() => _detailEndDate = d); _loadDetailReport(); })),
            ]),
            const SizedBox(height: 16),
            SizedBox(width: double.infinity, child: OutlinedButton(
              onPressed: () {
                setState(() { _detailClassRoomId = ''; _detailStatus = ''; _detailStartDate = DateTime.now().subtract(const Duration(days: 7)); _detailEndDate = DateTime.now(); });
                _loadDetailReport();
              },
              child: const Text('RESET'),
            )),
          ]),
        ),
        const SizedBox(height: 16),
        Text('Hasil Rekap (${_detailRows.length})', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
        const SizedBox(height: 8),
        if (_loadingDetail)
          const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
        else if (_detailRows.isEmpty)
          Container(
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
            padding: const EdgeInsets.all(24),
            child: const Center(child: Text('Tidak ada data rekap absensi.', style: TextStyle(color: AppTheme.textMuted))),
          )
        else
          ..._detailRows.map((row) {
            final r = row as Map<String, dynamic>;
            final statusText = r['Status'] as String? ?? '-';
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

            String tanggal;
            try { tanggal = DateFormat('dd/MM/yyyy').format(DateTime.parse(r['Tanggal'])); } catch (_) { tanggal = r['Tanggal']?.toString() ?? '-'; }

            return Container(
              margin: const EdgeInsets.only(bottom: 8),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppTheme.borderLight)),
              padding: const EdgeInsets.all(12),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                  Text(r['Nama'] as String? ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.1), border: Border.all(color: statusColor.withValues(alpha: 0.3)), borderRadius: BorderRadius.circular(12)),
                    child: Text(statusText, style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 10)),
                  ),
                ]),
                const SizedBox(height: 4),
                Text('Kelas: ${r['Kelas'] ?? "-"} · Tanggal: $tanggal', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                Text('Masuk: ${r['Masuk'] ?? "-"} · Pulang: ${r['Pulang'] ?? "-"}', style: const TextStyle(fontSize: 12, color: AppTheme.textDark)),
                if (r['Keterangan Izin'] != null && r['Keterangan Izin'] != '-')
                  Padding(padding: const EdgeInsets.only(top: 4), child: Text('Info Izin: ${r['Keterangan Izin']}',
                      style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: AppTheme.textMuted))),
              ]),
            );
          }),
      ]),
    );
  }

  Widget _buildSummaryTab(MockDatabase db) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Container(
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('Filter Rekap Keterangan', style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _summaryClassRoomId, isExpanded: true,
              decoration: const InputDecoration(labelText: 'Kelas', contentPadding: EdgeInsets.symmetric(horizontal: 8)),
              items: [const DropdownMenuItem(value: '', child: Text('Semua Kelas')), ...db.classrooms.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))],
              onChanged: (val) { setState(() => _summaryClassRoomId = val ?? ''); _loadSummaryReport(); },
            ),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: _buildDatePicker('Dari Tanggal', _summaryStartDate, (d) { setState(() => _summaryStartDate = d); _loadSummaryReport(); })),
              const SizedBox(width: 12),
              Expanded(child: _buildDatePicker('Sampai Tanggal', _summaryEndDate, (d) { setState(() => _summaryEndDate = d); _loadSummaryReport(); })),
            ]),
            const SizedBox(height: 16),
            SizedBox(width: double.infinity, child: OutlinedButton(
              onPressed: () {
                setState(() { _summaryClassRoomId = ''; _summaryStartDate = DateTime.now().subtract(const Duration(days: 7)); _summaryEndDate = DateTime.now(); });
                _loadSummaryReport();
              },
              child: const Text('RESET'),
            )),
          ]),
        ),
        const SizedBox(height: 16),
        Text('Ringkasan Akumulasi (${_summaryRows.length} Siswa)', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
        const SizedBox(height: 8),
        if (_loadingSummary)
          const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
        else if (_summaryRows.isEmpty)
          Container(
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
            padding: const EdgeInsets.all(24),
            child: const Center(child: Text('Tidak ada data.', style: TextStyle(color: AppTheme.textMuted))),
          )
        else
          ..._summaryRows.map((row) => Container(
            margin: const EdgeInsets.only(bottom: 10),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppTheme.borderLight)),
            padding: const EdgeInsets.all(14),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(row['nama'] as String? ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              Text('Kelas: ${row['kelas'] ?? "-"} · Jurusan: ${row['jurusan'] ?? "-"}', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
              const SizedBox(height: 10),
              Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                _buildCountItem('Hadir', row['present'] as int, AppTheme.statusPresent),
                _buildCountItem('Telat', row['late'] as int, AppTheme.statusLate),
                _buildCountItem('Izin', row['leave'] as int, AppTheme.statusLeave),
                _buildCountItem('Alfa', row['absent'] as int, AppTheme.statusAbsent),
              ]),
            ]),
          )),
      ]),
    );
  }

  Widget _buildDatePicker(String label, DateTime date, ValueChanged<DateTime> onSelected) {
    return InkWell(
      onTap: () async {
        final s = await showDatePicker(context: context, initialDate: date, firstDate: DateTime(2025), lastDate: DateTime(2030));
        if (s != null) onSelected(s);
      },
      child: InputDecorator(
        decoration: InputDecoration(labelText: label, contentPadding: const EdgeInsets.symmetric(horizontal: 8)),
        child: Text(DateFormat('dd/MM/yyyy').format(date), style: const TextStyle(fontSize: 12)),
      ),
    );
  }

  Widget _buildCountItem(String label, int count, Color color) {
    return Container(
      width: 70,
      padding: const EdgeInsets.symmetric(vertical: 8),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.06), border: Border.all(color: color.withValues(alpha: 0.2)), borderRadius: BorderRadius.circular(8)),
      child: Column(children: [
        Text(label, style: TextStyle(fontSize: 10, color: color, fontWeight: FontWeight.bold)),
        const SizedBox(height: 2),
        Text('$count', style: TextStyle(fontSize: 16, color: color, fontWeight: FontWeight.bold)),
      ]),
    );
  }
}
