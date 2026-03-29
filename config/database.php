<?php
// config/database.php
$host = 'localhost';
$db   = 'email_blast_db';
$user = 'root'; // Change to your DB username
$pass = '';     // Change to your DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;
$db_error = null;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $db_error = "Database connection failed. Please ensure MySQL is running, the 'email_blast_db' database is created, and your credentials are correct.";
}
?>