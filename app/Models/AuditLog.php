<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable activity record. Read through the Filament audit resource only.
 */
#[Fillable([
    'user_id',
    'actor_name',
    'event',
    'module',
    'subject_type',
    'subject_id',
    'subject_label',
    'before',
    'after',
    'ip_address',
    'user_agent',
    'status',
    'created_at',
])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var array<string, string>
     */
    public const MODULE_LABELS = [
        'auth' => 'Autentikasi',
        'posts' => 'Berita',
        'bulletins' => 'Buletin',
        'gallery' => 'Galeri',
        'categories' => 'Kategori Berita',
        'organizational_units' => 'Struktur Organisasi',
        'settings' => 'Pengaturan Website',
        'pages' => 'Halaman Profil',
        'contact_messages' => 'Pesan Kontak',
        'reports' => 'Laporan',
        'users' => 'Pengguna',
        'audit_logs' => 'Audit Log',
    ];

    /**
     * @var array<string, string>
     */
    public const EVENT_LABELS = [
        'created' => 'Dibuat',
        'updated' => 'Diperbarui',
        'published' => 'Diterbitkan',
        'unpublished' => 'Dibatalkan terbit',
        'deleted' => 'Dihapus',
        'media_replaced' => 'Media diganti',
        'role_changed' => 'Peran diubah',
        'settings_updated' => 'Pengaturan diperbarui',
        'login' => 'Login berhasil',
        'login_failed' => 'Login gagal',
        'logout' => 'Logout',
        'audit_log_viewed' => 'Audit log dilihat',
        'audit_log_exported' => 'Audit log diekspor',
        'report_emailed' => 'Laporan bulanan dikirim',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'success' => 'Berhasil',
        'failed' => 'Gagal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Audit log tidak boleh diubah.');
        });

        static::deleting(function (): never {
            throw new LogicException('Audit log tidak boleh dihapus melalui model. Gunakan perintah retensi.');
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function moduleLabel(?string $module): string
    {
        return self::MODULE_LABELS[$module ?? ''] ?? ucwords(str_replace('_', ' ', (string) $module));
    }

    public static function eventLabel(?string $event): string
    {
        return self::EVENT_LABELS[$event ?? ''] ?? ucwords(str_replace('_', ' ', (string) $event));
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status ?? ''] ?? ucfirst((string) $status);
    }

    public static function formatPayload(?array $payload): string
    {
        if (blank($payload)) {
            return '—';
        }

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
    }
}
