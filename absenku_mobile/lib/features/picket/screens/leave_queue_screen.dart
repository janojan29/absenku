import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../services/teacher_service.dart';
import '../../../models/user.dart';

class LeaveQueueScreen extends StatefulWidget {
  const LeaveQueueScreen({super.key});

  @override
  State<LeaveQueueScreen> createState() => _LeaveQueueScreenState();
}

class _LeaveQueueScreenState extends State<LeaveQueueScreen> {
  final TeacherService _teacherService = TeacherService();
  
  // Filter States
  String _searchQuery = '';
  String _filterStatus = ''; // '', 'approved', 'rejected'
  String _filterType = ''; // '', 'absent', 'early_leave'
  DateTime? _filterDateFrom;
  DateTime? _filterDateTo;

  // Text controllers
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

  void _processLeave(String id, bool approve) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(approve ? 'Setujui Permohonan Izin' : 'Tolak Permohonan Izin'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                approve
                    ? 'Berikan catatan persetujuan untuk siswa:'
                    : 'Berikan alasan penolakan permohonan:',
                style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _noteController,
                decoration: InputDecoration(
                  hintText: approve ? 'Contoh: ACC. Cepat sembuh.' : 'Contoh: Alasan tidak mendesak.',
                  hintStyle: const TextStyle(fontSize: 12),
                ),
                maxLines: 2,
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                _noteController.clear();
                Navigator.pop(context);
              },
              child: const Text('BATAL', style: TextStyle(color: AppTheme.textMuted)),
            ),
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
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(approve ? 'Permohonan izin disetujui!' : 'Permohonan izin ditolak!'),
                      backgroundColor: approve ? AppTheme.statusPresent : AppTheme.statusAbsent,
                    ),
                  );
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: approve ? AppTheme.statusPresent : AppTheme.statusAbsent,
              ),
              child: const Text('KIRIM'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        final pendingLeaves = _teacherService.getPendingRequests();
        final processedLeaves = _teacherService.getProcessedRequests();

        // Apply filters to processed leaves
        final filteredLeaves = processedLeaves.where((leave) {
          final student = db.users.firstWhere(
            (u) => u.id == leave.userId,
            orElse: () => User(id: '', name: 'Unknown', email: '', role: 'siswa'),
          );
          
          final matchesSearch = student.name.toLowerCase().contains(_searchQuery.toLowerCase());
          final matchesStatus = _filterStatus.isEmpty || leave.status == _filterStatus;
          final matchesType = _filterType.isEmpty || leave.type == _filterType;
          
          bool matchesDate = true;
          if (_filterDateFrom != null) {
            matchesDate = matchesDate && !leave.date.isBefore(_filterDateFrom!);
          }
          if (_filterDateTo != null) {
            matchesDate = matchesDate && !leave.date.isAfter(_filterDateTo!.add(const Duration(days: 1)));
          }

          return matchesSearch && matchesStatus && matchesType && matchesDate;
        }).toList();

        return SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // 1. PENDING QUEUE SECTION
              Row(
                children: [
                  Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'Antrian Pending (${pendingLeaves.length})',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontSize: 16),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              if (pendingLeaves.isEmpty)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Center(
                      child: Column(
                        children: [
                          Icon(Icons.assignment_turned_in_outlined, size: 40, color: Colors.grey[400]),
                          const SizedBox(height: 8),
                          const Text(
                            'Tidak ada permohonan izin pending.',
                            style: TextStyle(color: AppTheme.textMuted, fontSize: 13),
                          ),
                          const Text(
                            'Semua permohonan sudah diproses.',
                            style: TextStyle(color: AppTheme.textMuted, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                  ),
                )
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: pendingLeaves.length,
                  itemBuilder: (context, index) {
                    final leave = pendingLeaves[index];
                    final student = db.users.firstWhere(
                      (u) => u.id == leave.userId,
                      orElse: () => User(id: '', name: 'Unknown', email: '', role: 'siswa'),
                    );
                    final classRoom = db.classrooms.firstWhere(
                      (c) => c.id == student.classRoomId,
                      orElse: () => ClassRoom(id: '', name: '-', jurusan: '-'),
                    );
                    final dateStr = DateFormat('dd/MM/yyyy').format(leave.date);

                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                        side: BorderSide(
                          color: leave.type == 'absent' ? AppTheme.statusLeave : AppTheme.statusLate,
                          width: 1.5,
                        ),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Header: Name & Type Badge
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        student.name,
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                      ),
                                      Text(
                                        'Kelas: ${classRoom.name} · Tanggal: $dateStr',
                                        style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                                      ),
                                    ],
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: (leave.type == 'absent' ? AppTheme.statusLeave : AppTheme.statusLate).withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Text(
                                    leave.type == 'absent' ? 'Tidak Masuk' : 'Pulang Awal',
                                    style: TextStyle(
                                      color: leave.type == 'absent' ? AppTheme.statusLeave : AppTheme.statusLate,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 11,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const Divider(height: 20),

                            // Reason
                            RichText(
                              text: TextSpan(
                                style: const TextStyle(color: AppTheme.textDark, fontSize: 13),
                                children: [
                                  const TextSpan(text: 'Alasan: ', style: TextStyle(fontWeight: FontWeight.bold)),
                                  TextSpan(text: leave.reason == 'sick' ? 'Sakit' : 'Urusan Penting / Mendadak'),
                                ],
                              ),
                            ),
                            const SizedBox(height: 6),

                            // Keterangan
                            RichText(
                              text: TextSpan(
                                style: const TextStyle(color: AppTheme.textDark, fontSize: 13),
                                children: [
                                  const TextSpan(text: 'Keterangan: ', style: TextStyle(fontWeight: FontWeight.bold)),
                                  TextSpan(
                                    text: '"${leave.keterangan}"',
                                    style: const TextStyle(fontStyle: FontStyle.italic, color: AppTheme.textMuted),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),

                            // Buttons Action
                            Row(
                              children: [
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: () => _processLeave(leave.id, false),
                                    icon: const Icon(Icons.close, size: 16),
                                    label: const Text('TOLAK'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppTheme.statusAbsent,
                                      foregroundColor: Colors.white,
                                      padding: const EdgeInsets.symmetric(vertical: 8),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: () => _processLeave(leave.id, true),
                                    icon: const Icon(Icons.check, size: 16),
                                    label: const Text('TERIMA'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppTheme.statusPresent,
                                      foregroundColor: Colors.white,
                                      padding: const EdgeInsets.symmetric(vertical: 8),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),

              const SizedBox(height: 28),

              // 2. LEAVE HISTORY SECTION
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Riwayat Izin (${filteredLeaves.length})',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontSize: 16),
                  ),
                  const Icon(Icons.history, color: AppTheme.textMuted),
                ],
              ),
              const SizedBox(height: 12),

              // Filter Panel
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(14.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Search bar
                      TextField(
                        controller: _searchController,
                        onChanged: (val) => setState(() => _searchQuery = val),
                        decoration: const InputDecoration(
                          hintText: 'Cari Nama Siswa...',
                          prefixIcon: Icon(Icons.search),
                          contentPadding: EdgeInsets.zero,
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Status & Type row filters
                      Row(
                        children: [
                          Expanded(
                            child: DropdownButtonFormField<String>(
                              initialValue: _filterStatus,
                              items: const [
                                DropdownMenuItem(value: '', child: Text('Semua Status')),
                                DropdownMenuItem(value: 'approved', child: Text('Disetujui')),
                                DropdownMenuItem(value: 'rejected', child: Text('Ditolak')),
                              ],
                              onChanged: (val) => setState(() => _filterStatus = val ?? ''),
                              decoration: const InputDecoration(
                                labelText: 'Status',
                                contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: DropdownButtonFormField<String>(
                              initialValue: _filterType,
                              items: const [
                                DropdownMenuItem(value: '', child: Text('Semua Jenis')),
                                DropdownMenuItem(value: 'absent', child: Text('Tidak Masuk')),
                                DropdownMenuItem(value: 'early_leave', child: Text('Pulang Awal')),
                              ],
                              onChanged: (val) => setState(() => _filterType = val ?? ''),
                              decoration: const InputDecoration(
                                labelText: 'Tipe',
                                contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),

                      // Date From / To
                      Row(
                        children: [
                          Expanded(
                            child: InkWell(
                              onTap: () async {
                                final selected = await showDatePicker(
                                  context: context,
                                  initialDate: DateTime.now(),
                                  firstDate: DateTime(2025),
                                  lastDate: DateTime(2030),
                                );
                                if (selected != null) {
                                  setState(() => _filterDateFrom = selected);
                                }
                              },
                              child: InputDecorator(
                                decoration: const InputDecoration(
                                  labelText: 'Dari Tanggal',
                                  contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                ),
                                child: Text(
                                  _filterDateFrom == null
                                      ? 'Pilih...'
                                      : DateFormat('dd/MM/yy').format(_filterDateFrom!),
                                  style: const TextStyle(fontSize: 12),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: InkWell(
                              onTap: () async {
                                final selected = await showDatePicker(
                                  context: context,
                                  initialDate: DateTime.now(),
                                  firstDate: DateTime(2025),
                                  lastDate: DateTime(2030),
                                );
                                if (selected != null) {
                                  setState(() => _filterDateTo = selected);
                                }
                              },
                              child: InputDecorator(
                                decoration: const InputDecoration(
                                  labelText: 'Sampai Tanggal',
                                  contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                ),
                                child: Text(
                                  _filterDateTo == null
                                      ? 'Pilih...'
                                      : DateFormat('dd/MM/yy').format(_filterDateTo!),
                                  style: const TextStyle(fontSize: 12),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),

                      // Reset Filters
                      OutlinedButton.icon(
                        onPressed: _resetFilters,
                        icon: const Icon(Icons.refresh, size: 16),
                        label: const Text('RESET FILTER'),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          side: const BorderSide(color: AppTheme.borderLight),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),

              // Export Buttons Row
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        _showExportDialog('Excel');
                      },
                      icon: const Icon(Icons.download, size: 16, color: Colors.green),
                      label: const Text('Ekspor Excel', style: TextStyle(color: Colors.green, fontSize: 12)),
                      style: OutlinedButton.styleFrom(side: const BorderSide(color: Colors.green)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        _showExportDialog('PDF');
                      },
                      icon: const Icon(Icons.picture_as_pdf, size: 16, color: Colors.red),
                      label: const Text('Ekspor PDF', style: TextStyle(color: Colors.red, fontSize: 12)),
                      style: OutlinedButton.styleFrom(side: const BorderSide(color: Colors.red)),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Processed List View
              if (filteredLeaves.isEmpty)
                const Card(
                  child: Padding(
                    padding: EdgeInsets.all(24.0),
                    child: Center(
                      child: Text(
                        'Tidak ada riwayat perizinan yang cocok.',
                        style: TextStyle(color: AppTheme.textMuted, fontSize: 13),
                      ),
                    ),
                  ),
                )
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: filteredLeaves.length,
                  itemBuilder: (context, index) {
                    final leave = filteredLeaves[index];
                    final student = db.users.firstWhere(
                      (u) => u.id == leave.userId,
                      orElse: () => User(id: '', name: 'Unknown', email: '', role: 'siswa'),
                    );
                    final classRoom = db.classrooms.firstWhere(
                      (c) => c.id == student.classRoomId,
                      orElse: () => ClassRoom(id: '', name: '-', jurusan: '-'),
                    );
                    final officer = db.users.firstWhere(
                      (u) => u.id == leave.decidedById,
                      orElse: () => User(id: '', name: 'Guru Piket', email: '', role: 'guru_piket'),
                    );

                    final dateStr = DateFormat('dd/MM/yyyy').format(leave.date);
                    final processedStr = leave.decidedAt != null
                        ? DateFormat('dd/MM HH:mm').format(leave.decidedAt!)
                        : '-';

                    final isApproved = leave.status == 'approved';
                    final badgeColor = isApproved ? AppTheme.statusPresent : AppTheme.statusAbsent;

                    return Card(
                      margin: const EdgeInsets.only(bottom: 8),
                      child: Padding(
                        padding: const EdgeInsets.all(12.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        student.name,
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                      ),
                                      Text(
                                        'Kelas: ${classRoom.name} · Tgl: $dateStr',
                                        style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                                      ),
                                    ],
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: badgeColor.withValues(alpha: 0.1),
                                    border: Border.all(color: badgeColor.withValues(alpha: 0.3)),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Text(
                                    isApproved ? 'Disetujui' : 'Ditolak',
                                    style: TextStyle(
                                      color: badgeColor,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 10,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              'Jenis: ${leave.type == "absent" ? "Tidak Masuk" : "Pulang Awal"} (${leave.reason == "sick" ? "Sakit" : "Urusan Penting"})',
                              style: const TextStyle(fontSize: 12, color: AppTheme.textDark),
                            ),
                            if (leave.keterangan.isNotEmpty)
                              Padding(
                                padding: const EdgeInsets.only(top: 2.0),
                                child: Text(
                                  'Keterangan: "${leave.keterangan}"',
                                  style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: AppTheme.textMuted),
                                ),
                              ),
                            if (leave.decisionNote != null && leave.decisionNote!.isNotEmpty)
                              Padding(
                                padding: const EdgeInsets.only(top: 4.0),
                                child: Text(
                                  'Catatan: ${leave.decisionNote}',
                                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: badgeColor),
                                ),
                              ),
                            const Divider(height: 14),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'Petugas: ${officer.name}',
                                  style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
                                ),
                                Text(
                                  'Diproses: $processedStr',
                                  style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
                                ),
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
      },
    );
  }

  void _showExportDialog(String type) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text('Ekspor Riwayat $type'),
          content: Text('Data riwayat perizinan telah berhasil di-ekspor ke format $type. File tersimpan di folder Unduhan Anda.'),
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
