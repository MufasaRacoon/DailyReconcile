<?php
$host = 'localhost';
// For Windows
$db   = 'daily';
$user = 'root';
$pass = '';
// For Mac
// $db   = 'db_daily_reconcile';
// $user = 'root';
// $pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    error_log($e->getMessage());
    die('Erreur de connexion à la base de données');
}
