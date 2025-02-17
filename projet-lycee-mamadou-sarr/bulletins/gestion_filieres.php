<?php
$page = 'administration';
$subpage = 'filieres';

require_once('config.php');
require_once('includes/header.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Récupération des séries pour le formulaire
try {
    $sql_series = "SELECT * FROM series ORDER BY series";
    $stmt_series = $conn->query($sql_series);
    $series = $stmt_series->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des séries";
}

// Récupération des promotions pour le formulaire des classes
try {
    $sql_promotions = "SELECT * FROM promotion ORDER BY annee_scolaire DESC";
    $stmt_promotions = $conn->query($sql_promotions);
    $promotions = $stmt_promotions->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des promotions";
}

// Traitement de l'ajout d'une filière
if (isset($_POST['ajouter_filiere'])) {
    $nom_filiere = htmlspecialchars($_POST['nom_filiere']);
    $idserie = $_POST['idserie'];
    
    try {
        // Vérifier si la filière existe déjà dans cette série
        $check = $conn->prepare("SELECT COUNT(*) FROM filiere WHERE nom_filiere = ? AND idserie = ?");
        $check->execute([$nom_filiere, $idserie]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Cette filière existe déjà dans cette série";
        } else {
            $sql = "INSERT INTO filiere (nom_filiere, idserie) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nom_filiere, $idserie]);
            $_SESSION['success'] = "Filière ajoutée avec succès";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de la filière";
    }
    header('Location: gestion_filieres.php');
    exit();
}

// Traitement de la modification d'une filière
if (isset($_POST['modifier_filiere'])) {
    $code_filiere = $_POST['code_filiere'];
    $nom_filiere = htmlspecialchars($_POST['nom_filiere']);
    $idserie = $_POST['idserie'];
    
    try {
        $sql = "UPDATE filiere SET nom_filiere = ?, idserie = ? WHERE code_filiere = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nom_filiere, $idserie, $code_filiere]);
        $_SESSION['success'] = "Filière modifiée avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification de la filière";
    }
    header('Location: gestion_filieres.php');
    exit();
}

// Traitement de la suppression d'une filière
if (isset($_POST['supprimer_filiere'])) {
    $code_filiere = $_POST['code_filiere'];
    
    try {
        // Vérifier si la filière a des classes associées
        $check = $conn->prepare("SELECT COUNT(*) FROM classe WHERE code_filiere = ?");
        $check->execute([$code_filiere]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Impossible de supprimer cette filière car elle contient des classes";
        } else {
            $sql = "DELETE FROM filiere WHERE code_filiere = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$code_filiere]);
            $_SESSION['success'] = "Filière supprimée avec succès";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression de la filière";
    }
    header('Location: gestion_filieres.php');
    exit();
}

// Traitement de l'ajout d'une classe à une filière
if (isset($_POST['ajouter_classe'])) {
    $code_filiere = $_POST['code_filiere'];
    $nom_classe = htmlspecialchars($_POST['nom_classe']);
    $idpromotion = $_POST['idpromotion'];
    
    try {
        // Vérifier si la classe existe déjà pour cette filière et promotion
        $check = $conn->prepare("SELECT COUNT(*) FROM classe WHERE nom_classe = ? AND code_filiere = ? AND idpromotion = ?");
        $check->execute([$nom_classe, $code_filiere, $idpromotion]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Cette classe existe déjà pour cette filière et promotion";
        } else {
            $sql = "INSERT INTO classe (nom_classe, code_filiere, idpromotion) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nom_classe, $code_filiere, $idpromotion]);
            $_SESSION['success'] = "Classe ajoutée avec succès";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de la classe";
    }
    header('Location: gestion_filieres.php');
    exit();
}

// Récupération des filières avec leurs séries et classes
try {
    $sql = "SELECT f.*, s.series, 
            (SELECT COUNT(*) FROM classe c WHERE c.code_filiere = f.code_filiere) as nb_classes
            FROM filiere f 
            JOIN series s ON f.idserie = s.idserie 
            ORDER BY s.series, f.nom_filiere";
    $stmt = $conn->query($sql);
    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des filières";
    $filieres = [];
}
?>

<div class="container mt-4">
    <h2 class="mb-4">
        <i class="fas fa-graduation-cap me-2"></i>
        Gestion des filières
    </h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Bouton d'ajout -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#ajoutFiliereModal">
        <i class="fas fa-plus me-2"></i>
        Ajouter une filière
    </button>

    <!-- Tableau des filières -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Série</th>
                            <th>Filière</th>
                            <th>Nombre de classes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($filieres)): ?>
                            <?php foreach ($filieres as $filiere): ?>
                            <tr>
                                <td><?php echo $filiere['series']; ?></td>
                                <td><?php echo $filiere['nom_filiere']; ?></td>
                                <td><?php echo $filiere['nb_classes']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                            data-bs-target="#ajoutClasseModal<?php echo $filiere['code_filiere']; ?>">
                                        <i class="fas fa-plus me-1"></i> Classe
                                    </button>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                            data-bs-target="#modifierFiliereModal<?php echo $filiere['code_filiere']; ?>">
                                        <i class="fas fa-edit me-1"></i> Modifier
                                    </button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                            data-bs-target="#supprimerFiliereModal<?php echo $filiere['code_filiere']; ?>">
                                        <i class="fas fa-trash me-1"></i> Supprimer
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Aucune filière trouvée</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Filière -->
    <div class="modal fade" id="ajoutFiliereModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une filière</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Série</label>
                            <select class="form-select" name="idserie" required>
                                <option value="">Sélectionner une série</option>
                                <?php foreach ($series as $serie): ?>
                                    <option value="<?php echo $serie['idserie']; ?>">
                                        <?php echo $serie['series']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom de la filière</label>
                            <input type="text" class="form-control" name="nom_filiere" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" name="ajouter_filiere" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals pour chaque filière -->
    <?php foreach ($filieres as $filiere): ?>
        <!-- Modal Modification Filière -->
        <div class="modal fade" id="modifierFiliereModal<?php echo $filiere['code_filiere']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la filière</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="code_filiere" value="<?php echo $filiere['code_filiere']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Série</label>
                                <select class="form-select" name="idserie" required>
                                    <?php foreach ($series as $serie): ?>
                                        <option value="<?php echo $serie['idserie']; ?>"
                                            <?php echo ($serie['idserie'] == $filiere['idserie']) ? 'selected' : ''; ?>>
                                            <?php echo $serie['series']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom de la filière</label>
                                <input type="text" class="form-control" name="nom_filiere" 
                                       value="<?php echo $filiere['nom_filiere']; ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" name="modifier_filiere" class="btn btn-primary">Modifier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Suppression Filière -->
        <div class="modal fade" id="supprimerFiliereModal<?php echo $filiere['code_filiere']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Êtes-vous sûr de vouloir supprimer la filière "<?php echo $filiere['nom_filiere']; ?>" ?
                        <?php if ($filiere['nb_classes'] > 0): ?>
                            <div class="alert alert-warning mt-2">
                                Attention : Cette filière contient <?php echo $filiere['nb_classes']; ?> classe(s).
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <form method="POST">
                            <input type="hidden" name="code_filiere" value="<?php echo $filiere['code_filiere']; ?>">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" name="supprimer_filiere" class="btn btn-danger">Supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ajout Classe -->
        <div class="modal fade" id="ajoutClasseModal<?php echo $filiere['code_filiere']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter une classe à <?php echo $filiere['nom_filiere']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="code_filiere" value="<?php echo $filiere['code_filiere']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nom de la classe</label>
                                <input type="text" class="form-control" name="nom_classe" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Année scolaire</label>
                                <select class="form-select" name="idpromotion" required>
                                    <option value="">Sélectionner une année scolaire</option>
                                    <?php foreach ($promotions as $promotion): ?>
                                        <option value="<?php echo $promotion['idpromotion']; ?>">
                                            <?php echo $promotion['annee_scolaire']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" name="ajouter_classe" class="btn btn-primary">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once('includes/footer.php'); ?>
