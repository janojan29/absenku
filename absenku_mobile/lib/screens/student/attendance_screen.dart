// lib/screens/student/attendance_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../core/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/clock_widget.dart';
import '../../widgets/status_badge.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  final _formKey = GlobalKey<FormState>();
  final _reasonController = TextEditingController();
  bool _isLoading = false;
  bool _checkedIn = false;
  bool _checkedOut = false;
  String? _checkInTime;
  String? _checkOutTime;

  SchoolSettingModel? _schoolSetting;
  List<AttendanceModel> _history = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  String _formatTimeString(String? dateTimeStr) {
    if (dateTimeStr == null) return '--:--';
    try {
      final dt = DateTime.parse(dateTimeStr).toLocal();
      return DateFormat('HH:mm').format(dt);
    } catch (_) {
      return '--:--';
    }
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final data = await ApiService.getAttendanceStatus();
      final attJson = data['attendance'] as Map<String, dynamic>?;
      final recentJson = data['recent'] as List<dynamic>? ?? [];
      final settingJson = data['setting'] as Map<String, dynamic>?;

      setState(() {
        if (attJson != null) {
          _checkedIn = attJson['check_in_at'] != null;
          _checkInTime = _formatTimeString(attJson['check_in_at']);
          _checkedOut = attJson['check_out_at'] != null;
          _checkOutTime = _formatTimeString(attJson['check_out_at']);
        } else {
          _checkedIn = false;
          _checkedOut = false;
          _checkInTime = null;
          _checkOutTime = null;
        }

        if (settingJson != null) {
          _schoolSetting = SchoolSettingModel.fromJson(settingJson);
        }

        _history = recentJson
            .map((x) => AttendanceModel.fromJson(x as Map<String, dynamic>))
            .toList();
      });
    } catch (e) {
      if (kDebugMode) {
        print('Error loading attendance status: $e');
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _checkIn() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final lat = _schoolSetting?.latitude ?? -6.200000;
      final lng = _schoolSetting?.longitude ?? 106.816666;
      final msg = await ApiService.checkIn(lat, lng, 10.0);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg)),
      );
      _loadData();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _checkOut() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final lat = _schoolSetting?.latitude ?? -6.200000;
      final lng = _schoolSetting?.longitude ?? 106.816666;
      final msg = await ApiService.checkOut(lat, lng, 10.0);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg)),
      );
      _loadData();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _submitLeave() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isLoading = true;
    });
    try {
      final text = _reasonController.text.trim();
      final type = 'absent';
      final isSick = text.toLowerCase().contains('sakit') || text.toLowerCase().contains('dokter');
      final reason = isSick ? 'sick' : 'urgent';
      final todayStr = DateFormat('yyyy-MM-dd').format(DateTime.now());

      final msg = await ApiService.submitLeaveRequest(type, reason, text, todayStr);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg)),
      );
      _reasonController.clear();
      FocusScope.of(context).unfocus();
      _loadData();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
      );
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
    final auth = Provider.of<AuthProvider>(context);

    return AppScaffold(
      title: 'Presensi Siswa',
      child: _isLoading && _history.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  children: [
                    Card(
                      clipBehavior: Clip.antiAlias,
                      child: Container(
                        width: double.infinity,
                        decoration: const BoxDecoration(
                          gradient: AppColors.gradHero,
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
                        child: Column(
                          children: [
                            Text(
                              'Halo, ${auth.currentUser?.name ?? "Siswa"}',
                              style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            const ClockWidget(
                              timeStyle: TextStyle(fontSize: 40, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                            const SizedBox(height: 16),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                              decoration: BoxDecoration(
                                color: Colors.white24,
                                borderRadius: BorderRadius.circular(100),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.location_on, color: Colors.greenAccent, size: 16),
                                  const SizedBox(width: 4),
                                  Text(
                                    _schoolSetting != null
                                        ? 'Radius Presensi: ${_schoolSetting!.radius.round()}m'
                                        : 'Dalam Jangkauan Sekolah',
                                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 12),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Column(
                                children: [
                                  const Text('Masuk', style: TextStyle(fontWeight: FontWeight.bold)),
                                  const SizedBox(height: 8),
                                  Text(_checkInTime ?? '--:--', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                                  const SizedBox(height: 12),
                                  ElevatedButton(
                                    onPressed: (_checkedIn || _isLoading) ? null : _checkIn,
                                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.present),
                                    child: const Text('Presensi'),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Column(
                                children: [
                                  const Text('Pulang', style: TextStyle(fontWeight: FontWeight.bold)),
                                  const SizedBox(height: 8),
                                  Text(_checkOutTime ?? '--:--', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                                  const SizedBox(height: 12),
                                  ElevatedButton(
                                    onPressed: (!_checkedIn || _checkedOut || _isLoading) ? null : _checkOut,
                                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.absent),
                                    child: const Text('Presensi'),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Ajukan Permohonan Izin / Sakit',
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.space900),
                              ),
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: _reasonController,
                                maxLines: 3,
                                decoration: const InputDecoration(
                                  hintText: 'Tuliskan alasan izin/sakit secara lengkap...',
                                ),
                                validator: (value) => value == null || value.isEmpty
                                    ? 'Alasan tidak boleh kosong'
                                    : null,
                              ),
                              const SizedBox(height: 16),
                              SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : _submitLeave,
                                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.leave),
                                  child: const Text('Kirim Permohonan'),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Riwayat Kehadiran 7 Hari Terakhir',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 16),
                            _history.isEmpty
                                ? const Padding(
                                    padding: EdgeInsets.symmetric(vertical: 16.0),
                                    child: Center(child: Text('Tidak ada riwayat kehadiran')),
                                  )
                                : ListView.separated(
                                    shrinkWrap: true,
                                    physics: const NeverScrollableScrollPhysics(),
                                    itemCount: _history.length,
                                    separatorBuilder: (context, index) => const Divider(color: AppColors.space200),
                                    itemBuilder: (context, index) {
                                      final record = _history[index];
                                      final tIn = _formatTimeString(record.timeIn);
                                      final tOut = _formatTimeString(record.timeOut);
                                      return ListTile(
                                        title: Text(record.date, style: const TextStyle(fontWeight: FontWeight.bold)),
                                        subtitle: Text(
                                          record.timeIn != null
                                              ? 'Masuk: $tIn - Pulang: $tOut'
                                              : 'Tidak ada catatan jam masuk',
                                        ),
                                        trailing: StatusBadge(status: record.status),
                                      );
                                    },
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
