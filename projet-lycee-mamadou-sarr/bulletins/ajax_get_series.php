<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = ['success' => false, 'data' => [], 'message' => ''];
    
    try {
        $sql = "SELECT s.*, 
                (SELECT COUNT(*) FROM filiere f WHERE f.idserie = s.idserie) as nb_filieres 
                FROM series s 
                ORDER BY s.series";
        $stmt = $conn->query($sql);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['success'] = true;
    } catch(PDOException $e) {
        $response['message'] = "Erreur lors de la récupération des séries";
    }
    
    echo json_encode($response);
    exit;
} 