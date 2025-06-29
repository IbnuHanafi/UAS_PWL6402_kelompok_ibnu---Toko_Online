<?php

/**
 * Konfigurasi Database Connection
 * File: config/database.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'toko_online');

class Database
{
    private $host = DB_HOST;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $database = DB_NAME;
    private $connection;

    // Constructor untuk membuat koneksi database
    public function __construct()
    {
        $this->connect();
    }

    // Method untuk koneksi ke database
    private function connect()
    {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database}",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    // Method untuk mendapatkan koneksi
    public function getConnection()
    {
        return $this->connection;
    }

    // Method untuk menjalankan query SELECT
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Query error: " . $e->getMessage());
        }
    }

    // Method untuk menjalankan query INSERT, UPDATE, DELETE
    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("Execute error: " . $e->getMessage());
        }
    }

    // Method untuk mendapatkan data satu row
    public function fetch($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Fetch error: " . $e->getMessage());
        }
    }

    // Method untuk mendapatkan ID terakhir yang diinsert
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    // Method untuk menghitung jumlah row
    public function count($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Count error: " . $e->getMessage());
        }
    }
}

// Membuat instance global database
$db = new Database();
