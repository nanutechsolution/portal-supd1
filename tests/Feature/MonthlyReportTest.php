<?php

namespace Tests\Feature;

use App\Console\Commands\EmailMonthlyReport;
use App\Mail\MonthlyReportMail;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\MonthlyReport;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Setting::flushCache();

        parent::tearDown();
    }

    public function test_month_can_be_parsed_from_a_yyyy_mm_string(): void
    {
        $report = MonthlyReport::fromString('2026-10');

        $this->assertSame('2026-10', $report->periodKey());
        $this->assertSame('2026-10-01', $report->dateFrom());
        $this->assertSame('2026-10-31', $report->dateUntil());
        $this->assertSame('laporan-bulanan-2026-10.xlsx', $report->filename());
    }

    public function test_default_month_is_the_previous_month(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-15 10:00:00'));

        $this->assertSame('2026-02', MonthlyReport::previousMonth()->periodKey());

        $this->travelTo(CarbonImmutable::parse('2026-01-05 10:00:00'));

        $this->assertSame('2025-12', MonthlyReport::previousMonth()->periodKey());
    }

    public function test_invalid_month_is_rejected_without_sending_anything(): void
    {
        Mail::fake();
        User::factory()->admin()->create();

        $this->artisan('reports:email-monthly', ['--month' => '2026-13'])
            ->expectsOutputToContain('Format bulan harus YYYY-MM')
            ->assertExitCode(EmailMonthlyReport::INVALID);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('audit_logs', ['event' => 'report_emailed']);
    }

    public function test_dry_run_lists_recipients_without_sending_or_auditing(): void
    {
        Mail::fake();
        Setting::putMany(['contact_email' => 'humas@example.test']);
        User::factory()->admin()->create(['email' => 'admin@example.test']);

        $this->artisan('reports:email-monthly', [
            '--month' => '2026-10',
            '--dry-run' => true,
            '--to' => ['tambahan@example.test'],
        ])
            ->expectsOutputToContain('Mode dry-run')
            ->expectsOutputToContain('humas@example.test')
            ->expectsOutputToContain('admin@example.test')
            ->expectsOutputToContain('tambahan@example.test')
            ->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('audit_logs', ['event' => 'report_emailed']);
    }

    public function test_report_is_sent_to_contact_admins_and_extra_recipients(): void
    {
        Mail::fake();
        Setting::putMany(['contact_email' => 'humas@example.test']);

        User::factory()->admin()->create(['email' => 'admin-satu@example.test']);
        User::factory()->admin()->create(['email' => 'admin-dua@example.test']);
        User::factory()->editor()->create(['email' => 'editor@example.test']);

        $this->artisan('reports:email-monthly', [
            '--month' => '2026-10',
            '--to' => ['tambahan@example.test'],
        ])->assertExitCode(0);

        Mail::assertSent(MonthlyReportMail::class, 4);

        Mail::assertSent(MonthlyReportMail::class, function (MonthlyReportMail $mail): bool {
            $attachments = $mail->attachments();

            return $attachments !== []
                && $attachments[0]->as === 'laporan-bulanan-2026-10.xlsx'
                && $attachments[0]->mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                && $mail->report->periodKey() === '2026-10';
        });

        Mail::assertNotSent(MonthlyReportMail::class, fn (MonthlyReportMail $mail): bool => $mail->hasTo('editor@example.test'));
    }

    public function test_recipients_are_deduplicated_case_insensitively(): void
    {
        Mail::fake();
        Setting::putMany(['contact_email' => 'Admin@Example.test']);
        User::factory()->admin()->create(['email' => 'admin@example.test']);

        $this->artisan('reports:email-monthly', [
            '--month' => '2026-10',
            '--to' => ['ADMIN@example.test'],
        ])->assertExitCode(0);

        Mail::assertSent(MonthlyReportMail::class, 1);
    }

    public function test_report_delivery_is_recorded_in_the_audit_log(): void
    {
        Mail::fake();
        Setting::putMany(['contact_email' => 'humas@example.test']);
        User::factory()->admin()->create(['email' => 'admin@example.test']);

        $this->artisan('reports:email-monthly', ['--month' => '2026-10'])->assertExitCode(0);

        $audit = DB::table('audit_logs')->where('event', 'report_emailed')->first();

        $this->assertNotNull($audit);
        $this->assertSame('reports', $audit->module);
        $this->assertSame('success', $audit->status);
        $this->assertStringContainsString('Laporan bulanan', (string) $audit->subject_label);

        $after = json_decode((string) $audit->after, true);

        $this->assertSame('2026-10', $after['period']);
        $this->assertSame('laporan-bulanan-2026-10.xlsx', $after['attachment']);
        $this->assertContains('humas@example.test', $after['sent']);
        $this->assertContains('admin@example.test', $after['sent']);
    }

    public function test_command_fails_when_there_are_no_valid_recipients(): void
    {
        Mail::fake();

        $this->artisan('reports:email-monthly', ['--month' => '2026-10'])
            ->expectsOutputToContain('Tidak ada penerima yang valid')
            ->assertExitCode(1);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_invalid_extra_recipients_are_skipped(): void
    {
        Mail::fake();
        Setting::putMany(['contact_email' => 'humas@example.test']);

        $this->artisan('reports:email-monthly', [
            '--month' => '2026-10',
            '--to' => ['bukan-email'],
        ])
            ->expectsOutputToContain('Alamat email tidak valid dan dilewati: bukan-email')
            ->assertExitCode(0);

        Mail::assertSent(MonthlyReportMail::class, 1);
    }

    public function test_summary_only_counts_posts_published_in_the_period(): void
    {
        Post::factory()->published()->create([
            'title' => 'Terbit Oktober',
            'published_at' => '2026-10-05 09:00:00',
        ]);
        Post::factory()->published()->create([
            'title' => 'Terbit November',
            'published_at' => '2026-11-05 09:00:00',
        ]);
        Post::factory()->create(['title' => 'Masih Draf']);

        $summary = MonthlyReport::fromString('2026-10')->summary();

        $this->assertSame(1, $summary['published']);
        $this->assertSame(1, $summary['drafts']);
        $this->assertCount(1, $summary['topPosts']);
        $this->assertSame('Terbit Oktober', $summary['topPosts'][0]['title']);
    }

    public function test_summary_reports_recorded_views_without_backfilling_history(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Berita Kunjungan Bulanan',
            'published_at' => '2026-10-05 09:00:00',
            'views' => 120,
        ]);

        DB::table('post_view_statistics')->insert([
            'post_id' => $post->id,
            'view_date' => '2026-10-06',
            'views' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = MonthlyReport::fromString('2026-10')->summary();

        $this->assertSame(7, $summary['viewsInPeriod']);
        $this->assertSame(120, $summary['viewsHistorical']);
        $this->assertSame('2026-10-06', $summary['viewsRecordedSince']);
    }

    public function test_attachment_is_a_real_xlsx_file(): void
    {
        Post::factory()->published()->create([
            'title' => 'Berita Lampiran Laporan',
            'published_at' => '2026-10-05 09:00:00',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'laporan-test-');

        $this->assertNotFalse($path);

        try {
            MonthlyReport::fromString('2026-10')->write($path);

            $this->assertFileExists($path);
            $this->assertGreaterThan(0, (int) filesize($path));
            $this->assertSame('PK', file_get_contents($path, false, null, 0, 2));
        } finally {
            @unlink($path);
        }
    }

    public function test_monthly_report_command_is_scheduled_on_the_first_day_of_the_month(): void
    {
        $events = collect(app(Schedule::class)->events());

        $event = $events->first(
            fn ($event): bool => str_contains((string) $event->command, 'reports:email-monthly'),
        );

        $this->assertNotNull($event);
        $this->assertSame('0 7 1 * *', $event->expression);
    }
}
