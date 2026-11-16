<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Support\AuditLogger;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Defense in depth: parent::mount() sudah menegakkan otorisasi lewat
        // policy, tetapi jangan bergantung pada itu saja. Akses yang ditolak
        // harus berhenti di sini, sebelum event audit ditulis.
        abort_unless(AuditLogResource::canAccess(), 403);

        /** @var AuditLog $log */
        $log = $this->getRecord();

        // Written explicitly rather than through an observer, so the audit log
        // never observes itself and cannot recurse.
        AuditLogger::record(
            event: 'audit_log_viewed',
            module: 'audit_logs',
            subject: $log,
            subjectLabel: 'Audit #'.$log->getKey().' · '.AuditLog::eventLabel($log->event),
            after: [
                'event' => $log->event,
                'module' => $log->module,
                'subject_label' => $log->subject_label,
            ],
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke daftar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AuditLogResource::getUrl('index')),
        ];
    }
}
