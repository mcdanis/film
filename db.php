<?php
require_once 'env.php'; // panggil env dulu

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;

$pdo = new PDO($dsn, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
