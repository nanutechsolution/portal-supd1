<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Mail\MonthlyReportMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\MonthlyReport;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

#[Signature('reports:email-monthly {--month= : Bulan laporan dalam format YYYY-MM, default bulan lalu} {--to=* : Alamat email penerima tambahan, boleh diulang} {--dry-run : Tampilkan penerima dan ringkasan tanpa mengirim email}')]
class EmailMonthlyReport extends Command
{
    protected $description = 'Kirim laporan bulanan (ringkasan + lampiran XLSX) ke redaksi dan administrator';

    public function handle(): int
    {
        try {
            $report = filled($this->option('month'))
                ? MonthlyReport::fromString((string) $this->option('month'))
                : MonthlyReport::previousMonth();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $recipients = $this->recipients();

        if ($recipients === []) {
            $this->error('Tidak ada penerima yang valid. Isi email kontak pada Pengaturan Website atau tambahkan opsi --to.');

            return self::FAILURE;
        }

        $summary = $report->summary();

        if ($this->option('dry-run')) {
            $this->info('Mode dry-run: email tidak dikirim dan audit tidak dicatat.');
            $this->line('Laporan: '.$report->periodLabel().' ('.$report->periodKey().')');
            $this->table(['Penerima'], array_map(fn (string $email): array => [$email], $recipients));
            $this->renderSummary($summary);

            return self::SUCCESS;
        }

        $path = tempnam(sys_get_temp_dir(), 'laporan-bulanan-');

        if ($path === false) {
            $this->error('Tidak dapat membuat berkas sementara untuk lampiran laporan.');

            return self::FAILURE;
        }

        try {
            $report->write($path);
        } catch (Throwable $exception) {
            @unlink($path);

            $this->error('Gagal membuat lampiran laporan: '.$exception->getMessage());
            Log::error('Gagal membuat lampiran laporan bulanan.', [
                'period' => $report->periodKey(),
                'exception' => $exception->getMessage(),
            ]);
            $this->recordAudit($report, $recipients, [], [$exception->getMessage()], $summary);

            return self::FAILURE;
        }

        $sent = [];
        $failures = [];

        try {
            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient)->send(new MonthlyReportMail($report, $path));
                    $sent[] = $recipient;
                } catch (Throwable $exception) {
                    $failures[] = $recipient.': '.$exception->getMessage();
                    Log::error('Gagal mengirim laporan bulanan.', [
                        'period' => $report->periodKey(),
                        'recipient' => $recipient,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            }
        } finally {
            @unlink($path);
        }

        $this->recordAudit($report, $recipients, $sent, $failures, $summary);

        $this->table(
            ['Penerima', 'Status'],
            array_map(
                fn (string $email): array => [$email, in_array($email, $sent, true) ? 'Terkirim' : 'Gagal'],
                $recipients,
            ),
        );

        $this->renderSummary($summary);

        if ($failures !== []) {
            foreach ($failures as $failure) {
                $this->warn('Gagal mengirim: '.$failure);
            }

            $this->error('Laporan '.$report->periodLabel().' hanya terkirim ke '.count($sent).' dari '.count($recipients).' penerima.');

            return self::FAILURE;
        }

        $this->info('Laporan '.$report->periodLabel().' terkirim ke '.count($sent).' penerima.');

        return self::SUCCESS;
    }

    /**
     * Email kontak resmi, seluruh administrator, dan alamat tambahan dari --to.
     *
     * @return array<int, string>
     */
    private function recipients(): array
    {
        $contactEmail = Setting::get('contact_email');

        $candidates = array_merge(
            filled($contactEmail) ? [trim((string) $contactEmail)] : [],
            User::query()
                ->where('role', UserRole::Admin->value)
                ->orderBy('name')
                ->pluck('email')
                ->all(),
            array_map(
                fn (mixed $email): string => trim((string) $email),
                (array) $this->option('to'),
            ),
        );

        $valid = [];

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL) === false) {
                $this->warn('Alamat email tidak valid dan dilewati: '.$candidate);

                continue;
            }

            $valid[Str::lower($candidate)] = Str::lower($candidate);
        }

        return array_values($valid);
    }

    /**
     * @param  array<int, string>  $recipients
     * @param  array<int, string>  $sent
     * @param  array<int, string>  $failures
     * @param  array<string, mixed>  $summary
     */
    private function recordAudit(
        MonthlyReport $report,
        array $recipients,
        array $sent,
        array $failures,
        array $summary,
    ): void {
        AuditLogger::record(
            event: 'report_emailed',
            module: 'reports',
            subjectLabel: 'Laporan bulanan '.$report->periodLabel(),
            after: [
                'period' => $report->periodKey(),
                'attachment' => $report->filename(),
                'recipients' => $recipients,
                'sent' => $sent,
                'failures' => $failures,
                'published_in_period' => $summary['published'],
                'views_recorded_in_period' => $summary['viewsInPeriod'],
                'views_recorded_since' => $summary['viewsRecordedSince'],
            ],
            status: $failures === [] ? 'success' : 'failed',
        );
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function renderSummary(array $summary): void
    {
        $this->table(['Metrik', 'Nilai'], [
            ['Berita terbit pada periode', (string) $summary['published']],
            ['Draf saat ini', (string) $summary['drafts']],
            ['Berita terjadwal', (string) $summary['scheduled']],
            ['Buletin terbit pada periode', (string) $summary['bulletins']],
            ['Kunjungan tercatat pada periode', number_format((int) $summary['viewsInPeriod'], 0, ',', '.')],
            ['Total views historis', number_format((int) $summary['viewsHistorical'], 0, ',', '.')],
        ]);

        if ($summary['viewsRecordedSince'] === null) {
            $this->line('Catatan: data kunjungan per hari/bulan belum tersedia sejak rollup diaktifkan.');
        } else {
            $this->line('Catatan: data kunjungan per hari/bulan tersedia sejak '.$summary['viewsRecordedSince'].'.');
        }
    }
}
