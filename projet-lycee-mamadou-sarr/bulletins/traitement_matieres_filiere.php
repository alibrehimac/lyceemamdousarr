<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_filiere = $_POST['code_filiere'] ?? '';
    $idserie = intval($_POST['idserie'] ?? 0);
    $matieres = $_POST['matieres'] ?? [];
    $coefficients = $_POST['coef'] ?? [];

    try {
        $conn->beginTransaction();

        // Supprimer les anciennes associations
        $stmt = $conn->prepare("DELETE FROM matiere_filiere 
                              WHERE code_filiere = ?");
        $stmt->execute([$code_filiere]);

        // Insérer les nouvelles associations
        $stmt = $conn->prepare("INSERT INTO matiere_filiere 
                              (code_filiere, id_matiere, coefficient) 
                              VALUES (?, ?, ?)");

        foreach ($matieres as $id_matiere) {
            $coef = isset($coefficients[$id_matiere]) ? $coefficients[$id_matiere] : 1;
            $stmt->execute([$code_filiere, $id_matiere, $coef]);
        }

        $conn->commit();
        $_SESSION['success'] = "Les matières ont été mises à jour avec succès";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Erreur lors de la mise à jour des matières : " . $e->getMessage();
    }
}

header('Location: gerer_matieres_filiere.php?code_filiere=' . $code_filiere . '&idserie=' . $idserie);
exit(); 