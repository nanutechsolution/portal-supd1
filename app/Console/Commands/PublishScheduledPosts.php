<?php

namespace App\Console\Commands;

use App\Http\Controllers\RssFeedController;
use App\Models\Post;
use App\Support\AuditLogger;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('posts:publish-scheduled {--window=60 : Jendela menit ke belakang untuk berita yang baru melewati jam tayang}')]
class PublishScheduledPosts extends Command
{
    protected $description = 'Catat audit published untuk berita yang jam tayangnya baru saja terlewati';

    /**
     * Berita terjadwal sudah berstatus `published` dengan published_at di
     * masa depan, sehingga portal menampilkannya tepat pada waktunya melalui
     * scope `published()` — tidak ada perubahan status yang diperlukan.
     *
     * Perintah ini hanya melengkapi jejak audit: saat jam tayang terlampaui,
     * event `published` dicatat satu kali per berita. Aman terhadap
     * eksekusi berulang berkat subkueri NOT EXISTS.
     *
     * Jendela (--window) sengaja dibatasi agar berita lama yang terbit
     * jauh sebelum perintah aktif tidak ditulis ulang ke audit log.
     */
    public function handle(): int
    {
        $window = max(1, (int) $this->option('window'));
        $threshold = now()->subMinutes($window);

        $posts = Post::query()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->where('published_at', '>=', $threshold)
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('audit_logs')
                    ->whereColumn('audit_logs.subject_id', 'posts.id')
                    ->where('audit_logs.subject_type', Post::class)
                    ->where('audit_logs.event', 'published');
            })
            ->get();

        foreach ($posts as $post) {
            AuditLogger::record(
                event: 'published',
                module: 'posts',
                subject: $post,
                subjectLabel: $post->title,
                after: [
                    'published_at' => $post->published_at?->toIso8601String(),
                    'source' => 'auto_publish',
                ],
            );
        }

        if ($posts->isNotEmpty()) {
            Cache::forget(RssFeedController::CACHE_KEY);
        }

        $this->info("Berita yang terbit tercatat di audit log: {$posts->count()}");

        return self::SUCCESS;
    }
}
