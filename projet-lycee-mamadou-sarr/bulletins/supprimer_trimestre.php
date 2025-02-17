<?php
require_once 'config.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['idperiode']) && isset($_GET['idpromotion'])) {
    $idperiode = $_GET['idperiode'];
    $idpromotion = $_GET['idpromotion'];
    
    try {
        // Vérifier si des notes sont associées à ce trimestre
        $check = $conn->prepare("SELECT COUNT(*) FROM notes WHERE idperiode = ?");
        $check->execute([$idperiode]);
        
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Impossible de supprimer ce trimestre car des notes y sont associées";
        } else {
            $sql = "DELETE FROM trimestres WHERE idperiode = ? AND idpromotion = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idperiode, $idpromotion]);
            $_SESSION['message'] = "Trimestre supprimé avec succès";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression du trimestre";
    }
    
    header("Location: gestion_trimestres.php?idpromotion=" . $idpromotion);
    exit();
} else {
    header('Location: gestion_promotions.php');
    exit();
}
?> 