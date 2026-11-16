<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Audit log bersifat baca-saja dan hanya untuk administrator.
 *
 * Semua kemampuan selain view dan viewAny ditolak secara eksplisit agar
 * tidak ada operasi create, update, delete, restore, atau replikasi yang
 * dapat melewati policy ini.
 */
class AuditLogPolicy
{
    /**
     * Hanya administrator yang boleh melihat daftar audit log.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya administrator yang boleh membuka detail audit log.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
