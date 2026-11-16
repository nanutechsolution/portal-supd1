<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Support\AuditLogger;
use App\Support\CsvSanitizer;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Ekspor CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => AuditLogResource::canAccess())
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    /**
     * Export the currently filtered audit list without loading every row into
     * memory or holding a database transaction open while the file streams.
     */
    private function exportCsv(): StreamedResponse
    {
        // Hanya administrator. Tombol disembunyikan sebagai kenyamanan UI,
        // tetapi aksi ini menegakkan otorisasinya sendiri.
        abort_unless(AuditLogResource::canAccess(), 403);

        $filtered = $this->getFilteredTableQuery() ?? AuditLog::query();

        // Satu statement agregat untuk SNAPSHOT: COUNT(*) dan MAX(id) dibaca
        // dalam statement yang sama, dengan filter ekspor yang sama, sehingga
        // keduanya konsisten terhadap satu read view. Query tidak ditahan
        // dibuka selama streaming (streaming terjadi dari php://temp, bukan
        // langsung dari database).
        $snapshot = (clone $filtered)
            ->reorder()
            ->select([
                DB::raw('COUNT(*) AS rows_snapshot'),
                DB::raw('MAX(id) AS last_id'),
            ])
            ->first();

        $rowsSnapshot = (int) ($snapshot->rows_snapshot ?? 0);
        $lastId = (int) ($snapshot->last_id ?? 0);

        $stream = fopen('php://temp', 'r+b');

        if ($stream === false) {
            abort(500, 'Gagal menyiapkan file ekspor.');
        }

        // UTF-8 BOM keeps accented and Indonesian characters readable in Excel.
        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, [
            'Waktu',
            'Pengguna',
            'Aktivitas',
            'Modul',
            'Objek',
            'Tipe Objek',
            'ID Objek',
            'Status',
            'Alamat IP',
            'User Agent',
            'Nilai Sebelum',
            'Nilai Sesudah',
        ], ',', '"', '');

        // Baris ditulis ke stream sementara SEBELUM event ekspor dicatat,
        // sehingga event tidak menghasilkan event ganda, tidak rekursi, dan
        // file tidak memuat baris audit_log_exported yang baru dibuat.
        $rowsEmitted = $this->eachFilteredAuditLog($filtered, $lastId, function (AuditLog $log) use ($stream): void {
            fputcsv($stream, [
                CsvSanitizer::cell($log->created_at?->format('Y-m-d H:i:s')),
                CsvSanitizer::cell($log->actor_name),
                CsvSanitizer::cell(AuditLog::eventLabel($log->event)),
                CsvSanitizer::cell(AuditLog::moduleLabel($log->module)),
                CsvSanitizer::cell($log->subject_label),
                CsvSanitizer::cell($log->subject_type),
                CsvSanitizer::cell($log->subject_id),
                CsvSanitizer::cell(AuditLog::statusLabel($log->status)),
                CsvSanitizer::cell($log->ip_address),
                CsvSanitizer::cell($log->user_agent),
                CsvSanitizer::cell(AuditLog::formatPayload($log->before)),
                CsvSanitizer::cell(AuditLog::formatPayload($log->after)),
            ], ',', '"', '');
        });

        AuditLogger::record(
            event: 'audit_log_exported',
            module: 'audit_logs',
            subjectLabel: 'Ekspor CSV audit log',
            after: [
                'format' => 'csv',
                'rows_snapshot' => $rowsSnapshot,
                'rows_emitted' => $rowsEmitted,
                'last_id' => $lastId,
                'filters' => $this->tableFilters ?? [],
            ],
        );

        $filename = 'audit-log-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($stream): void {
            rewind($stream);

            while (! feof($stream)) {
                echo fread($stream, 8192);
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Keyset pagination dengan cursor gabungan (created_at, id) menurun.
     * Bertiap chunk minta halaman berikutnya SETELAH cursor terakhir, bukan
     * berdasarkan offset, sehingga penghapusan baris di atas cursor tidak
     * menggeser data di bawah cursor (tidak ada baris terlewat atau duplikat).
     * Batas id <= $lastId menjaga snapshot: baris yang masuk setelah snapshot
     * tidak ikut diekspor, termasuk yang timestamp-nya diatur mundur.
     *
     * @param  Builder<AuditLog>  $query
     * @param  callable(AuditLog): void  $callback
     * @return int Jumlah baris yang benar-benar ditulis
     */
    private function eachFilteredAuditLog(Builder $query, int $lastId, callable $callback): int
    {
        $base = $query->reorder()->orderByDesc('created_at')->orderByDesc('id')->where('id', '<=', $lastId);

        $lastCreatedAt = null;
        $lastRowId = null;
        $emitted = 0;

        do {
            $chunk = (clone $base)
                ->select([
                    'id',
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
                ])
                ->when($lastCreatedAt !== null, function (Builder $chunk) use ($lastCreatedAt, $lastRowId): Builder {
                    return $chunk->where(function (Builder $cursor) use ($lastCreatedAt, $lastRowId): Builder {
                        return $cursor->where('created_at', '<', $lastCreatedAt)
                            ->orWhere(function (Builder $same) use ($lastCreatedAt, $lastRowId): Builder {
                                return $same->where('created_at', '=', $lastCreatedAt)->where('id', '<', $lastRowId);
                            });
                    });
                })
                ->limit(500)
                ->get();

            if ($chunk->isEmpty()) {
                break;
            }

            foreach ($chunk as $log) {
                $callback($log);
                $emitted++;
            }

            $last = $chunk->last();
            $lastCreatedAt = $last->created_at;
            $lastRowId = (int) $last->getKey();
        } while ($chunk->count() === 500);

        return $emitted;
    }
}
