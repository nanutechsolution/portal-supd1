<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pesan yang dikirim melalui halaman Kontak publik.
 *
 * Pesan diterima publik sehingga tidak diamati oleh ModelAuditObserver;
 * perubahan status oleh admin dicatat secara eksplisit dari panel.
 */
#[Fillable([
    'name',
    'email',
    'phone',
    'subject',
    'message',
    'status',
    'ip_address',
    'user_agent',
])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
        ];
    }

    /**
     * @param  Builder<ContactMessage>  $query
     */
    public function scopeNew(Builder $query): void
    {
        $query->where('status', ContactMessageStatus::Baru);
    }
}
