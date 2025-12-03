<?php
/**
 * Produktivitätstool - Datenbankverbindung
 * 
 * PDO-basierte Datenbankverbindung mit Singleton-Pattern
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    
    /**
     * Privater Konstruktor (Singleton)
     */
    private function __construct() {}
    
    /**
     * Gibt die PDO-Instanz zurück (erstellt sie bei Bedarf)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                ];
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (PDOException $e) {
                // In Produktion: Fehler loggen statt anzeigen
                die('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
            }
        }
        
        return self::$instance;
    }
    
    /**
     * Klonen verhindern (Singleton)
     */
    private function __clone() {}
    
    /**
     * Unserialisieren verhindern (Singleton)
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Kurzfunktion für Datenbankzugriff
 */
function db(): PDO {
    return Database::getInstance();
}

/**
 * Hilfsfunktion: Prepared Statement ausführen
 */
function dbQuery(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Hilfsfunktion: Einzelne Zeile abrufen
 */
function dbFetchOne(string $sql, array $params = []): ?array {
    $result = dbQuery($sql, $params)->fetch();
    return $result ?: null;
}

/**
 * Hilfsfunktion: Alle Zeilen abrufen
 */
function dbFetchAll(string $sql, array $params = []): array {
    return dbQuery($sql, $params)->fetchAll();
}

/**
 * Hilfsfunktion: Letzte Insert-ID
 */
function dbLastInsertId(): string {
    return db()->lastInsertId();
}

/**
 * Hilfsfunktion: Anzahl betroffener Zeilen
 */
function dbRowCount(string $sql, array $params = []): int {
    return dbQuery($sql, $params)->rowCount();
}

/**
 * Hilfsfunktion: Insert ausführen und ID zurückgeben
 * Unterstützt zwei Syntaxen:
 * 1. dbInsert('INSERT INTO table (col) VALUES (?)', [$value]) - Raw SQL
 * 2. dbInsert('table', ['col' => 'value']) - ORM-style
 */
function dbInsert(string $tableOrSql, array $params = []): int {
    // Prüfen ob es ein Tabellenname oder eine SQL-Query ist
    if (stripos(trim($tableOrSql), 'INSERT') === 0 || stripos(trim($tableOrSql), 'REPLACE') === 0) {
        // Raw SQL
        dbQuery($tableOrSql, $params);
    } else {
        // ORM-style: Tabellenname + assoziatives Array
        $table = $tableOrSql;
        $data = $params;
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        dbQuery($sql, array_values($data));
    }
    
    return (int) db()->lastInsertId();
}
