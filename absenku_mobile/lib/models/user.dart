class ClassRoom {
  final String id;
  final String name;
  final String jurusan;

  ClassRoom({
    required this.id,
    required this.name,
    required this.jurusan,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'jurusan': jurusan,
      };

  factory ClassRoom.fromJson(Map<String, dynamic> json) => ClassRoom(
        id: json['id'] as String,
        name: json['name'] as String,
        jurusan: json['jurusan'] as String,
      );
}

class User {
  final String id;
  final String name;
  final String email;
  final String role; // 'admin', 'guru_piket', 'siswa'
  final String? nip; // For teachers
  final String? nis; // For students
  final String? classRoomId; // For students

  User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.nip,
    this.nis,
    this.classRoomId,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'role': role,
        'nip': nip,
        'nis': nis,
        'classRoomId': classRoomId,
      };

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json['id'] as String,
        name: json['name'] as String,
        email: json['email'] as String,
        role: json['role'] as String,
        nip: json['nip'] as String?,
        nis: json['nis'] as String?,
        classRoomId: json['classRoomId'] as String?,
      );
}
