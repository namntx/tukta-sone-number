<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class ExportDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export {--file=database_export.sql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export database to SQL file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->option('file');
        $connection = Config::get('database.default');
        $config = Config::get("database.connections.{$connection}");
        
        if ($config['driver'] !== 'mysql') {
            $this->error('This command only supports MySQL database');
            return 1;
        }

        $this->info("Exporting database: {$config['database']}");

        // Try to use mysqldump if available
        $mysqldumpPath = $this->findMysqldump();
        
        if ($mysqldumpPath) {
            return $this->exportWithMysqldump($mysqldumpPath, $config, $filename);
        }

        // Fallback: export manually
        return $this->exportManually($config, $filename);
    }

    protected function findMysqldump()
    {
        $paths = [
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.1\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.xx\\bin\\mysqldump.exe',
            'mysqldump', // Try PATH
        ];

        foreach ($paths as $path) {
            if (file_exists($path) || shell_exec("where {$path} 2>nul")) {
                return $path;
            }
        }

        return null;
    }

    protected function exportWithMysqldump($mysqldumpPath, $config, $filename)
    {
        $this->info("Using mysqldump: {$mysqldumpPath}");
        
        // Use --defaults-extra-file to avoid password in command line
        $tempConfigFile = tempnam(sys_get_temp_dir(), 'mysql_config_');
        $configContent = "[client]\n";
        $configContent .= "user={$config['username']}\n";
        if ($config['password']) {
            $configContent .= "password={$config['password']}\n";
        }
        $configContent .= "host={$config['host']}\n";
        $configContent .= "port={$config['port']}\n";
        file_put_contents($tempConfigFile, $configContent);
        
        // Redirect stderr to null to avoid warnings in output
        $command = sprintf(
            '"%s" --defaults-extra-file=%s --single-transaction --routines --triggers %s > %s 2>nul',
            $mysqldumpPath,
            escapeshellarg($tempConfigFile),
            escapeshellarg($config['database']),
            escapeshellarg($filename)
        );

        exec($command, $output, $returnCode);
        
        // Clean up temp config file
        @unlink($tempConfigFile);

        if ($returnCode === 0) {
            // Remove any remaining warning messages from the file
            $content = file_get_contents($filename);
            // Remove lines starting with mysqldump: warning
            $content = preg_replace('/^mysqldump:.*$/m', '', $content);
            // Remove lines starting with [Warning]
            $content = preg_replace('/^\[Warning\].*$/m', '', $content);
            // Remove empty lines at the beginning
            $content = preg_replace('/^\s*\n/m', '', $content);
            file_put_contents($filename, $content);
            
            $this->info("✅ Database exported successfully to: {$filename}");
            return 0;
        } else {
            $this->error("Failed to export database");
            if (!empty($output)) {
                $this->error(implode("\n", $output));
            }
            return 1;
        }
    }

    protected function exportManually($config, $filename)
    {
        $this->warn("mysqldump not found, using manual export...");
        
        $handle = fopen($filename, 'w');
        if (!$handle) {
            $this->error("Cannot create file: {$filename}");
            return 1;
        }

        // Write header
        fwrite($handle, "-- MySQL dump\n");
        fwrite($handle, "-- Database: {$config['database']}\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $config['database'];

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $this->info("Exporting table: {$tableName}");
            
            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            fwrite($handle, "\n-- Table structure for table `{$tableName}`\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            fwrite($handle, $createTable[0]->{'Create Table'} . ";\n\n");

            // Get table data
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                fwrite($handle, "-- Dumping data for table `{$tableName}`\n");
                fwrite($handle, "LOCK TABLES `{$tableName}` WRITE;\n");
                
                $chunkSize = 100;
                $rows->chunk($chunkSize, function ($chunk) use ($handle, $tableName) {
                    $columns = array_keys((array)$chunk->first());
                    $columnList = '`' . implode('`, `', $columns) . '`';
                    
                    foreach ($chunk as $row) {
                        $values = [];
                        foreach ($columns as $col) {
                            $value = $row->$col;
                            if ($value === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $valuesStr = implode(', ', $values);
                        fwrite($handle, "INSERT INTO `{$tableName}` ({$columnList}) VALUES ({$valuesStr});\n");
                    }
                });
                
                fwrite($handle, "UNLOCK TABLES;\n\n");
            }
        }

        fclose($handle);
        $this->info("✅ Database exported successfully to: {$filename}");
        return 0;
    }
}
