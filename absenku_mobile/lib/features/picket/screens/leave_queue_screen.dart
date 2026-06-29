import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../services/teacher_service.dart';
import '../../../models/user.dart';
import 'package:open_file/open_file.dart';
import 'package:path_provider/path_provider.dart';
import '../../../services/api_client.dart';
class LeaveQueueScreen extends StatefulWidget {
  const LeaveQueueScreen({super.key});

  @override
  State<LeaveQueueScreen> createState() => _LeaveQueueScreenState();
}

class _LeaveQueueScreenState extends State<LeaveQueueScreen> {
  final TeacherService _teacherService = TeacherService();
  bool _initialLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    await MockDatabase().fetchLeaveQueue();
    if (mounted) setState(() => _initialLoading = false);
  }

  String _searchQuery = '';
  String _filterStatus = '';
  String _filterType = '';
  DateTime? _filterDateFrom;
  DateTime? _filterDateTo;

  final _searchController = TextEditingController();
  final _noteController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  void _resetFilters() {
    setState(() {
      _searchQuery = '';
      _filterStatus = '';
      _filterType = '';
      _filterDateFrom = null;
      _filterDateTo = null;
      _searchController.clear();
    });
  }

  void _downloadReport(String type) async {
    try {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mengunduh riwayat izin...')));
      
      String query = '?';
      if (_searchQuery.isNotEmpty) query += 'search=$_searchQuery&';
      if (_filterStatus.isNotEmpty) query += 'status=$_filterStatus&';
      if (_filterType.isNotEmpty) query += 'type=$_filterType&';
      if (_filterDateFrom != null) query += 'date_from=${DateFormat('yyyy-MM-dd').format(_filterDateFrom!)}&';
      if (_filterDateTo != null) query += 'date_to=${DateFormat('yyyy-MM-dd').format(_filterDateTo!)}&';

      final dir = await getTemporaryDirectory();
      final ext = type == 'excel' ? 'xlsx' : 'pdf';
      final startStr = _filterDateFrom != null ? DateFormat('yyyy-MM-dd').format(_filterDateFrom!) : 'all';
      final savePath = '${dir.path}/riwayat_izin_$startStr.$ext';
      
      await ApiClient().dio.download(
        '/picket/leave-requests/$type$query',
        savePath,
      );
      
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Unduhan selesai! Membuka file...')));
      }
      await OpenFile.open(savePath);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal mengunduh: $e')));
      }
    }
  }

  void _processLeave(String id, bool approve) {
    _noteController.clear();
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(approve ? 'Setujui Permohonan Izin' : 'Tolak Permohonan Izin'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(approve ? 'Berikan catatan persetujuan:' : 'Berikan alasan penolakan:',
                style: const TextStyle(fontSize: 13, color: AppTheme.textMuted)),
            const SizedBox(height: 12),
            TextField(
              controller: _noteController,
              decoration: InputDecoration(hintText: approve ? 'Contoh: ACC. Cepat sembuh.' : 'Contoh: Alasan tidak mendesak.', hintStyle: const TextStyle(fontSize: 12)),
              maxLines: 2,
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () { _noteController.clear(); Navigator.pop(context); }, child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted))),
          ElevatedButton(
            onPressed: () async {
              final db = MockDatabase();
              final officer = db.currentUser;
              if (officer == null) return;
              final note = _noteController.text;
              if (approve) {
                await _teacherService.approveLeave(id, officer.id, note);
              } else {
                await _teacherService.rejectLeave(id, officer.id, note);
              }
              _noteController.clear();
              if (context.mounted) {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                  content: Text(approve ? 'Permohonan izin disetujui!' : 'Permohonan izin ditolak!'),
                  backgroundColor: approve ? AppTheme.statusPresent : AppTheme.statusAbsent,
                ));
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: approve ? AppTheme.statusPresent : AppTheme.statusAbsent),
            child: const Text('KIRIM'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_initialLoading) return const Center(child: CircularProgressIndicator());

    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        final pendingLeaves = _teacherService.getPendingRequests();
        final processedLeaves = _teacherService.getProcessedRequests();

        final filteredLeaves = processedLeaves.where((leave) {
          final studentName = leave.userName ?? db.users.firstWhere(
            (u) => u.id == leave.userId,
            orElse: () => User(id: '', name: 'Unknown', email: '', role: 'siswa'),
          ).name;
          final matchesSearch = studentName.toLowerCase().contains(_searchQuery.toLowerCase());
          final matchesStatus = _filterStatus.isEmpty || leave.status == _filterStatus;
          final matchesType = _filterType.isEmpty || leave.type == _filterType;
          bool matchesDate = true;
          if (_filterDateFrom != null) matchesDate = matchesDate && !leave.date.isBefore(_filterDateFrom!);
          if (_filterDateTo != null) matchesDate = matchesDate && !leave.date.isAfter(_filterDateTo!.add(const Duration(days: 1)));
          return matchesSearch && matchesStatus && matchesType && matchesDate;
        }).toList();

        return RefreshIndicator(
          onRefresh: _loadData,
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Pending section
                Row(children: [
                  Container(width: 8, height: 8, decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle)),
                  const SizedBox(width: 8),
                  Text('Antrian Pending (${pendingLeaves.length})', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textDark)),
                ]),
                const SizedBox(height: 12),

                if (pendingLeaves.isEmpty)
                  Container(
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
                    padding: const EdgeInsets.all(24),
                    child: Column(children: [
                      Icon(Icons.assignment_turned_in_outlined, size: 40, color: Colors.grey[400]),
                      const SizedBox(height: 8),
                      const Text('Tidak ada permohonan izin pending.', style: TextStyle(color: AppTheme.textMuted, fontSize: 13)),
                      const Text('Semua permohonan sudah diproses.', style: TextStyle(color: AppTheme.textMuted, fontSize: 11)),
                    ]),
                  )
                else
                  ...pendingLeaves.map((leave) => _buildPendingCard(leave)),
                if (pendingLeaves.isNotEmpty && db.leavePendingLastPage > 1)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.chevron_left),
                          onPressed: db.leavePendingCurrentPage > 1 
                              ? () => db.fetchLeaveQueue(pendingPage: db.leavePendingCurrentPage - 1, historyPage: db.leaveHistoryCurrentPage) 
                              : null,
                        ),
                        Text('Hal ${db.leavePendingCurrentPage} dari ${db.leavePendingLastPage}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        IconButton(
                          icon: const Icon(Icons.chevron_right),
                          onPressed: db.leavePendingCurrentPage < db.leavePendingLastPage 
                              ? () => db.fetchLeaveQueue(pendingPage: db.leavePendingCurrentPage + 1, historyPage: db.leaveHistoryCurrentPage) 
                              : null,
                        ),
                      ],
                    ),
                  ),

                const SizedBox(height: 28),

                // History section
                Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                  Text('Riwayat Izin (${filteredLeaves.length})', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textDark)),
                  const Icon(Icons.history, color: AppTheme.textMuted),
                ]),
                const SizedBox(height: 12),

                // Filter panel
                Container(
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      TextField(controller: _searchController, onChanged: (val) => setState(() => _searchQuery = val),
                          decoration: const InputDecoration(hintText: 'Cari Nama Siswa...', prefixIcon: Icon(Icons.search), contentPadding: EdgeInsets.zero)),
                      const SizedBox(height: 12),
                      Row(children: [
                        Expanded(child: DropdownButtonFormField<String>(
                          initialValue: _filterStatus, isExpanded: true,
                          items: const [
                            DropdownMenuItem(value: '', child: Text('Semua Status')),
                            DropdownMenuItem(value: 'approved', child: Text('Disetujui')),
                            DropdownMenuItem(value: 'rejected', child: Text('Ditolak')),
                          ],
                          onChanged: (val) => setState(() => _filterStatus = val ?? ''),
                          decoration: const InputDecoration(labelText: 'Status', contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4)),
                        )),
                        const SizedBox(width: 12),
                        Expanded(child: DropdownButtonFormField<String>(
                          initialValue: _filterType, isExpanded: true,
                          items: const [
                            DropdownMenuItem(value: '', child: Text('Semua Jenis')),
                            DropdownMenuItem(value: 'absent', child: Text('Tidak Masuk')),
                            DropdownMenuItem(value: 'early_leave', child: Text('Pulang Awal')),
                          ],
                          onChanged: (val) => setState(() => _filterType = val ?? ''),
                          decoration: const InputDecoration(labelText: 'Tipe', contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4)),
                        )),
                      ]),
                      const SizedBox(height: 12),
                      Row(children: [
                        Expanded(child: InkWell(
                          onTap: () async {
                            final s = await showDatePicker(context: context, initialDate: DateTime.now(), firstDate: DateTime(2025), lastDate: DateTime(2030));
                            if (s != null) setState(() => _filterDateFrom = s);
                          },
                          child: InputDecorator(
                            decoration: const InputDecoration(labelText: 'Dari Tanggal', contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4)),
                            child: Text(_filterDateFrom == null ? 'Pilih...' : DateFormat('dd/MM/yy').format(_filterDateFrom!), style: const TextStyle(fontSize: 12)),
                          ),
                        )),
                        const SizedBox(width: 12),
                        Expanded(child: InkWell(
                          onTap: () async {
                            final s = await showDatePicker(context: context, initialDate: DateTime.now(), firstDate: DateTime(2025), lastDate: DateTime(2030));
                            if (s != null) setState(() => _filterDateTo = s);
                          },
                          child: InputDecorator(
                            decoration: const InputDecoration(labelText: 'Sampai Tanggal', contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4)),
                            child: Text(_filterDateTo == null ? 'Pilih...' : DateFormat('dd/MM/yy').format(_filterDateTo!), style: const TextStyle(fontSize: 12)),
                          ),
                        )),
                      ]),
                      const SizedBox(height: 12),
                      OutlinedButton.icon(onPressed: _resetFilters, icon: const Icon(Icons.refresh, size: 16), label: const Text('RESET FILTER'),
                          style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 8), side: const BorderSide(color: AppTheme.borderLight))),
                      const SizedBox(height: 12),
                      Row(children: [
                        Expanded(child: ElevatedButton.icon(
                          onPressed: () => _downloadReport('excel'),
                          icon: const Icon(Icons.table_chart, size: 18),
                          label: const Text('Ekspor Excel'),
                          style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                        )),
                        const SizedBox(width: 12),
                        Expanded(child: ElevatedButton.icon(
                          onPressed: () => _downloadReport('pdf'),
                          icon: const Icon(Icons.picture_as_pdf, size: 18),
                          label: const Text('Ekspor PDF'),
                          style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                        )),
                      ]),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                if (filteredLeaves.isEmpty)
                  Container(
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
                    padding: const EdgeInsets.all(24),
                    child: const Center(child: Text('Tidak ada riwayat perizinan yang cocok.', style: TextStyle(color: AppTheme.textMuted, fontSize: 13))),
                  )
                else
                  ...filteredLeaves.map((leave) => _buildHistoryCard(leave)),
                if (filteredLeaves.isNotEmpty && db.leaveHistoryLastPage > 1)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.chevron_left),
                          onPressed: db.leaveHistoryCurrentPage > 1 
                              ? () => db.fetchLeaveQueue(pendingPage: db.leavePendingCurrentPage, historyPage: db.leaveHistoryCurrentPage - 1) 
                              : null,
                        ),
                        Text('Hal ${db.leaveHistoryCurrentPage} dari ${db.leaveHistoryLastPage}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        IconButton(
                          icon: const Icon(Icons.chevron_right),
                          onPressed: db.leaveHistoryCurrentPage < db.leaveHistoryLastPage 
                              ? () => db.fetchLeaveQueue(pendingPage: db.leavePendingCurrentPage, historyPage: db.leaveHistoryCurrentPage + 1) 
                              : null,
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildPendingCard(dynamic leave) {
    final studentName = leave.userName ?? 'Unknown';
    final className = leave.userClassName ?? '-';
    final dateStr = DateFormat('dd/MM/yyyy').format(leave.date);
    final borderColor = leave.type == 'absent' ? AppTheme.statusLeave : AppTheme.statusLate;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: borderColor, width: 1.5)),
      padding: const EdgeInsets.all(16),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(studentName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            Text('Kelas: $className · Tanggal: $dateStr', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
          ])),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(color: borderColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
            child: Text(leave.type == 'absent' ? 'Tidak Masuk' : 'Pulang Awal',
                style: TextStyle(color: borderColor, fontWeight: FontWeight.bold, fontSize: 11)),
          ),
        ]),
        const Divider(height: 20),
        Text.rich(TextSpan(style: const TextStyle(color: AppTheme.textDark, fontSize: 13), children: [
          const TextSpan(text: 'Alasan: ', style: TextStyle(fontWeight: FontWeight.bold)),
          TextSpan(text: leave.reason == 'sick' ? 'Sakit' : 'Urusan Penting / Mendadak'),
        ])),
        const SizedBox(height: 6),
        Text.rich(TextSpan(style: const TextStyle(color: AppTheme.textDark, fontSize: 13), children: [
          const TextSpan(text: 'Keterangan: ', style: TextStyle(fontWeight: FontWeight.bold)),
          TextSpan(text: '"${leave.keterangan}"', style: const TextStyle(fontStyle: FontStyle.italic, color: AppTheme.textMuted)),
        ])),
        const SizedBox(height: 16),
        Row(children: [
          Expanded(child: ElevatedButton.icon(
            onPressed: () => _processLeave(leave.id, false),
            icon: const Icon(Icons.close, size: 16), label: const Text('TOLAK'),
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.statusAbsent, foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 8), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
          )),
          const SizedBox(width: 12),
          Expanded(child: ElevatedButton.icon(
            onPressed: () => _processLeave(leave.id, true),
            icon: const Icon(Icons.check, size: 16), label: const Text('TERIMA'),
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.statusPresent, foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 8), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
          )),
        ]),
      ]),
    );
  }

  Widget _buildHistoryCard(dynamic leave) {
    final studentName = leave.userName ?? 'Unknown';
    final className = leave.userClassName ?? '-';
    final dateStr = DateFormat('dd/MM/yyyy').format(leave.date);
    final processedStr = leave.decidedAt != null ? DateFormat('dd/MM HH:mm').format(leave.decidedAt!) : '-';
    final isApproved = leave.status == 'approved';
    final badgeColor = isApproved ? AppTheme.statusPresent : AppTheme.statusAbsent;

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppTheme.borderLight)),
      padding: const EdgeInsets.all(12),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(studentName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            Text('Kelas: $className · Tgl: $dateStr', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
          ])),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(color: badgeColor.withValues(alpha: 0.1), border: Border.all(color: badgeColor.withValues(alpha: 0.3)), borderRadius: BorderRadius.circular(12)),
            child: Text(isApproved ? 'Disetujui' : 'Ditolak', style: TextStyle(color: badgeColor, fontWeight: FontWeight.bold, fontSize: 10)),
          ),
        ]),
        const SizedBox(height: 6),
        Text('Jenis: ${leave.type == "absent" ? "Tidak Masuk" : "Pulang Awal"} (${leave.reason == "sick" ? "Sakit" : "Urusan Penting"})',
            style: const TextStyle(fontSize: 12, color: AppTheme.textDark)),
        if (leave.keterangan.isNotEmpty)
          Padding(padding: const EdgeInsets.only(top: 2), child: Text('Keterangan: "${leave.keterangan}"',
              style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: AppTheme.textMuted))),
        if (leave.decisionNote != null && leave.decisionNote!.isNotEmpty)
          Padding(padding: const EdgeInsets.only(top: 4), child: Text('Catatan: ${leave.decisionNote}',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: badgeColor))),
        const Divider(height: 14),
        Align(alignment: Alignment.centerRight, child: Text('Diproses: $processedStr', style: const TextStyle(fontSize: 10, color: AppTheme.textMuted))),
      ]),
    );
  }
}
