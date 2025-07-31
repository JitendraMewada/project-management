<?php
// Database migration script for production deployment
require_once '../config/database.php';

class DatabaseMigrator {
    private $db;
    private $migrations_path;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->migrations_path = __DIR__ . '/migrations/';
        
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
    }
    
    private function createMigrationsTable() {
        $query = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        )";
        $this->db->exec($query);
    }
    
    public function migrate() {
        echo "🔄 Starting database migration...\n";
        
        $migrations = $this->getPendingMigrations();
        
        if (empty($migrations)) {
            echo "✅ No pending migrations.\n";
            return;
        }
        
        foreach ($migrations as $migration) {
            echo "⏳ Running migration: $migration\n";
            
            try {
                $this->runMigration($migration);
                $this->markMigrationAsExecuted($migration);
                echo "✅ Migration completed: $migration\n";
            } catch (Exception $e) {
                echo "❌ Migration failed: $migration\n";
                echo "Error: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
        
        echo "🎉 All migrations completed successfully!\n";
    }
    
    private function getPendingMigrations() {
        // Get all migration files
        $migration_files = glob($this->migrations_path . '*.sql');
        $migration_files = array_map('basename', $migration_files);
        sort($migration_files);
        
        // Get executed migrations
        $query = "SELECT migration FROM migrations";
        $stmt = $this->db->query($query);
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Return pending migrations
        return array_diff($migration_files, $executed);
    }
    
    private function runMigration($migration) {
        $sql = file_get_contents($this->migrations_path . $migration);
        
        // Split SQL by semicolons and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->db->exec($statement);
            }
        }
    }
    
    private function markMigrationAsExecuted($migration) {
        $query = "INSERT INTO migrations (migration) VALUES (?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$migration]);
    }
    
    public function rollback($steps = 1) {
        echo "🔄 Rolling back $steps migration(s)...\n";
        
        $query = "SELECT migration FROM migrations ORDER BY executed_at DESC LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$steps]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($migrations as $migration) {
            echo "⏳ Rolling back: $migration\n";
            
            // Check if rollback file exists
            $rollback_file = str_replace('.sql', '_rollback.sql', $migration);
            if (file_exists($this->migrations_path . $rollback_file)) {
                $sql = file_get_contents($this->migrations_path . $rollback_file);
                $this->db->exec($sql);
                
                // Remove from migrations table
                $delete_query = "DELETE FROM migrations WHERE migration = ?";
                $delete_stmt = $this->db->prepare($delete_query);
                $delete_stmt->execute([$migration]);
                
                echo "✅ Rollback completed: $migration\n";
            } else {
                echo "⚠️ No rollback file found for: $migration\n";
            }
        }
    }
}

// Command line usage
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'migrate';
    $migrator = new DatabaseMigrator();
    
    switch ($action) {
        case 'migrate':
            $migrator->migrate();
            break;
        case 'rollback':
            $steps = intval($argv[2] ?? 1);
            $migrator->rollback($steps);
            break;
        default:
            echo "Usage: php migrate.php [migrate|rollback] [steps]\n";
    }
}
?>