<?php
require_once('includes/header.php');
require_once 'config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Récupérer la promotion active
try {
    $sql = "SELECT idpromotion, annee_scolaire 
            FROM promotion 
            WHERE annee_scolaire = (SELECT MAX(annee_scolaire) FROM promotion)";
    $stmt = $conn->query($sql);
    $promotion_active = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promotion_active) {
        $_SESSION['error'] = "Aucune promotion active trouvée";
        header('Location: gestion_eleves.php');
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération de la promotion";
    header('Location: gestion_eleves.php');
    exit();
}

// Traitement de l'inscription
if (isset($_POST['inscrire']) && isset($_POST['eleves']) && isset($_POST['classe'])) {
    try {
        $conn->beginTransaction();
        
        // Récupérer les informations de la classe
        $stmt = $conn->prepare("SELECT code_filiere FROM classe WHERE idClasse = ?");
        $stmt->execute([$_POST['classe']]);
        $classe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$classe) {
            throw new Exception("Classe non trouvée");
        }
        
        // Préparer la requête d'inscription
        $insert = $conn->prepare("INSERT INTO inscrire (idpromotion, matricule, code_filiere, idClasse) 
                                VALUES (?, ?, ?, ?)");
        
        $inscrits = 0;
        foreach ($_POST['eleves'] as $matricule) {
            try {
                $insert->execute([
                    $promotion_active['idpromotion'],
                    $matricule,
                    $classe['code_filiere'],
                    $_POST['classe']
                ]);
                $inscrits++;
            } catch(PDOException $e) {
                // Ignorer les doublons d'inscription
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        $conn->commit();
        $_SESSION['message'] = "$inscrits élève(s) inscrit(s) avec succès";
        header('Location: valider_inscription.php');
        exit();
        
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Erreur lors de l'inscription : " . $e->getMessage();
    }
}

// Récupérer les classes disponibles
try {
    $sql = "SELECT c.idClasse, c.nom_classe, f.nom_filiere 
            FROM classe c 
            JOIN filiere f ON c.code_filiere = f.code_filiere 
            WHERE c.idpromotion = ? 
            ORDER BY f.nom_filiere, c.nom_classe";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$promotion_active['idpromotion']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des classes";
}

// Récupérer les élèves non inscrits pour l'année active
$sql = "SELECT e.* 
        FROM eleves e 
        WHERE NOT EXISTS (
            SELECT 1 
            FROM inscrire i 
            WHERE i.matricule = e.matricule 
            AND i.idpromotion = ?
        )
        ORDER BY e.nom, e.prenom";
$stmt = $conn->prepare($sql);
$stmt->execute([$promotion_active['idpromotion']]);
$eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation des inscriptions</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Validation des inscriptions</h1>
            <div class="badge bg-primary">
                Année scolaire : <?php echo $promotion_active['annee_scolaire']; ?>
            </div>
        </div>

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
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($eleves) && !empty($classes)): ?>
            <form method="POST" class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sélectionner une classe</label>
                            <select name="classe" class="form-select" required>
                                <option value="">Choisir une classe...</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo $classe['idClasse']; ?>">
                                        <?php echo $classe['nom_classe'] . ' ' . $classe['nom_filiere']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Matricule</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Date de naissance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eleves as $eleve): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="eleves[]" 
                                                   value="<?php echo $eleve['matricule']; ?>" 
                                                   class="form-check-input">
                                        </td>
                                        <td><?php echo $eleve['matricule']; ?></td>
                                        <td><?php echo $eleve['nom']; ?></td>
                                        <td><?php echo $eleve['prenom']; ?></td>
                                        <td><?php echo $eleve['date_naiss']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" name="inscrire" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Valider les inscriptions
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucun élève à inscrire pour le moment.
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.getElementById('selectAll').addEventListener('change', function() {
        var checkboxes = document.getElementsByName('eleves[]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });
    </script>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 