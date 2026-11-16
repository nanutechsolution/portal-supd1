<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

#[Signature('backup:database {--keep=14 : Hari riwayat backup yang disimpan} {--path= : Folder tujuan (default: storage/app/backups)}')]
#[Description('Buat backup database (SQLite copy / mysqldump / pg_dump) dan hapus backup yang lebih tua dari batas retensi')]
class BackupDatabase extends Command
{
    /**
     * Jalankan backup database dan rotasi riwayatnya.
     */
    public function handle(): int
    {
        $keep = max(1, (int) $this->option('keep'));
        $dir = $this->option('path') ?: storage_path('app/backups');

        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error("Tidak dapat membuat folder backup: {$dir}");

            return self::FAILURE;
        }

        $connection = (string) config('database.default');
        $config = (array) config("database.connections.{$connection}");
        $driver = (string) ($config['driver'] ?? '');
        $timestamp = now()->format('Ymd-His');

        try {
            $file = match ($driver) {
                'sqlite' => $this->backupSqlite($config, $dir, $timestamp),
                'mysql', 'mariadb' => $this->backupSqlDatabase($config, $dir, $timestamp, 'mysqldump', ['--single-transaction', '--quick', '--no-tablespaces'], 'MYSQL_PWD', true),
                'pgsql' => $this->backupSqlDatabase($config, $dir, $timestamp, 'pg_dump', ['--no-owner', '--no-privileges'], 'PGPASSWORD', false),
                default => throw new \RuntimeException("Driver database '{$driver}' belum didukung oleh backup:database. Lakukan backup manual sesuai dokumentasi driver tersebut."),
            };
        } catch (Throwable $e) {
            Log::error('Backup database gagal', ['connection' => $connection, 'error' => $e->getMessage()]);
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $sizeMb = round(filesize($file) / 1024 / 1024, 2);
        Log::info('Backup database berhasil', ['connection' => $connection, 'file' => basename($file), 'size_mb' => $sizeMb]);
        $this->info("Backup dibuat: {$file} ({$sizeMb} MB)");

        $deleted = $this->pruneOldBackups($dir, $keep);
        if ($deleted > 0) {
            $this->line("Menghapus {$deleted} backup lama (lebih dari {$keep} hari).");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function backupSqlite(array $config, string $dir, string $timestamp): string
    {
        $source = (string) ($config['database'] ?? '');

        if ($source === '' || $source === ':memory:') {
            throw new \RuntimeException('SQLite in-memory tidak dapat dibackup; gunakan file database.');
        }

        if (! is_file($source)) {
            throw new \RuntimeException("Berkas database SQLite tidak ditemukan: {$source}");
        }

        $target = $dir.'/portal-'.$timestamp.'.sqlite';

        if (! copy($source, $target)) {
            throw new \RuntimeException("Gagal menyalin {$source} ke {$target}");
        }

        return $target;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $dumpArguments
     */
    private function backupSqlDatabase(array $config, string $dir, string $timestamp, string $binary, array $dumpArguments, string $passwordEnvironmentVariable, bool $isMysql): string
    {
        $binaryPath = $this->findBinary($binary);

        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? ($isMysql ? 3306 : 5432));
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new \RuntimeException('Konfigurasi DB_DATABASE atau DB_USERNAME kosong.');
        }

        $socket = (string) ($config['unix_socket'] ?? '');

        $arguments = [$binaryPath, ...$dumpArguments];

        if ($socket !== '') {
            $arguments[] = $isMysql ? '--socket='.$socket : '--host='.$socket;
        } elseif ($isMysql) {
            $arguments[] = '--host='.$host;
            $arguments[] = '--port='.$port;
        } else {
            $arguments[] = '--host='.$host;
            $arguments[] = '--port='.$port;
        }

        $arguments[] = '--user='.$username;
        $arguments[] = $database;

        $target = $dir.'/portal-'.$timestamp.'.sql';
        $handle = fopen($target, 'wb');

        if ($handle === false) {
            throw new \RuntimeException("Tidak dapat menulis {$target}");
        }

        $process = new Process($arguments, null, [$passwordEnvironmentVariable => $password]);
        $process->setTimeout(600);

        $process->run(function (string $type, string $buffer) use ($handle): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            }
        });

        fclose($handle);

        if (! $process->isSuccessful()) {
            @unlink($target);

            throw new \RuntimeException(trim($process->getErrorOutput()) ?: "{$binary} gagal dijalankan.");
        }

        return $this->compressIfPossible($target);
    }

    private function findBinary(string $binary): string
    {
        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $command = $isWindows ? 'where '.$binary : 'command -v '.$binary;
        $output = trim((string) shell_exec($command));

        if ($output === '') {
            throw new \RuntimeException("Binary '{$binary}' tidak ditemukan. Pasang MySQL/PostgreSQL client pada server, atau lakukan backup manual sesuai dokumentasi driver tersebut.");
        }

        $firstLine = strtok($output, "\r\n");

        return $firstLine !== false ? $firstLine : $output;
    }

    private function compressIfPossible(string $file): string
    {
        if (! function_exists('gzencode')) {
            return $file;
        }

        $compressed = $file.'.gz';
        $contents = file_get_contents($file);

        if ($contents === false || file_put_contents($compressed, gzencode($contents, 6)) === false) {
            return $file;
        }

        unlink($file);

        return $compressed;
    }

    private function pruneOldBackups(string $dir, int $keepDays): int
    {
        $cutoff = time() - ($keepDays * 86400);
        $deleted = 0;

        foreach (glob($dir.'/portal-*.*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
