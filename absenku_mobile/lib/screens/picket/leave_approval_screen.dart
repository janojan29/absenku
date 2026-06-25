// lib/screens/picket/leave_approval_screen.dart
import 'package:flutter/material.dart';
import '../../core/app_colors.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/status_badge.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';

class LeaveApprovalScreen extends StatefulWidget {
  const LeaveApprovalScreen({super.key});

  @override
  State<LeaveApprovalScreen> createState() => _LeaveApprovalScreenState();
}

class _LeaveApprovalScreenState extends State<LeaveApprovalScreen> {
  final List<LeaveRequestModel> _localRequests = [];
  final List<int> _expandedIds = [];
  final List<int> _loadingIds = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadRequests();
  }

  Future<void> _loadRequests() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final data = await ApiService.getPicketLeaveRequests();
      setState(() {
        _localRequests.clear();
        _localRequests.addAll(data.map((x) => LeaveRequestModel.fromJson(x as Map<String, dynamic>)));
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
        );
      }
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _handleApproval(int id, String newStatus) async {
    setState(() {
      _loadingIds.add(id);
    });

    try {
      if (newStatus == 'approved') {
        await ApiService.approveLeaveRequest(id);
      } else {
        await ApiService.rejectLeaveRequest(id);
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Pengajuan berhasil ${newStatus == 'approved' ? 'disetujui' : 'ditolak'}')),
        );
      }
      _loadRequests();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
        );
      }
    } finally {
      setState(() {
        _loadingIds.remove(id);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final pendingQueue = _localRequests.where((r) => r.status == 'pending').toList();
    final processedHistory = _localRequests.where((r) => r.status != 'pending').toList();

    return AppScaffold(
      title: 'Persetujuan Piket',
      child: _isLoading && _localRequests.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadRequests,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Antrean Pengajuan Izin',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 12),
                    pendingQueue.isEmpty
                        ? const Card(
                            child: Padding(
                              padding: EdgeInsets.all(24.0),
                              child: Center(
                                child: Text('Tidak ada antrean pengajuan izin aktif.', style: TextStyle(color: Colors.black45)),
                              ),
                            ),
                          )
                        : ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: pendingQueue.length,
                            itemBuilder: (context, index) {
                              final req = pendingQueue[index];
                              final isExpanded = _expandedIds.contains(req.id);
                              final isLoading = _loadingIds.contains(req.id);

                              return Card(
                                child: Column(
                                  children: [
                                    ListTile(
                                      title: Text(req.student.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                      subtitle: Text('${req.student.classRoom.name} • Tanggal: ${req.date}'),
                                      trailing: IconButton(
                                        icon: Icon(isExpanded ? Icons.expand_less : Icons.expand_more),
                                        onPressed: () {
                                          setState(() {
                                            if (isExpanded) {
                                              _expandedIds.remove(req.id);
                                            } else {
                                              _expandedIds.add(req.id);
                                            }
                                          });
                                        },
                                      ),
                                    ),
                                    if (isExpanded) ...[
                                      Padding(
                                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                        child: Align(
                                          alignment: Alignment.centerLeft,
                                          child: Text(
                                            req.reason,
                                            style: const TextStyle(fontSize: 14, color: Colors.black87, fontStyle: FontStyle.italic),
                                          ),
                                        ),
                                      ),
                                      Padding(
                                        padding: const EdgeInsets.all(16.0),
                                        child: Row(
                                          mainAxisAlignment: MainAxisAlignment.end,
                                          children: isLoading
                                              ? [const CircularProgressIndicator()]
                                              : [
                                                  TextButton(
                                                    onPressed: () => _handleApproval(req.id, 'rejected'),
                                                    style: TextButton.styleFrom(foregroundColor: AppColors.absent),
                                                    child: const Text('Tolak'),
                                                  ),
                                                  const SizedBox(width: 8),
                                                  ElevatedButton(
                                                    onPressed: () => _handleApproval(req.id, 'approved'),
                                                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.present),
                                                    child: const Text('Setujui'),
                                                  ),
                                                ],
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              );
                            },
                          ),
                    const SizedBox(height: 24),
                    const Text(
                      'Riwayat Keputusan',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 12),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: processedHistory.isEmpty
                            ? const Center(
                                child: Padding(
                                  padding: EdgeInsets.symmetric(vertical: 16),
                                  child: Text('Belum ada riwayat persetujuan.', style: TextStyle(color: Colors.black45)),
                                ),
                              )
                            : ListView.separated(
                                shrinkWrap: true,
                                physics: const NeverScrollableScrollPhysics(),
                                itemCount: processedHistory.length,
                                separatorBuilder: (context, index) => const Divider(color: AppColors.space200),
                                itemBuilder: (context, index) {
                                  final req = processedHistory[index];
                                  return ListTile(
                                    title: Text(req.student.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                    subtitle: Text('${req.student.classRoom.name} • Tanggal: ${req.date}\nCatatan: ${req.note ?? req.reason}'),
                                    trailing: StatusBadge(status: req.status),
                                  );
                                },
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
