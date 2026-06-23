<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Keterangan</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 10mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
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
            background: #e5e7eb;
            font-size: 9px;
            text-transform: uppercase;
        }

        .title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="title">Rekap Keterangan — {{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}</div>

    <table>
        <thead>
            <tr>
                <th style="width:30%">Nama</th>
                <th style="width:15%">Kelas</th>
                <th style="width:19%">Jurusan</th>
                <th style="width:9%" class="center">Hadir</th>
                <th style="width:9%" class="center">Izin</th>
                <th style="width:9%" class="center">Telat</th>
                <th style="width:9%" class="center">Alfa</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summaryRows as $row)
                <tr>
                    <td>{{ $row['Nama'] }}</td>
                    <td>{{ $row['Kelas'] }}</td>
                    <td>{{ $row['Jurusan'] }}</td>
                    <td class="center">{{ $row['Hadir'] }}</td>
                    <td class="center">{{ $row['Izin'] }}</td>
                    <td class="center">{{ $row['Telat'] }}</td>
                    <td class="center">{{ $row['Alfa'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
