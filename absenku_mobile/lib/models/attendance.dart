class Attendance {
  final String id;
  final String userId;
  final DateTime? checkInAt;
  final DateTime? checkOutAt;
  final DateTime date;
  final String status; // 'present', 'late', 'absent', 'leave', 'sick'
  final double? latitude;
  final double? longitude;

  Attendance({
    required this.id,
    required this.userId,
    this.checkInAt,
    this.checkOutAt,
    required this.date,
    required this.status,
    this.latitude,
    this.longitude,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'userId': userId,
        'checkInAt': checkInAt?.toIso8601String(),
        'checkOutAt': checkOutAt?.toIso8601String(),
        'date': date.toIso8601String(),
        'status': status,
        'latitude': latitude,
        'longitude': longitude,
      };

  factory Attendance.fromJson(Map<String, dynamic> json) => Attendance(
        id: json['id'] as String,
        userId: json['userId'] as String,
        checkInAt: json['checkInAt'] != null
            ? DateTime.parse(json['checkInAt'] as String)
            : null,
        checkOutAt: json['checkOutAt'] != null
            ? DateTime.parse(json['checkOutAt'] as String)
            : null,
        date: DateTime.parse(json['date'] as String),
        status: json['status'] as String,
        latitude: json['latitude'] as double?,
        longitude: json['longitude'] as double?,
      );

  Attendance copyWith({
    DateTime? checkInAt,
    DateTime? checkOutAt,
    String? status,
    double? latitude,
    double? longitude,
  }) {
    return Attendance(
      id: id,
      userId: userId,
      checkInAt: checkInAt ?? this.checkInAt,
      checkOutAt: checkOutAt ?? this.checkOutAt,
      date: date,
      status: status ?? this.status,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
    );
  }
}

class LeaveRequest {
  final String id;
  final String userId;
  final String type; // 'absent' (tidak masuk), 'early_leave' (pulang awal)
  final DateTime date;
  final String reason; // 'urgent' (urusan penting), 'sick' (sakit)
  final String keterangan;
  final String status; // 'pending', 'approved', 'rejected'
  final DateTime? decidedAt;
  final String? decidedById;
  final String? decisionNote;

  LeaveRequest({
    required this.id,
    required this.userId,
    required this.type,
    required this.date,
    required this.reason,
    required this.keterangan,
    required this.status,
    this.decidedAt,
    this.decidedById,
    this.decisionNote,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'userId': userId,
        'type': type,
        'date': date.toIso8601String(),
        'reason': reason,
        'keterangan': keterangan,
        'status': status,
        'decidedAt': decidedAt?.toIso8601String(),
        'decidedById': decidedById,
        'decisionNote': decisionNote,
      };

  factory LeaveRequest.fromJson(Map<String, dynamic> json) => LeaveRequest(
        id: json['id'] as String,
        userId: json['userId'] as String,
        type: json['type'] as String,
        date: DateTime.parse(json['date'] as String),
        reason: json['reason'] as String,
        keterangan: json['keterangan'] as String,
        status: json['status'] as String,
        decidedAt: json['decidedAt'] != null
            ? DateTime.parse(json['decidedAt'] as String)
            : null,
        decidedById: json['decidedById'] as String?,
        decisionNote: json['decisionNote'] as String?,
      );

  LeaveRequest copyWith({
    String? status,
    DateTime? decidedAt,
    String? decidedById,
    String? decisionNote,
  }) {
    return LeaveRequest(
      id: id,
      userId: userId,
      type: type,
      date: date,
      reason: reason,
      keterangan: keterangan,
      status: status ?? this.status,
      decidedAt: decidedAt ?? this.decidedAt,
      decidedById: decidedById ?? this.decidedById,
      decisionNote: decisionNote ?? this.decisionNote,
    );
  }
}
