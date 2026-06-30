import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../core/widgets/custom_expand_menu.dart';


class LeaveRequestScreen extends StatefulWidget {
  const LeaveRequestScreen({super.key});

  @override
  State<LeaveRequestScreen> createState() => _LeaveRequestScreenState();
}

class _LeaveRequestScreenState extends State<LeaveRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  String _leaveType = 'absent'; // 'absent' or 'early_leave'
  DateTime _leaveDate = DateTime.now();
  String _leaveReason = 'sick'; // 'sick' or 'urgent'
  final _keteranganController = TextEditingController();
  bool _isLoading = false;
  String? _errorMessage;

  @override
  void dispose() {
    _keteranganController.dispose();
    super.dispose();
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final db = MockDatabase();
      await db.submitLeaveRequest(
        _leaveType,
        _leaveDate,
        _leaveReason,
        _keteranganController.text,
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Pengajuan izin berhasil dikirim!')),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString().replaceAll('Exception: ', '');
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final today = DateTime.now();
    final tomorrow = today.add(const Duration(days: 1));

    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: AppBar(
        title: const Text('Pengajuan Izin'),
      ),
      body: ListenableBuilder(
        listenable: MockDatabase(),
        builder: (context, _) {
          final db = MockDatabase();
          
          // Determine if current selection is blocked
          bool isBlocked = false;
          if (_leaveType == 'absent') {
            final dateStr = DateFormat('yyyy-MM-dd').format(_leaveDate);
            isBlocked = db.absentBlockedDates.contains(dateStr);
          } else {
            isBlocked = db.earlyLeaveBlockedToday;
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Error banner
                  if (_errorMessage != null) ...[
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.red[50],
                        border: Border.all(color: Colors.red[200]!),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline, color: AppTheme.statusAbsent),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              _errorMessage!,
                              style: const TextStyle(
                                color: AppTheme.statusAbsent,
                                fontWeight: FontWeight.w500,
                                fontSize: 13,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Form Title
                          const Text(
                            'Buat Surat Izin Digital',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.textDark),
                          ),
                          const SizedBox(height: 20),

                           // Jenis Izin
                          const Text('Jenis Izin', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                          const SizedBox(height: 6),
                          CustomExpandMenu(
                            title: 'Pilih Jenis Izin',
                            subtitle: _leaveType == 'absent' ? 'Izin Tidak Masuk' : 'Izin Pulang Lebih Awal',
                            items: const [
                              {'value': 'absent', 'label': 'Izin Tidak Masuk'},
                              {'value': 'early_leave', 'label': 'Izin Pulang Lebih Awal'},
                            ],
                            selectedValue: _leaveType,
                            onChanged: (value) {
                              setState(() {
                                _leaveType = value;
                                if (_leaveType == 'early_leave') {
                                  _leaveDate = today;
                                }
                              });
                            },
                          ),
                          const SizedBox(height: 16),

                          // Waktu Izin
                          if (_leaveType == 'absent') ...[
                            const Text('Waktu Izin', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            const SizedBox(height: 6),
                            CustomExpandMenu(
                              title: 'Pilih Waktu Izin',
                              subtitle: _leaveDate.day == today.day
                                  ? 'Hari Ini (${DateFormat('dd/MM/yyyy').format(today)})'
                                  : 'Besok (${DateFormat('dd/MM/yyyy').format(tomorrow)})',
                              items: [
                                {'value': 'today', 'label': 'Hari Ini (${DateFormat('dd/MM/yyyy').format(today)})'},
                                {'value': 'tomorrow', 'label': 'Besok (${DateFormat('dd/MM/yyyy').format(tomorrow)})'},
                              ],
                              selectedValue: _leaveDate.day == today.day ? 'today' : 'tomorrow',
                              onChanged: (value) {
                                setState(() {
                                  if (value == 'today') {
                                    _leaveDate = today;
                                  } else {
                                    _leaveDate = tomorrow;
                                  }
                                });
                              },
                            ),
                            const SizedBox(height: 4),
                            const Text(
                              '1 hari hanya boleh 1 kali pengajuan izin.',
                              style: TextStyle(fontSize: 11, color: AppTheme.textMuted),
                            ),
                            const SizedBox(height: 16),
                          ],

                          // Alasan
                          const Text('Alasan Izin', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                          const SizedBox(height: 6),
                          CustomExpandMenu(
                            title: 'Pilih Alasan Izin',
                            subtitle: _leaveReason == 'sick' ? 'Sakit' : 'Urusan Penting / Mendadak',
                            items: const [
                              {'value': 'sick', 'label': 'Sakit'},
                              {'value': 'urgent', 'label': 'Urusan Penting / Mendadak'},
                            ],
                            selectedValue: _leaveReason,
                            onChanged: (value) {
                              setState(() {
                                _leaveReason = value;
                              });
                            },
                          ),
                          const SizedBox(height: 16),

                          // Keterangan / Text Area
                          const Text('Keterangan Alasan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextFormField(
                            controller: _keteranganController,
                            maxLines: 4,
                            decoration: const InputDecoration(
                              hintText: 'Jelaskan alasan izin secara detail...',
                              alignLabelWithHint: true,
                            ),
                            validator: (value) {
                              if (value == null || value.trim().isEmpty) {
                                return 'Keterangan wajib diisi';
                              }
                              final trimmed = value.trim();
                              if (trimmed.length < 5) {
                                return 'Keterangan minimal 5 karakter';
                              }
                              if (trimmed.length > 2000) {
                                return 'Keterangan maksimal 2000 karakter';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 24),

                          if (isBlocked) ...[
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(10),
                              margin: const EdgeInsets.only(bottom: 16),
                              decoration: BoxDecoration(
                                color: Colors.red[50],
                                border: Border.all(color: Colors.red[100]!),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Text(
                                'Pengajuan izin untuk tanggal ini sudah ada.',
                                style: TextStyle(color: AppTheme.statusAbsent, fontSize: 11, fontWeight: FontWeight.bold),
                                textAlign: TextAlign.center,
                              ),
                            ),
                          ],

                          ElevatedButton(
                            onPressed: (_isLoading || isBlocked) ? null : _submit,
                            style: ElevatedButton.styleFrom(
                              minimumSize: const Size.fromHeight(50),
                              backgroundColor: AppTheme.accentBlue,
                            ),
                            child: _isLoading
                                ? const CircularProgressIndicator(color: Colors.white)
                                : const Text('KIRIM PENGAJUAN'),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
