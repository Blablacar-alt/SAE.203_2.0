<?php
// db.php : Connexion à la base de données OVH / Distante

$host    = 'ijtebowcompte3.mysql.db';
$db      = 'ijtebowcompte3';
$user    = 'ijtebowcompte3';
$pass    = '56sc9NVi2026';
$charset = 'utf8mb4'; // utf8mb4 est recommandé pour gérer tous les caractères et émojis

// Configuration des options PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    // ATTENTION : Les variables ($user et $pass) doivent bien être placées ici !
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En production, il est préférable de ne pas afficher le message brut pour des raisons de sécurité
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>