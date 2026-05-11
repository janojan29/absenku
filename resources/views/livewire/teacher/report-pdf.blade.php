<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi</title>
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
            padding: 4px 5px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            line-height: 1.25;
        }

        th {
            background: #e5e7eb;
            font-size: 8.5px;
            text-transform: uppercase;
        }

        .title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="title">Rekap Absensi - {{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}</div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kelas</th>
                <th>Jurusan</th>
                <th>Nama</th>
                <th>Status</th>
                <th>Status Ijin</th>
                <th>Jenis Ijin</th>
                <th>Alasan Ijin</th>
                <th>Waktu Tidak Masuk</th>
                <th>Keterangan Ijin</th>
                <th>Masuk</th>
                <th>Pulang</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['Tanggal'] }}</td>
                    <td>{{ $row['Kelas'] }}</td>
                    <td>{{ $row['Jurusan'] }}</td>
                    <td>{{ $row['Nama'] }}</td>
                    <td>{{ $row['Status'] }}</td>
                    <td>{{ $row['Status Ijin'] }}</td>
                    <td>{{ $row['Jenis Ijin'] }}</td>
                    <td>{{ $row['Alasan Ijin'] }}</td>
                    <td>{{ $row['Waktu Tidak Masuk'] }}</td>
                    <td>{{ $row['Keterangan Ijin'] }}</td>
                    <td class="center">{{ $row['Masuk'] }}</td>
                    <td class="center">{{ $row['Pulang'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
