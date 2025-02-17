<?php
// Vérifier si la session n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbgloballms');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Configuration générale
define('SITE_NAME', 'ALAMAL LMS');
define('SITE_URL', 'http://localhost/alamal1lms');

// Connexion à la base de données
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Inclure les fonctions communes
require_once 'includes/functions.php';

// Configuration des rôles et permissions
$PERMISSIONS = [
    'admin' => ['all'],
    'user' => ['view_series', 'view_filieres', 'view_classes', 'add_notes', 'view_notes']
];

// Fonction de vérification des permissions
function hasPermission($permission) {
    global $PERMISSIONS;
    if (!isset($_SESSION['role'])) return false;
    $userRole = $_SESSION['role'];
    return in_array($permission, $PERMISSIONS[$userRole]) || 
           in_array('all', $PERMISSIONS[$userRole]);
}
?>