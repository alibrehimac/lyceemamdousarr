<?php
// Démarrer la session et les requires au tout début du fichier
session_start();
require_once('includes/header.php');
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Au début du fichier, après la connexion à la base de données
try {
    // Modifier la structure des tables si nécessaire
    $sql_modifications = [
        "ALTER TABLE eleves MODIFY matricule VARCHAR(20) NOT NULL",
        "ALTER TABLE inscrire MODIFY matricule VARCHAR(20) NOT NULL",
        "ALTER TABLE bulletin MODIFY matricule VARCHAR(20) NOT NULL",
        "ALTER TABLE notes MODIFY matricule VARCHAR(20) NOT NULL"
    ];

    foreach ($sql_modifications as $sql) {
        try {
            $conn->exec($sql);
        } catch(PDOException $e) {
            // Ignorer les erreurs si la colonne est déjà de la bonne taille
            if ($e->getCode() != '42S21' && $e->getCode() != '42000') {
                throw $e;
            }
        }
    }

    // Récupérer la promotion active
    $sql_promotion = "SELECT idpromotion FROM promotion WHERE annee_scolaire = (
                      SELECT MAX(annee_scolaire) FROM promotion)";
    $stmt = $conn->query($sql_promotion);
    $promotion_active = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promotion_active) {
        throw new Exception("Aucune promotion active trouvée");
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la modification de la structure : " . $e->getMessage();
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header('Location: import_eleves.php');
    exit();
}

// Créer la table eleves_sans_matricule si elle n'existe pas
try {
    $sql = "CREATE TABLE IF NOT EXISTS eleves_sans_matricule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(50) NOT NULL,
        prenom VARCHAR(50) NOT NULL,
        date_naiss DATE DEFAULT NULL,
        lieu_naiss VARCHAR(50) DEFAULT NULL,
        sexe VARCHAR(1) DEFAULT NULL,
        adresse TEXT,
        tel VARCHAR(15) DEFAULT NULL,
        pere VARCHAR(50) DEFAULT NULL,
        mere VARCHAR(50) DEFAULT NULL,
        date_import TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la création de la table : " . $e->getMessage();
}

// Récupérer la liste des classes disponibles
try {
    $sql = "SELECT c.idClasse, c.nom_classe, f.nom_filiere, p.annee_scolaire 
            FROM classe c 
            JOIN filiere f ON c.code_filiere = f.code_filiere 
            JOIN promotion p ON c.idpromotion = p.idpromotion 
            ORDER BY p.annee_scolaire DESC, c.nom_classe";
    $stmt = $conn->query($sql);
    $classes_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des classes";
}

// Au début du fichier, après les requires
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction de débogage - à déplacer dans un fichier d'utilitaires
function debug($data, $title = null) {
    ob_start();
    echo '<div style="background: #f4f4f4; padding: 10px; margin: 10px; border: 1px solid #ccc;">';
    if ($title) {
        echo "<h3>$title</h3>";
    }
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    echo '</div>';
    return ob_get_clean(); // Retourner le contenu au lieu de l'afficher directement
}

// Initialiser les compteurs
$eleves_importes = 0;
$eleves_inscrits = 0;
$erreurs = [];

// Fonction pour nettoyer les données
function cleanData($data, $maxLength = null) {
    $data = trim($data);
    $data = str_replace('"', '', $data);
    if ($maxLength) {
        $data = substr($data, 0, $maxLength);
    }
    return empty($data) ? null : $data;
}

// Fonction pour valider les données d'un élève
function validateEleve($data) {
    $errors = [];
    
    // Validation du matricule (obligatoire)
    if (empty($data['matricule'])) {
        $errors[] = "Le matricule est obligatoire";
    }
    
    // Validation du nom et prénom (obligatoires)
    if (empty($data['nom'])) {
        $errors[] = "Le nom est obligatoire";
    }
    if (empty($data['prenom'])) {
        $errors[] = "Le prénom est obligatoire";
    }
    
    // Validation du sexe
    if (!empty($data['sexe']) && !in_array(strtoupper($data['sexe']), ['M', 'F'])) {
        $errors[] = "Le sexe doit être 'M' ou 'F' pour {$data['nom']} {$data['prenom']}";
    }
    
    return $errors;
}

// Traitement de l'importation CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['csv_file']['tmp_name'];
            
            // Vérifier l'extension du fichier
            $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
            if ($extension !== 'csv') {
                throw new Exception("Le fichier doit avoir l'extension .csv");
            }
            
            // Ouvrir le fichier
            $handle = fopen($file, 'r');
            if ($handle === false) {
                throw new Exception("Impossible d'ouvrir le fichier");
            }

            // Commencer une transaction
            $conn->beginTransaction();
            
            // Lire l'en-tête
            $header = fgetcsv($handle, 0, ',');
            if ($header === false) {
                throw new Exception("Le fichier est vide");
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];
            $debug_output = '';

            // Préparer la requête d'insertion
            $sql = "INSERT INTO eleves (matricule, nom, prenom, date_naiss, lieu_naiss, sexe, adresse, tel, pere, mere) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // Préparer la requête de vérification
            $check = $conn->prepare("SELECT COUNT(*) FROM eleves WHERE matricule = ?");

            // Lire et traiter chaque ligne
            while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                if (count($data) < 10) continue;

                $eleve = [
                    'matricule' => cleanData($data[0]),
                    'nom' => cleanData($data[1]),
                    'prenom' => cleanData($data[2]),
                    'date_naiss' => cleanData($data[3]),
                    'lieu_naiss' => cleanData($data[4]),
                    'sexe' => strtoupper(cleanData($data[5])),
                    'adresse' => cleanData($data[6]),
                    'tel' => cleanData($data[7]),
                    'pere' => cleanData($data[8]),
                    'mere' => cleanData($data[9])
                ];

                // Valider les données
                $validation_errors = validateEleve($eleve);
                
                if (empty($validation_errors)) {
                    try {
                        // Vérifier si le matricule existe déjà
                        $check->execute([$eleve['matricule']]);
                        if ($check->fetchColumn() > 0) {
                            $skipped++;
                            $errors[] = "Matricule {$eleve['matricule']} déjà existant pour {$eleve['nom']} {$eleve['prenom']} - ignoré";
                            continue; // Passer à l'enregistrement suivant
                        }
                        
                        // Insérer l'élève
                        $stmt->execute([
                            $eleve['matricule'],
                            $eleve['nom'],
                            $eleve['prenom'],
                            $eleve['date_naiss'],
                            $eleve['lieu_naiss'],
                            $eleve['sexe'],
                            $eleve['adresse'],
                            $eleve['tel'],
                            $eleve['pere'],
                            $eleve['mere']
                        ]);
                        $imported++;
                    } catch (PDOException $e) {
                        // Ignorer l'erreur de doublon et continuer
                        if ($e->getCode() == '23000') { // Code d'erreur MySQL pour doublon
                            $skipped++;
                            $errors[] = "Erreur doublon pour {$eleve['nom']} {$eleve['prenom']} - ignoré";
                            continue;
                        }
                        throw $e; // Relancer les autres types d'erreurs
                    }
                } else {
                    $errors = array_merge($errors, $validation_errors);
                    $skipped++;
                }
            }

            fclose($handle);

            // Commit même s'il y a eu des erreurs
            $conn->commit();
            
            // Préparer le message de résultat
            $message = [];
            if ($imported > 0) {
                $message[] = "$imported élève(s) importé(s) avec succès";
            }
            if ($skipped > 0) {
                $message[] = "$skipped élève(s) ignoré(s)";
            }
            
            $_SESSION['message'] = implode(', ', $message);
            if (!empty($errors)) {
                $_SESSION['error'] = $errors;
            }

        } else {
            throw new Exception("Erreur lors du téléchargement du fichier : " . $_FILES['csv_file']['error']);
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
    }

    header('Location: import_eleves.php');
    exit;
}

// Récupérer la liste des élèves sans matricule
try {
    $sql = "SELECT * FROM eleves_sans_matricule ORDER BY date_import DESC";
    $stmt = $conn->query($sql);
    $eleves_sans_matricule = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'][] = "Erreur lors de la récupération des élèves sans matricule";
    $eleves_sans_matricule = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Import des élèves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Import des élèves</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <h4>Erreurs lors de l'import :</h4>
                <ul>
                    <?php 
                    if (is_array($_SESSION['error'])) {
                        foreach ($_SESSION['error'] as $error) {
                            echo "<li>" . htmlspecialchars($error) . "</li>";
                        }
                    } else {
                        echo "<li>" . htmlspecialchars($_SESSION['error']) . "</li>";
                    }
                    unset($_SESSION['error']);
                    ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Importer un fichier CSV</h5>
                <p class="card-text">
                    Le fichier CSV doit contenir les colonnes suivantes dans cet ordre :
                    <ul>
                        <li>matricule</li>
                        <li>nom</li>
                        <li>prenom</li>
                        <li>date_naiss (YYYY-MM-DD)</li>
                        <li>lieu_naiss</li>
                        <li>sexe (M/F)</li>
                        <li>adresse</li>
                        <li>tel</li>
                        <li>pere</li>
                        <li>mere</li>
                    </ul>
                </p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Fichier CSV</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Importer</button>
                </form>
            </div>
        </div>

        <!-- Liste des élèves sans matricule -->
        <?php if (!empty($eleves_sans_matricule)): ?>
        <div class="card" id="sans-matricule">
            <div class="card-header">
                <h5>
                    Élèves sans matricule 
                    <span class="badge bg-warning"><?php echo count($eleves_sans_matricule); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Date de naissance</th>
                                <th>Lieu de naissance</th>
                                <th>Date d'import</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves_sans_matricule as $eleve): ?>
                            <tr>
                                <td><?php echo $eleve['nom']; ?></td>
                                <td><?php echo $eleve['prenom']; ?></td>
                                <td><?php echo $eleve['date_naiss']; ?></td>
                                <td><?php echo $eleve['lieu_naiss']; ?></td>
                                <td><?php echo $eleve['date_import']; ?></td>
                                <td>
                                    <a href="attribuer_matricule.php?id=<?php echo $eleve['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        Attribuer un matricule
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Afficher la section de debug uniquement si nécessaire -->
        <?php if (!empty($debug_output)): ?>
            <div class="debug-section">
                <?php echo $debug_output; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 