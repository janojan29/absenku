<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\StudentViolation;
use App\Models\StudentProfile;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Jobs\SendWhatsAppMessage;

class StudentViolationController extends Controller
{
    public function reportMissingStudent(Request $request)
    {
        $request->validate([
            'student_profile_id' => 'required|exists:student_profiles,id',
            'subject' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $siswa = StudentProfile::with('user')->findOrFail($request->student_profile_id);
        $waktuKejadian = Carbon::now('Asia/Jakarta');

        // 1. Cek jam berapa siswa hadir (masuk gerbang) hari ini
        $absenHariIni = Attendance::where('user_id', $siswa->user_id)
            ->whereDate('date', $waktuKejadian->toDateString())
            ->whereNotNull('check_in_at')
            ->first();

        $waktuHadir = $absenHariIni ? Carbon::parse($absenHariIni->check_in_at)->format('H:i') : 'pagi ini';

        // 2. Simpan laporan guru ke database
        $violation = StudentViolation::create([
            'student_profile_id' => $siswa->id,
            'reported_by' => auth()->id(),
            'subject' => $request->subject,
            'incident_time' => $waktuKejadian->format('H:i:s'),
            'description' => $request->description,
        ]);

        // 3. Susun isi pesan Bahasa Indonesia
        $pesan = "Yth. Bapak/Ibu Orang Tua/Wali dari *{$siswa->user->name}*.\n\n"
               . "Menginformasikan bahwa ananda tercatat telah *hadir di sekolah* pada pukul *{$waktuHadir} WIB*. "
               . "Namun, pada pukul *{$waktuKejadian->format('H:i')} WIB* (saat Mata Pelajaran {$request->subject}), "
               . "ananda didapati *tidak berada di dalam ruang kelas* tanpa keterangan yang jelas.\n\n"
               . "Mohon bantuan Bapak/Ibu untuk turut mengkonfirmasi keberadaan ananda saat ini demi keamanan, keselamatan, dan kedisiplinan siswa.\n\n"
               . "Terima kasih atas perhatian dan kerjasamanya.";

        // 4. Kirim notifikasi WhatsApp ke orang tua
        $nomorTujuan = $siswa->parent_phone_wa ?? $siswa->parent_whatsapp_number;
        
        if (empty($nomorTujuan)) {
            return response()->json(['message' => 'Laporan dicatat, namun WA tidak terkirim karena nomor HP orang tua kosong di database.']);
        }

        SendWhatsAppMessage::dispatch(
            $nomorTujuan,
            $pesan,
            StudentViolation::class,
            $violation->id
        );

        return response()->json(['message' => 'Laporan berhasil dicatat dan dikirim ke WA Ortu!']);
    }
}
