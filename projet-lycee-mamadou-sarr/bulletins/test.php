<?php
// Inclure le fichier de configuration
require_once 'config.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction de débogage
function debug($data, $title = null) {
    echo '<div style="background: #f4f4f4; padding: 10px; margin: 10px; border: 1px solid #ccc;">';
    if ($title) {
        echo "<h3>$title</h3>";
    }
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    echo '</div>';
}

// Traitement du fichier CSV
if (isset($_POST["submit"])) {
    try {
        // Debug des données reçues
        debug($_FILES, 'Fichier reçu');
        
        if ($_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['csv_file']['tmp_name'];
            $fileName = $_FILES['csv_file']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if ($fileExtension === 'csv') {
                // Début de la transaction
                $conn->beginTransaction();
                
                if (($handle = fopen($fileTmpPath, "r")) !== false) {
                    // Ignorer l'en-tête
                    $header = fgetcsv($handle, 1000, ",");
                    debug($header, 'En-têtes du CSV');
                    
                    // Statistiques
                    $stats = [
                        'total_lignes' => 0,
                        'succes' => 0,
                        'doublons' => 0,
                        'erreurs' => 0
                    ];
                    
                    // Préparer les requêtes
                    $check = $conn->prepare("SELECT COUNT(*) FROM eleves WHERE matricule = ?");
                    $insert = $conn->prepare("INSERT INTO eleves (matricule, nom, prenom, date_naiss, lieu_naiss, sexe, adresse, tel, pere, mere)
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $doublons = [];
                    $erreurs = [];
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                        $stats['total_lignes']++;
                        
                        if (count($data) == 10) {
                            // Nettoyer les données
                            $matricule = trim($data[0]);
                            $nom = trim($data[1]);
                            $prenom = trim($data[2]);
                            $date_naiss = trim($data[3]) ?: null;
                            $lieu_naiss = trim($data[4]) ?: null;
                            $sexe = trim($data[5]);
                            $adresse = trim($data[6]) ?: null;
                            $tel = trim($data[7]) ?: null;
                            $pere = trim($data[8]) ?: null;
                            $mere = trim($data[9]) ?: null;
                            
                            // Vérifier si le matricule existe déjà
                            $check->execute([$matricule]);
                            if ($check->fetchColumn() > 0) {
                                $stats['doublons']++;
                                $doublons[] = [
                                    'matricule' => $matricule,
                                    'nom' => $nom,
                                    'prenom' => $prenom
                                ];
                                continue; // Passer à la ligne suivante
                            }
                            
                            try {
                                $insert->execute([
                                    $matricule, $nom, $prenom, $date_naiss, 
                                    $lieu_naiss, $sexe, $adresse, $tel, 
                                    $pere, $mere
                                ]);
                                $stats['succes']++;
                            } catch(PDOException $e) {
                                $stats['erreurs']++;
                                $erreurs[] = "Ligne {$stats['total_lignes']}: Erreur pour $nom $prenom ($matricule)";
                            }
                        } else {
                            $stats['erreurs']++;
                            $erreurs[] = "Ligne {$stats['total_lignes']}: Nombre de colonnes incorrect";
                        }
                    }
                    
                    fclose($handle);
                    
                    // Afficher le résumé
                    echo "<div class='alert alert-info'>";
                    echo "<h4>Résumé de l'import</h4>";
                    echo "<ul>";
                    echo "<li>Total de lignes traitées : {$stats['total_lignes']}</li>";
                    echo "<li>Élèves importés avec succès : {$stats['succes']}</li>";
                    echo "<li>Doublons ignorés : {$stats['doublons']}</li>";
                    echo "<li>Erreurs : {$stats['erreurs']}</li>";
                    echo "</ul>";
                    echo "</div>";
                    
                    // Afficher les doublons si présents
                    if (!empty($doublons)) {
                        echo "<div class='alert alert-warning'>";
                        echo "<h4>Matricules déjà existants (ignorés)</h4>";
                        echo "<ul>";
                        foreach ($doublons as $doublon) {
                            echo "<li>Matricule {$doublon['matricule']} : {$doublon['nom']} {$doublon['prenom']}</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }
                    
                    // Afficher les erreurs si présentes
                    if (!empty($erreurs)) {
                        echo "<div class='alert alert-danger'>";
                        echo "<h4>Erreurs rencontrées</h4>";
                        echo "<ul>";
                        foreach ($erreurs as $erreur) {
                            echo "<li>$erreur</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }
                    
                    $conn->commit();
                } else {
                    throw new Exception("Impossible d'ouvrir le fichier CSV.");
                }
            } else {
                throw new Exception("Veuillez télécharger un fichier CSV.");
            }
        } else {
            throw new Exception("Erreur lors du téléchargement du fichier.");
        }
    } catch(Exception $e) {
        debug($e, 'Erreur survenue');
        echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test d'import CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Test d'import CSV</h1>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Importer un fichier CSV</h5>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Sélectionnez un fichier CSV :</label>
                        <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Importer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
