<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PDO;
use PDOException;

#[Signature('db:create')]
#[Description('Create the database if it does not exist')]
class DbCreateCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (!$config) {
            $this->error("Database connection '{$connection}' is not configured.");
            return Command::FAILURE;
        }

        $database = $config['database'] ?? null;
        if (!$database) {
            $this->error("Database name is not configured for connection '{$connection}'.");
            return Command::FAILURE;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

        try {
            // Establish a PDO connection without specifying a database
            $dsn = "mysql:host={$host};port={$port}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $this->info("Connecting to database server at {$host}:{$port}...");

            // Check if database exists
            $statement = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($database));
            $exists = $statement->fetch();

            if ($exists) {
                $this->info("Database '{$database}' already exists.");
            } else {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$collation};");
                $this->info("Database '{$database}' created successfully.");
            }

            return Command::SUCCESS;
        } catch (PDOException $exception) {
            $this->error("Could not connect to database server or create database: " . $exception->getMessage());
            return Command::FAILURE;
        }
    }
}
