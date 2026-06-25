// lib/screens/admin/settings_screen.dart
import 'package:flutter/material.dart';
import '../../widgets/app_scaffold.dart';
import '../../services/api_service.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _latController;
  late TextEditingController _lngController;
  late TextEditingController _radiusController;
  late TextEditingController _toleranceController;
  late TextEditingController _checkInStartController;
  late TextEditingController _checkInEndController;
  late TextEditingController _checkOutStartController;
  late TextEditingController _checkOutEndController;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController();
    _latController = TextEditingController();
    _lngController = TextEditingController();
    _radiusController = TextEditingController();
    _toleranceController = TextEditingController();
    _checkInStartController = TextEditingController();
    _checkInEndController = TextEditingController();
    _checkOutStartController = TextEditingController();
    _checkOutEndController = TextEditingController();
    _loadSettings();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _latController.dispose();
    _lngController.dispose();
    _radiusController.dispose();
    _toleranceController.dispose();
    _checkInStartController.dispose();
    _checkInEndController.dispose();
    _checkOutStartController.dispose();
    _checkOutEndController.dispose();
    super.dispose();
  }

  Future<void> _loadSettings() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final data = await ApiService.getAdminSettings();
      final s = data['setting'] as Map<String, dynamic>?;
      if (s != null) {
        _nameController.text = (s['name'] ?? '').toString();
        _latController.text = (s['latitude'] ?? '').toString();
        _lngController.text = (s['longitude'] ?? '').toString();
        _radiusController.text = (s['radius_meters'] ?? '').toString();
        _toleranceController.text = (s['late_tolerance_minutes'] ?? '').toString();
        _checkInStartController.text = _formatTime(s['check_in_start_time']);
        _checkInEndController.text = _formatTime(s['check_in_end_time']);
        _checkOutStartController.text = _formatTime(s['check_out_start_time']);
        _checkOutEndController.text = _formatTime(s['check_out_end_time']);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal memuat pengaturan: $e')),
        );
      }
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  String _formatTime(dynamic val) {
    if (val == null) return '';
    final s = val.toString();
    if (s.length >= 5) {
      return s.substring(0, 5); // Take "HH:MM"
    }
    return s;
  }

  void _saveSettings() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isLoading = true;
    });
    try {
      await ApiService.updateAdminSettings({
        'name': _nameController.text,
        'latitude': double.parse(_latController.text),
        'longitude': double.parse(_lngController.text),
        'radius_meters': int.parse(_radiusController.text),
        'late_tolerance_minutes': int.parse(_toleranceController.text),
        'check_in_start_time': _checkInStartController.text,
        'check_in_end_time': _checkInEndController.text,
        'check_out_start_time': _checkOutStartController.text,
        'check_out_end_time': _checkOutEndController.text,
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Pengaturan berhasil disimpan!')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal menyimpan: ${e.toString().replaceAll('Exception: ', '')}')),
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
    return AppScaffold(
      title: 'Pengaturan Sekolah',
      child: _isLoading && _nameController.text.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Profil Sekolah', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 16),
                            TextFormField(
                              controller: _nameController,
                              decoration: const InputDecoration(labelText: 'Nama Sekolah'),
                              validator: (v) => v == null || v.isEmpty ? 'Nama sekolah wajib diisi' : null,
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Geofencing & Koordinat', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 16),
                            Row(
                              children: [
                                Expanded(
                                  child: TextFormField(
                                    controller: _latController,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(labelText: 'Latitude'),
                                    validator: (v) => v == null || v.isEmpty ? 'Wajib' : null,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: TextFormField(
                                    controller: _lngController,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(labelText: 'Longitude'),
                                    validator: (v) => v == null || v.isEmpty ? 'Wajib' : null,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            TextFormField(
                              controller: _radiusController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(labelText: 'Radius Presensi (Meter)'),
                              validator: (v) => v == null || v.isEmpty ? 'Radius wajib diisi' : null,
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Waktu & Jadwal Presensi', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 16),
                            Row(
                              children: [
                                Expanded(
                                  child: TextFormField(
                                    controller: _checkInStartController,
                                    decoration: const InputDecoration(labelText: 'Mulai Masuk (HH:MM)'),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: TextFormField(
                                    controller: _checkInEndController,
                                    decoration: const InputDecoration(labelText: 'Batas Masuk (HH:MM)'),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            Row(
                              children: [
                                Expanded(
                                  child: TextFormField(
                                    controller: _checkOutStartController,
                                    decoration: const InputDecoration(labelText: 'Mulai Pulang (HH:MM)'),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: TextFormField(
                                    controller: _checkOutEndController,
                                    decoration: const InputDecoration(labelText: 'Batas Pulang (HH:MM)'),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            TextFormField(
                              controller: _toleranceController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(labelText: 'Toleransi Keterlambatan (Menit)'),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _saveSettings,
                        child: _isLoading ? const Center(child: CircularProgressIndicator(color: Colors.white)) : const Text('Simpan Pengaturan'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
