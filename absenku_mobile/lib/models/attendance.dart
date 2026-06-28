class Attendance {
  final String id;
  final String userId;
  final DateTime? checkInAt;
  final DateTime? checkOutAt;
  final DateTime date;
  final String status; // 'present', 'late', 'absent', 'leave', 'sick'
  final double? latitude;
  final double? longitude;
  final int? lateMinutes;
  final LeaveRequest? leaveRequest;

  Attendance({
    required this.id,
    required this.userId,
    this.checkInAt,
    this.checkOutAt,
    required this.date,
    required this.status,
    this.latitude,
    this.longitude,
    this.lateMinutes,
    this.leaveRequest,
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

  /// Parse from mock-style JSON (camelCase keys)
  factory Attendance.fromJson(Map<String, dynamic> json) => Attendance(
        id: json['id'].toString(),
        userId: (json['userId'] ?? json['user_id']).toString(),
        checkInAt: json['checkInAt'] != null
            ? DateTime.parse(json['checkInAt'] as String)
            : json['check_in_at'] != null
                ? DateTime.parse(json['check_in_at'] as String)
                : null,
        checkOutAt: json['checkOutAt'] != null
            ? DateTime.parse(json['checkOutAt'] as String)
            : json['check_out_at'] != null
                ? DateTime.parse(json['check_out_at'] as String)
                : null,
        date: DateTime.parse((json['date'] as String).substring(0, 10)),
        status: json['status'] as String,
        latitude: (json['latitude'] as num?)?.toDouble(),
        longitude: (json['longitude'] as num?)?.toDouble(),
        lateMinutes: json['late_minutes'] as int?,
      );

  /// Parse from Laravel API response (snake_case keys)
  factory Attendance.fromApiJson(Map<String, dynamic> json) => Attendance(
        id: json['id'].toString(),
        userId: json['user_id'].toString(),
        checkInAt: json['check_in_at'] != null
            ? DateTime.parse(json['check_in_at'] as String)
            : null,
        checkOutAt: json['check_out_at'] != null
            ? DateTime.parse(json['check_out_at'] as String)
            : null,
        date: DateTime.parse((json['date'] as String).substring(0, 10)),
        status: json['status'] as String,
        latitude: null, // API doesn't expose lat/lng in AttendanceResource
        longitude: null,
        lateMinutes: json['late_minutes'] as int?,
        leaveRequest: json['leave_request'] != null
            ? LeaveRequest(
                id: '',
                userId: json['user_id'].toString(),
                type: json['leave_request']['type'] as String,
                date: DateTime.parse((json['date'] as String).substring(0, 10)),
                reason: json['leave_request']['reason'] as String? ?? '',
                keterangan: json['leave_request']['keterangan'] as String? ?? '',
                status: json['leave_request']['status'] as String,
              )
            : null,
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
      lateMinutes: lateMinutes,
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
  // Extra fields from API
  final String? userName;
  final String? userClassName;

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
    this.userName,
    this.userClassName,
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

  /// Parse from mock-style JSON (camelCase)
  factory LeaveRequest.fromJson(Map<String, dynamic> json) => LeaveRequest(
        id: json['id'].toString(),
        userId: (json['userId'] ?? json['user_id']).toString(),
        type: json['type'] as String,
        date: DateTime.parse(json['date'] as String),
        reason: json['reason'] as String? ?? '',
        keterangan: json['keterangan'] as String? ?? '',
        status: json['status'] as String,
        decidedAt: json['decidedAt'] != null
            ? DateTime.parse(json['decidedAt'] as String)
            : json['decided_at'] != null
                ? DateTime.parse(json['decided_at'] as String)
                : null,
        decidedById: (json['decidedById'] ?? json['decided_by'])?.toString(),
        decisionNote: (json['decisionNote'] ?? json['decision_note']) as String?,
      );

  /// Parse from Laravel API response (snake_case)
  factory LeaveRequest.fromApiJson(Map<String, dynamic> json) {
    // Extract user name and class if user is embedded in the response
    String? userName;
    String? userClassName;
    final userJson = json['user'] as Map<String, dynamic>?;
    if (userJson != null) {
      userName = userJson['name'] as String?;
      final studentProfile = userJson['student_profile'] as Map<String, dynamic>?;
      if (studentProfile != null) {
        final classRoom = studentProfile['class_room'] as Map<String, dynamic>?;
        userClassName = classRoom?['name'] as String?;
      }
    }

    return LeaveRequest(
      id: json['id'].toString(),
      userId: json['user_id'].toString(),
      type: json['type'] as String,
      date: DateTime.parse((json['date'] as String).substring(0, 10)),
      reason: json['reason'] as String? ?? '',
      keterangan: json['keterangan'] as String? ?? '',
      status: json['status'] as String,
      decidedAt: json['decided_at'] != null
          ? DateTime.parse(json['decided_at'] as String)
          : null,
      decidedById: json['decided_by']?.toString(),
      decisionNote: json['decision_note'] as String?,
      userName: userName,
      userClassName: userClassName,
    );
  }

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
      userName: userName,
      userClassName: userClassName,
    );
  }
}
