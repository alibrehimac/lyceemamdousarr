<?php
require_once('includes/header.php');
require_once 'config.php';
$page_title = "Gestion des Trimestres";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Vérifier si une promotion est sélectionnée
if (!isset($_GET['idpromotion'])) {
    header('Location: gestion_promotions.php');
    exit();
}

$idpromotion = $_GET['idpromotion'];

// Récupérer les informations de la promotion
try {
    $stmt = $conn->prepare("SELECT * FROM promotion WHERE idpromotion = ?");
    $stmt->execute([$idpromotion]);
    $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promotion) {
        $_SESSION['error'] = "Promotion non trouvée";
        header('Location: gestion_promotions.php');
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération de la promotion";
    header('Location: gestion_promotions.php');
    exit();
}

// Vérifier et mettre à jour la structure de la table si nécessaire
try {
    $conn->query("SELECT idpromotion FROM trimestres LIMIT 1");
} catch(PDOException $e) {
    if (strpos($e->getMessage(), "Unknown column 'idpromotion'") !== false) {
        try {
            // Ajouter la colonne idpromotion
            $conn->exec("ALTER TABLE trimestres ADD COLUMN idpromotion INT NOT NULL");
            $conn->exec("ALTER TABLE trimestres ADD FOREIGN KEY (idpromotion) REFERENCES promotion(idpromotion)");
        } catch(PDOException $e2) {
            $_SESSION['error'] = "Erreur lors de la modification de la structure de la base de données";
        }
    }
}

// Traitement de l'ajout d'un trimestre
if (isset($_POST['ajouter'])) {
    $nom_trimestre = htmlspecialchars($_POST['nom_trimestre']);
    
    try {
        // Vérifier si le trimestre existe déjà pour cette promotion
        $check = $conn->prepare("SELECT COUNT(*) FROM trimestres WHERE trimestre = ? AND idpromotion = ?");
        $check->execute([$nom_trimestre, $idpromotion]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Ce trimestre existe déjà pour cette promotion";
        } else {
            // Vérifier le nombre de trimestres existants
            $check = $conn->prepare("SELECT COUNT(*) FROM trimestres WHERE idpromotion = ?");
            $check->execute([$idpromotion]);
            if ($check->fetchColumn() >= 3) {
                $_SESSION['error'] = "Une promotion ne peut pas avoir plus de 3 trimestres";
            } else {
                $sql = "INSERT INTO trimestres (trimestre, idpromotion) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nom_trimestre, $idpromotion]);
                $_SESSION['message'] = "Trimestre ajouté avec succès";
            }
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout du trimestre";
    }
    
    header("Location: gestion_trimestres.php?idpromotion=" . $idpromotion);
    exit();
}

// Traitement de la modification
if (isset($_POST['modifier'])) {
    $idperiode = $_POST['idperiode'];
    $trimestre = htmlspecialchars($_POST['trimestre']);
    $idpromotion = $_POST['idpromotion'];

    try {
        $sql = "UPDATE trimestres SET trimestre = ?, idpromotion = ? WHERE idperiode = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$trimestre, $idpromotion, $idperiode]);
        $_SESSION['success'] = "Trimestre modifié avec succès";
        header('Location: gestion_trimestres.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification : " . $e->getMessage();
    }
}

// Récupération des trimestres
try {
    $sql = "SELECT * FROM trimestres WHERE idpromotion = ? ORDER BY trimestre";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$idpromotion]);
    $trimestres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des trimestres";
    $trimestres = [];
}

// Récupérer la liste des promotions pour le formulaire
$sql_promotions = "SELECT * FROM promotion ORDER BY annee_scolaire DESC";
$stmt_promotions = $conn->query($sql_promotions);
$promotions = $stmt_promotions->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        Gestion des Trimestres
        <small class="text-muted">Promotion <?php echo htmlspecialchars($promotion['annee_scolaire']); ?></small>
    </h1>

    <div class="mb-4">
        <a href="gestion_promotions.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour aux promotions
        </a>
    </div>

    <!-- Formulaire d'ajout -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus me-1"></i>
            Ajouter un trimestre
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="nom_trimestre" class="form-label">Nom du trimestre</label>
                    <input type="text" class="form-control" id="nom_trimestre" name="nom_trimestre" 
                           required placeholder="ex: Premier trimestre">
                </div>
                <div class="col-12">
                    <button type="submit" name="ajouter" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des trimestres -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Liste des trimestres
        </div>
        <div class="card-body">
            <?php if (count($trimestres) < 3): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Il reste <?php echo 3 - count($trimestres); ?> trimestre(s) à ajouter.
            </div>
            <?php endif; ?>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom du trimestre</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($trimestres)): ?>
                        <?php foreach ($trimestres as $trimestre): ?>
                        <tr>
                            <td><?php echo $trimestre['idperiode']; ?></td>
                            <td><?php echo $trimestre['trimestre']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modifierModal<?php echo $trimestre['idperiode']; ?>">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmerSuppression(<?php echo $trimestre['idperiode']; ?>)">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Modification -->
                        <div class="modal fade" id="modifierModal<?php echo $trimestre['idperiode']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier le trimestre</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="idperiode" 
                                                   value="<?php echo $trimestre['idperiode']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Trimestre</label>
                                                <input type="text" class="form-control" name="trimestre" 
                                                       value="<?php echo htmlspecialchars($trimestre['trimestre']); ?>" 
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Année Scolaire</label>
                                                <select class="form-select" name="idpromotion" required>
                                                    <?php foreach($promotions as $promotion): ?>
                                                        <option value="<?php echo $promotion['idpromotion']; ?>"
                                                                <?php if($promotion['idpromotion'] == $trimestre['idpromotion']) echo 'selected'; ?>>
                                                            <?php echo htmlspecialchars($promotion['annee_scolaire']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" name="modifier" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">Aucun trimestre trouvé</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmerSuppression(idperiode) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce trimestre ?')) {
        window.location.href = `supprimer_trimestre.php?idperiode=${idperiode}&idpromotion=<?php echo $idpromotion; ?>`;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 