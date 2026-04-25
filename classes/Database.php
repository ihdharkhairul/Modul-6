<?php
/**
 * Class Database
 * Mengelola koneksi ke database MySQL menggunakan Singleton Pattern.
 */
class Database {
    private static ?Database $instance = null;
    private mysqli $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            die('Koneksi database gagal: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
    }

    /** Mencegah cloning instance (Singleton) */
    private function __clone() {}

    /**
     * Mengembalikan satu-satunya instance Database (Singleton Pattern).
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Mengembalikan objek koneksi mysqli.
     */
    public function getConnection(): mysqli {
        return $this->connection;
    }

    /**
     * Menutup koneksi dan reset instance.
     */
    public function close(): void {
        $this->connection->close();
        self::$instance = null;
    }
}
