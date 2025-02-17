<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    // Ajout d'une filière
    if (isset($_POST['action']) && $_POST['action'] === 'add_filiere') {
        $idserie = htmlspecialchars($_POST['serie']);
        $nom_filiere = htmlspecialchars($_POST['nom_filiere']);
        
        try {
            // Vérifier si le nom de la filière existe déjà dans cette série
            $check = $conn->prepare("SELECT COUNT(*) FROM filiere WHERE nom_filiere = ? AND idserie = ?");
            $check->execute([$nom_filiere, $idserie]);
            if ($check->fetchColumn() > 0) {
                $response['message'] = "Cette filière existe déjà dans cette série";
            } else {
                $sql = "INSERT INTO filiere (nom_filiere, idserie) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nom_filiere, $idserie]);
                
                $response['success'] = true;
                $response['message'] = "Filière ajoutée avec succès";
                
                // Récupérer les informations de la nouvelle filière
                $sql = "SELECT f.*, s.series 
                        FROM filiere f 
                        JOIN series s ON f.idserie = s.idserie 
                        WHERE f.code_filiere = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$conn->lastInsertId()]);
                $response['data'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch(PDOException $e) {
            $response['message'] = "Erreur lors de l'ajout de la filière: " . $e->getMessage();
        }
    }

    // Modification d'une filière
    if (isset($_POST['action']) && $_POST['action'] === 'edit_filiere') {
        $code_filiere = htmlspecialchars($_POST['code_filiere']);
        $nom_filiere = htmlspecialchars($_POST['nom_filiere']);
        $idserie = htmlspecialchars($_POST['serie']);
        
        try {
            // Vérifier si le nom existe déjà dans la même série (sauf pour la filière actuelle)
            $check = $conn->prepare("SELECT COUNT(*) FROM filiere WHERE nom_filiere = ? AND idserie = ? AND code_filiere != ?");
            $check->execute([$nom_filiere, $idserie, $code_filiere]);
            if ($check->fetchColumn() > 0) {
                $response['message'] = "Une filière avec ce nom existe déjà dans cette série";
            } else {
                $sql = "UPDATE filiere SET nom_filiere = ?, idserie = ? WHERE code_filiere = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nom_filiere, $idserie, $code_filiere]);
                $response['success'] = true;
                $response['message'] = "Filière modifiée avec succès";
            }
        } catch(PDOException $e) {
            $response['message'] = "Erreur lors de la modification de la filière";
        }
    }

    // Suppression d'une filière
    if (isset($_POST['action']) && $_POST['action'] === 'delete_filiere') {
        $code_filiere = htmlspecialchars($_POST['code_filiere']);
        
        try {
            // Vérifier si la filière a des associations
            $check = $conn->prepare("SELECT COUNT(*) FROM associer WHERE code_filiere = ?");
            $check->execute([$code_filiere]);
            if ($check->fetchColumn() > 0) {
                $response['message'] = "Impossible de supprimer cette filière car elle est associée à des matières";
            } else {
                $sql = "DELETE FROM filiere WHERE code_filiere = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$code_filiere]);
                $response['success'] = true;
                $response['message'] = "Filière supprimée avec succès";
            }
        } catch(PDOException $e) {
            $response['message'] = "Erreur lors de la suppression de la filière";
        }
    }

    // Récupération des filières
    if (isset($_POST['action']) && $_POST['action'] === 'get_filieres') {
        $idserie = isset($_POST['idserie']) ? htmlspecialchars($_POST['idserie']) : null;
        
        try {
            $sql = "SELECT f.*, s.series 
                    FROM filiere f 
                    JOIN series s ON f.idserie = s.idserie";
            $params = [];
            
            if ($idserie) {
                $sql .= " WHERE f.idserie = ?";
                $params[] = $idserie;
            }
            
            $sql .= " ORDER BY s.series, f.nom_filiere";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $response['success'] = true;
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $response['message'] = "Erreur lors de la récupération des filières";
        }
    }
    
    echo json_encode($response);
    exit;
} 