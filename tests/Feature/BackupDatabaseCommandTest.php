<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use PDO;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/backup-test-'.bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function test_sqlite_database_is_backed_up_and_old_backups_are_pruned(): void
    {
        $source = $this->tempDir.'/source.sqlite';
        $pdo = new PDO('sqlite:'.$source);
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO items (name) VALUES ('sample')");
        unset($pdo);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $source,
        ]);

        $backupDir = $this->tempDir.'/backups';

        $staleBackup = $backupDir.'/portal-20200101-000000.sqlite';
        mkdir($backupDir, 0755, true);
        file_put_contents($staleBackup, 'stale');
        touch($staleBackup, time() - 40 * 86400);

        $this->artisan('backup:database', ['--path' => $backupDir, '--keep' => '14'])
            ->assertSuccessful();

        $backups = glob($backupDir.'/portal-*.sqlite') ?: [];
        $this->assertCount(1, $backups, 'Backup baru harus dibuat dan yang basi harus dihapus');
        $this->assertGreaterThan(0, filesize($backups[0]));
        $this->assertFileDoesNotExist($staleBackup);

        $pdo = new PDO('sqlite:'.$backups[0]);
        $name = $pdo->query('SELECT name FROM items')->fetchColumn();
        $this->assertSame('sample', $name);
    }

    public function test_in_memory_sqlite_database_fails_with_a_clear_message(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->artisan('backup:database', ['--path' => $this->tempDir.'/backups'])
            ->assertFailed();
    }

    public function test_backup_command_is_registered(): void
    {
        $this->assertTrue(
            collect(array_keys(Artisan::all()))->contains('backup:database'),
            'Perintah backup:database harus terdaftar',
        );
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.'/'.$item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
