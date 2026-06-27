import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';

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
      body: SingleChildScrollView(
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
                      DropdownButtonFormField<String>(
                        initialValue: _leaveType,
                        items: const [
                          DropdownMenuItem(value: 'absent', child: Text('Izin Tidak Masuk')),
                          DropdownMenuItem(value: 'early_leave', child: Text('Izin Pulang Lebih Awal')),
                        ],
                        onChanged: (value) {
                          if (value != null) {
                            setState(() {
                              _leaveType = value;
                            });
                          }
                        },
                        decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                      ),
                      const SizedBox(height: 16),

                      // Waktu Izin
                      const Text('Waktu Izin', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 6),
                      DropdownButtonFormField<DateTime>(
                        initialValue: _leaveDate.day == today.day ? _leaveDate : tomorrow,
                        items: [
                          DropdownMenuItem(
                            value: _leaveDate.day == today.day ? _leaveDate : today,
                            child: Text('Hari Ini (${DateFormat('dd/MM/yyyy').format(today)})'),
                          ),
                          DropdownMenuItem(
                            value: _leaveDate.day == tomorrow.day ? _leaveDate : tomorrow,
                            child: Text('Besok (${DateFormat('dd/MM/yyyy').format(tomorrow)})'),
                          ),
                        ],
                        onChanged: (value) {
                          if (value != null) {
                            setState(() {
                              _leaveDate = value;
                            });
                          }
                        },
                        decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                      ),
                      const SizedBox(height: 16),

                      // Alasan
                      const Text('Alasan Izin', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 6),
                      DropdownButtonFormField<String>(
                        initialValue: _leaveReason,
                        items: const [
                          DropdownMenuItem(value: 'sick', child: Text('Sakit')),
                          DropdownMenuItem(value: 'urgent', child: Text('Urusan Penting / Mendadak')),
                        ],
                        onChanged: (value) {
                          if (value != null) {
                            setState(() {
                              _leaveReason = value;
                            });
                          }
                        },
                        decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
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
                          if (value == null || value.trim().length < 5) {
                            return 'Keterangan minimal 5 karakter';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 24),

                      ElevatedButton(
                        onPressed: _isLoading ? null : _submit,
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
      ),
    );
  }
}
