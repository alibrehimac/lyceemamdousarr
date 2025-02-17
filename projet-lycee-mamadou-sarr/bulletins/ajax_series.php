<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    // Modification d'une série
    if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
        $idserie = htmlspecialchars($_POST['idserie']);
        $nom_serie = htmlspecialchars($_POST['nom_serie']);
        
        try {
            // Vérifier si le nouveau nom existe déjà pour une autre série
            $check = $conn->prepare("SELECT COUNT(*) FROM series WHERE series = ? AND idserie != ?");
            $check->execute([$nom_serie, $idserie]);
            if ($check->fetchColumn() > 0) {
                $response['message'] = "Une série avec ce nom existe déjà";
            } else {
                $sql = "UPDATE series SET series = ? WHERE idserie = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nom_serie, $idserie]);
                $response['success'] = true;
                $response['message'] = "Série modifiée avec succès";
            }
        } catch(PDOException $e) {
            $response['message'] = "Erreur lors de la modification de la série";
        }
    }
    
    // Suppression d'une série
    if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
        $idserie = htmlspecialchars($_POST['idserie']);
        
        try {
            // Vérifier si la série a des filières associées
            $check = $conn->prepare("SELECT COUNT(*) FROM filiere WHERE idserie = ?");
            $check->execute([$idserie]);
            if ($check->fetchColumn() > 0) {
                $response['message'] = "Impossible de supprimer cette série car elle contient des filières";
            } else {
                $sql = "DELETE FROM series WHERE idserie = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$idserie]);
                $response['success'] = true;
                $response['message'] = "Série supprimée avec succès";
            }
        } catch(PDOException $e) {
            $response['message'] = "Erreur lors de la suppression de la série";
        }
    }
    
    echo json_encode($response);
    exit;
} 