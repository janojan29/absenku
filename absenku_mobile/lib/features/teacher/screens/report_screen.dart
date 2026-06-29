import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../services/api_client.dart';
import '../../../core/widgets/custom_expand_menu.dart';
import '../../../core/utils/download_file.dart';


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
  int _detailCurrentPage = 1;
  int _detailLastPage = 1;

  List<dynamic> _summaryRows = [];
  int _summaryCurrentPage = 1;
  int _summaryLastPage = 1;

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

  Future<void> _loadDetailReport({int page = 1}) async {
    setState(() => _loadingDetail = true);
    final db = MockDatabase();
    if (db.classrooms.isEmpty) await db.fetchClassrooms();
    final result = await db.fetchTeacherReport(
      classRoomId: _detailClassRoomId,
      startDate: DateFormat('yyyy-MM-dd').format(_detailStartDate),
      endDate: DateFormat('yyyy-MM-dd').format(_detailEndDate),
      status: _detailStatus,
      page: page,
    );
    if (mounted) {
      setState(() { 
        _detailRows = result['rows'] as List<dynamic>? ?? []; 
        final meta = result['meta']?['pagination'] as Map<String, dynamic>? ?? {};
        _detailCurrentPage = meta['current_page'] as int? ?? 1;
        _detailLastPage = meta['last_page'] as int? ?? 1;
        _loadingDetail = false; 
      });
    }
  }

  Future<void> _loadSummaryReport({int page = 1}) async {
    setState(() => _loadingSummary = true);
    final db = MockDatabase();
    if (db.classrooms.isEmpty) await db.fetchClassrooms();
    final result = await db.fetchTeacherSummaryReport(
      classRoomId: _summaryClassRoomId,
      startDate: DateFormat('yyyy-MM-dd').format(_summaryStartDate),
      endDate: DateFormat('yyyy-MM-dd').format(_summaryEndDate),
      page: page,
    );
    final rows = result['rows'] as List<dynamic>? ?? [];
    final meta = result['meta']?['pagination'] as Map<String, dynamic>? ?? {};

    if (mounted) {
      setState(() { 
        _summaryRows = rows.map((row) {
          final r = row as Map<String, dynamic>;
          return {
            'nama': r['Nama'] ?? '-',
            'kelas': r['Kelas'] ?? '-',
            'jurusan': r['Jurusan'] ?? '-',
            'present': r['Hadir'] ?? 0,
            'late': r['Telat'] ?? 0,
            'leave': r['Izin'] ?? 0,
            'absent': r['Alfa'] ?? 0,
          };
        }).toList(); 
        _summaryCurrentPage = meta['current_page'] as int? ?? 1;
        _summaryLastPage = meta['last_page'] as int? ?? 1;
        _loadingSummary = false; 
      });
    }
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
              Expanded(child: CustomExpandMenu(
                title: 'Kelas',
                subtitle: _detailClassRoomId.isEmpty
                    ? 'Semua Kelas'
                    : db.classrooms.firstWhere((c) => c.id == _detailClassRoomId, orElse: () => db.classrooms.first).name,
                items: [
                  const {'value': '', 'label': 'Semua Kelas'},
                  ...db.classrooms.map((c) => {'value': c.id, 'label': c.name}),
                ],
                selectedValue: _detailClassRoomId,
                onChanged: (val) { setState(() => _detailClassRoomId = val); _loadDetailReport(); },
              )),
              const SizedBox(width: 12),
              Expanded(child: CustomExpandMenu(
                title: 'Status',
                subtitle: _detailStatus.isEmpty
                    ? 'Semua Status'
                    : _detailStatus == 'present' ? 'Hadir'
                    : _detailStatus == 'late' ? 'Terlambat'
                    : _detailStatus == 'leave' ? 'Izin'
                    : _detailStatus == 'sick' ? 'Sakit'
                    : _detailStatus == 'absent' ? 'Alfa' : _detailStatus,
                items: const [
                  {'value': '', 'label': 'Semua Status'},
                  {'value': 'present', 'label': 'Hadir'},
                  {'value': 'late', 'label': 'Terlambat'},
                  {'value': 'leave', 'label': 'Izin'},
                  {'value': 'sick', 'label': 'Sakit'},
                  {'value': 'absent', 'label': 'Alfa'},
                ],
                selectedValue: _detailStatus,
                onChanged: (val) { setState(() => _detailStatus = val); _loadDetailReport(); },
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
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: ElevatedButton.icon(
                onPressed: () => _downloadReport('excel', false),
                icon: const Icon(Icons.table_chart, size: 18),
                label: const Text('Ekspor Excel'),
                style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
              )),
              const SizedBox(width: 12),
              Expanded(child: ElevatedButton.icon(
                onPressed: () => _downloadReport('pdf', false),
                icon: const Icon(Icons.picture_as_pdf, size: 18),
                label: const Text('Ekspor PDF'),
                style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
              )),
            ]),
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
                Row(children: [
                  Expanded(
                    child: Text(r['Nama'] as String? ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13), overflow: TextOverflow.ellipsis),
                  ),
                  const SizedBox(width: 8),
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
        if (!_loadingDetail && _detailRows.isNotEmpty && _detailLastPage > 1)
          _buildPaginationRow(
            currentPage: _detailCurrentPage,
            lastPage: _detailLastPage,
            onPageChanged: (page) => _loadDetailReport(page: page),
          ),
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
            CustomExpandMenu(
              title: 'Kelas',
              subtitle: _summaryClassRoomId.isEmpty
                  ? 'Semua Kelas'
                  : db.classrooms.firstWhere((c) => c.id == _summaryClassRoomId, orElse: () => db.classrooms.first).name,
              items: [
                const {'value': '', 'label': 'Semua Kelas'},
                ...db.classrooms.map((c) => {'value': c.id, 'label': c.name}),
              ],
              selectedValue: _summaryClassRoomId,
              onChanged: (val) { setState(() => _summaryClassRoomId = val); _loadSummaryReport(); },
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
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: ElevatedButton.icon(
                onPressed: () => _downloadReport('excel', true),
                icon: const Icon(Icons.table_chart, size: 18),
                label: const Text('Ekspor Excel'),
                style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
              )),
              const SizedBox(width: 12),
              Expanded(child: ElevatedButton.icon(
                onPressed: () => _downloadReport('pdf', true),
                icon: const Icon(Icons.picture_as_pdf, size: 18),
                label: const Text('Ekspor PDF'),
                style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
              )),
            ]),
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
              Row(children: [
                Expanded(child: _buildCountItem('Hadir', row['present'] as int, AppTheme.statusPresent)),
                const SizedBox(width: 6),
                Expanded(child: _buildCountItem('Telat', row['late'] as int, AppTheme.statusLate)),
                const SizedBox(width: 6),
                Expanded(child: _buildCountItem('Izin', row['leave'] as int, AppTheme.statusLeave)),
                const SizedBox(width: 6),
                Expanded(child: _buildCountItem('Alfa', row['absent'] as int, AppTheme.statusAbsent)),
              ]),
            ]),
          )),
        if (!_loadingSummary && _summaryRows.isNotEmpty && _summaryLastPage > 1)
          _buildPaginationRow(
            currentPage: _summaryCurrentPage,
            lastPage: _summaryLastPage,
            onPageChanged: (page) => _loadSummaryReport(page: page),
          ),
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
      padding: const EdgeInsets.symmetric(vertical: 8),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.06), border: Border.all(color: color.withValues(alpha: 0.2)), borderRadius: BorderRadius.circular(8)),
      child: Column(children: [
        Text(label, style: TextStyle(fontSize: 10, color: color, fontWeight: FontWeight.bold)),
        const SizedBox(height: 2),
        Text('$count', style: TextStyle(fontSize: 16, color: color, fontWeight: FontWeight.bold)),
      ]),
    );
  }

  Widget _buildPaginationRow({required int currentPage, required int lastPage, required Function(int) onPageChanged}) {
    return Padding(
      padding: const EdgeInsets.only(top: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            icon: const Icon(Icons.chevron_left),
            onPressed: currentPage > 1 ? () => onPageChanged(currentPage - 1) : null,
          ),
          Text('Halaman $currentPage dari $lastPage', style: const TextStyle(fontWeight: FontWeight.bold)),
          IconButton(
            icon: const Icon(Icons.chevron_right),
            onPressed: currentPage < lastPage ? () => onPageChanged(currentPage + 1) : null,
          ),
        ],
      ),
    );
  }

  void _downloadReport(String type, bool isSummary) async {
    try {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mengunduh laporan...')));
      
      String path = isSummary ? '/teacher/reports/attendance/summary/$type' : '/teacher/reports/attendance/$type';
      String classId = isSummary ? _summaryClassRoomId : _detailClassRoomId;
      String start = DateFormat('yyyy-MM-dd').format(isSummary ? _summaryStartDate : _detailStartDate);
      String end = DateFormat('yyyy-MM-dd').format(isSummary ? _summaryEndDate : _detailEndDate);
      
      String query = '?class_room_id=$classId&start_date=$start&end_date=$end';
      if (!isSummary && _detailStatus.isNotEmpty) {
        query += '&status=$_detailStatus';
      }

      final ext = type == 'excel' ? 'xlsx' : 'pdf';
      final fileName = 'rekap_absensi_$start.$ext';
      
      await downloadAndOpenFile(
        dio: ApiClient().dio,
        url: '$path$query',
        fileName: fileName,
      );
      
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Unduhan selesai!')));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal mengunduh: $e')));
      }
    }
  }
}
