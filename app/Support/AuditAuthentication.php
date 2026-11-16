<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authentication audit hooks. Credentials and session identifiers are never
 * passed to the audit writer.
 */
final class AuditAuthentication
{
    public function login(Login $event): void
    {
        $user = $this->resolveUser($event->user);

        AuditLogger::record(
            event: 'login',
            module: 'auth',
            subject: $user,
            subjectLabel: $user?->name ?? 'Pengguna',
            actor: $user,
        );
    }

    public function failed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $user = is_string($email)
            ? User::query()->where('email', $email)->first()
            : null;

        AuditLogger::record(
            event: 'login_failed',
            module: 'auth',
            subject: $user,
            subjectLabel: $user?->name ?? 'Tamu',
            status: 'failed',
            actor: $user,
        );
    }

    public function logout(Logout $event): void
    {
        $user = $this->resolveUser($event->user);

        AuditLogger::record(
            event: 'logout',
            module: 'auth',
            subject: $user,
            subjectLabel: $user?->name ?? 'Pengguna',
            actor: $user,
        );
    }

    private function resolveUser(?Authenticatable $user): ?User
    {
        return $user instanceof User ? $user : null;
    }
}
