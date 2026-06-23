<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Riwayat Pengajuan Izin</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 14mm 10mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            line-height: 1.3;
        }

        th {
            background: #1e3a8a;
            color: #ffffff;
            font-size: 8.5px;
            text-transform: uppercase;
        }

        .title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
            color: #1e3a8a;
        }

        .subtitle {
            font-size: 10px;
            color: #4b5563;
            margin-bottom: 12px;
        }

        .center {
            text-align: center;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="title">Riwayat Pengajuan Izin / Keterangan</div>
    <div class="subtitle">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 18%;">Siswa</th>
                <th style="width: 10%;">Kelas</th>
                <th style="width: 12%;">Jenis</th>
                <th style="width: 18%;">Alasan</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 18%;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $row['date'] }}</td>
                    <td>{{ $row['student'] }}</td>
                    <td>{{ $row['class'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td>
                        <strong>{{ $row['reason'] }}</strong>
                        @if($row['keterangan'])
                            <br><span style="color: #6b7280; font-size: 8px;">{{ $row['keterangan'] }}</span>
                        @endif
                    </td>
                    <td class="center">
                        <span class="badge badge-{{ $row['status'] }}">
                            {{ $row['status'] === 'approved' ? 'Disetujui' : 'Ditolak' }}
                        </span>
                    </td>
                    <td>{{ $row['picket'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center" style="padding: 10px;">Tidak ada riwayat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
