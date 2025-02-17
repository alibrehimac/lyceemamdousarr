<?php
// Fonction pour démarrer la session de manière sécurisée
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configuration sécurisée des cookies de session
        $secure = true; // Si vous utilisez HTTPS
        $httponly = true;
        
        session_set_cookie_params([
            'lifetime' => 3600, // 1 heure
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly
        ]);
        
        session_start();
        
        // Régénérer l'ID de session périodiquement pour prévenir la fixation de session
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } else if (time() - $_SESSION['last_regeneration'] > 1800) {
            // Régénérer toutes les 30 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Appeler initSession au début du fichier
initSession();

function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo "<div class='alert alert-{$alert['type']} alert-dismissible fade show' role='alert'>
                {$alert['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['alert']);
    }
}

function checkPermission($requiredRole = 'admin') {
    if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== $requiredRole) {
        header('Location: login.php');
        exit();
    }
}

function getSeries($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM series ORDER BY series");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getFilieres($conn, $idserie = null) {
    try {
        $sql = "SELECT f.*, s.series 
                FROM filiere f 
                JOIN series s ON f.idserie = s.idserie";
        if ($idserie) {
            $sql .= " WHERE f.idserie = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idserie]);
        } else {
            $stmt = $conn->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
} 