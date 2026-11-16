<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Bulanan {{ $report->periodLabel() }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
    <div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e4ded2;border-radius:8px;overflow:hidden;">
        <div style="background:#0b2836;color:#ffffff;padding:20px 24px;">
            <p style="margin:0;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#dda43a;">Portal SUPD I</p>
            <h1 style="margin:6px 0 0;font-size:20px;">Laporan Bulanan {{ $report->periodLabel() }}</h1>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 16px;font-size:14px;line-height:1.7;">
                Berikut ringkasan publikasi portal untuk periode
                <strong>{{ $report->dateFrom() }}</strong> sampai <strong>{{ $report->dateUntil() }}</strong>.
                Berkas rincian dalam format XLSX dilampirkan pada email ini.
            </p>

            <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;line-height:1.6;border-collapse:collapse;">
                <tr>
                    <td style="padding:6px 0;color:#66727c;border-bottom:1px solid #eee8dd;">Berita terbit pada periode</td>
                    <td style="padding:6px 0;text-align:right;font-weight:bold;border-bottom:1px solid #eee8dd;">{{ $summary['published'] }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#66727c;border-bottom:1px solid #eee8dd;">Draf saat ini</td>
                    <td style="padding:6px 0;text-align:right;border-bottom:1px solid #eee8dd;">{{ $summary['drafts'] }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#66727c;border-bottom:1px solid #eee8dd;">Berita terjadwal</td>
                    <td style="padding:6px 0;text-align:right;border-bottom:1px solid #eee8dd;">{{ $summary['scheduled'] }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#66727c;border-bottom:1px solid #eee8dd;">Buletin terbit pada periode</td>
                    <td style="padding:6px 0;text-align:right;border-bottom:1px solid #eee8dd;">{{ $summary['bulletins'] }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#66727c;border-bottom:1px solid #eee8dd;">Kunjungan tercatat pada periode</td>
                    <td style="padding:6px 0;text-align:right;border-bottom:1px solid #eee8dd;">{{ number_format((int) $summary['viewsInPeriod'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#66727c;">Total views historis (posts.views)</td>
                    <td style="padding:6px 0;text-align:right;font-weight:bold;">{{ number_format((int) $summary['viewsHistorical'], 0, ',', '.') }}</td>
                </tr>
            </table>

            <p style="margin:14px 0 0;font-size:12px;line-height:1.6;color:#8a6d3b;background:#fdf6e7;border:1px solid #f0dfb4;border-radius:6px;padding:10px 12px;">
                @if ($summary['viewsRecordedSince'] === null)
                    Data kunjungan per hari/bulan belum tersedia sejak rollup diaktifkan. Total views historis bukan angka per bulan dan tidak diisi ulang secara mundur.
                @else
                    Data kunjungan per hari/bulan tersedia sejak {{ \Carbon\CarbonImmutable::parse($summary['viewsRecordedSince'])->translatedFormat('d F Y') }}.
                    Periode sebelum tanggal tersebut tidak dilaporkan secara granular dan tidak diisi ulang secara mundur.
                @endif
            </p>

            @if ($summary['topPosts'] !== [])
                <h2 style="margin:24px 0 8px;font-size:14px;letter-spacing:0.08em;text-transform:uppercase;color:#0b2836;">Berita Terpopuler Bulan Ini</h2>
                <ul style="margin:0;padding-left:18px;font-size:13px;line-height:1.7;">
                    @foreach ($summary['topPosts'] as $post)
                        <li style="margin-bottom:6px;">
                            <strong>{{ $post['title'] }}</strong><br>
                            <span style="color:#66727c;">
                                {{ number_format($post['views'], 0, ',', '.') }} views
                                @if ($post['publishedAt'])
                                    &middot; {{ \Carbon\CarbonImmutable::parse($post['publishedAt'])->translatedFormat('j M Y') }}
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p style="margin:24px 0 0;border-top:1px solid #e4ded2;padding-top:16px;font-size:12px;line-height:1.6;color:#66727c;">
                Lampiran: <strong>{{ $report->filename() }}</strong>. Laporan dibuat otomatis oleh Portal SUPD I.
                Rincian angka dan keterbatasan data juga dicantumkan di dalam berkas lampiran.
            </p>
        </div>
    </div>
</body>
</html>
