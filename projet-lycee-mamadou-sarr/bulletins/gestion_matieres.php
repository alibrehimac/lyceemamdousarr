<?php
// Définir la page active pour le menu
$page = 'administration';
$subpage = 'matieres';

require_once('config.php');
require_once('includes/header.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Récupérer la liste des promotions
try {
    $sql = "SELECT * FROM promotion ORDER BY annee_scolaire DESC";
    $stmt = $conn->query($sql);
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la promotion active (la plus récente)
    $promotion_active = reset($promotions);
    $idpromotion = isset($_GET['idpromotion']) ? intval($_GET['idpromotion']) : $promotion_active['idpromotion'];
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des promotions";
}

// Traitement de l'ajout d'une matière
if (isset($_POST['ajouter'])) {
    $nom_matiere = htmlspecialchars($_POST['nom_matiere']);
    
    // Vérifier si la matière existe déjà
    $check = $conn->prepare("SELECT COUNT(*) FROM matieres WHERE nom_matiere = ?");
    $check->execute([$nom_matiere]);
    if ($check->fetchColumn() > 0) {
        $_SESSION['error'] = "Cette matière existe déjà";
        header('Location: gestion_matieres.php');
        exit();
    }
    
    try {
        $sql = "INSERT INTO matieres (nom_matiere) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nom_matiere]);
        
        $_SESSION['message'] = "Matière ajoutée avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de la matière";
    }
    
    header('Location: gestion_matieres.php');
    exit();
}

// Traitement de la modification d'une matière
if (isset($_POST['modifier'])) {
    $id_matiere = $_POST['id_matiere'];
    $nom_matiere = htmlspecialchars($_POST['nom_matiere']);
    
    try {
        $sql = "UPDATE matieres SET nom_matiere = ? WHERE id_matiere = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nom_matiere, $id_matiere]);
        
        $_SESSION['message'] = "Matière modifiée avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification de la matière";
    }
    
    header('Location: gestion_matieres.php');
    exit();
}

// Traitement de la suppression d'une matière
if (isset($_POST['supprimer'])) {
    $id_matiere = $_POST['id_matiere'];
    
    try {
        $sql = "DELETE FROM matieres WHERE id_matiere = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_matiere]);
        
        $_SESSION['message'] = "Matière supprimée avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression de la matière";
    }
    
    header('Location: gestion_matieres.php');
    exit();
}

// Récupération de la liste des matières
$sql = "SELECT * FROM matieres ORDER BY nom_matiere";
$stmt = $conn->query($sql);
$matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <h2 class="mb-4">
        <i class="fas fa-book me-2"></i>
        Gestion des matières
    </h2>

    <!-- Sélecteur d'année scolaire -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Année scolaire</label>
                    <select name="idpromotion" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($promotions as $promotion): ?>
                            <option value="<?php echo $promotion['idpromotion']; ?>"
                                    <?php echo ($idpromotion == $promotion['idpromotion']) ? 'selected' : ''; ?>>
                                <?php echo $promotion['annee_scolaire']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
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

    <!-- Bouton d'ajout -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#ajoutModal">
        <i class="fas fa-plus me-2"></i>
        Ajouter une matière
    </button>

    <!-- Tableau des matières -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom de la matière</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matieres as $matiere): ?>
                        <tr>
                            <td><?php echo $matiere['id_matiere']; ?></td>
                            <td><?php echo $matiere['nom_matiere']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                        data-bs-target="#modifierModal<?php echo $matiere['id_matiere']; ?>">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                        data-bs-target="#supprimerModal<?php echo $matiere['id_matiere']; ?>">
                                    <i class="fas fa-trash me-1"></i> Supprimer
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Ajout -->
    <div class="modal fade" id="ajoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une matière</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="idpromotion" value="<?php echo $idpromotion; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de la matière</label>
                            <input type="text" class="form-control" name="nom_matiere" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" name="ajouter" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals Modification et Suppression pour chaque matière -->
    <?php foreach ($matieres as $matiere): ?>
        <!-- Modal Modification -->
        <div class="modal fade" id="modifierModal<?php echo $matiere['id_matiere']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la matière</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="id_matiere" value="<?php echo $matiere['id_matiere']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nom de la matière</label>
                                <input type="text" class="form-control" name="nom_matiere" 
                                       value="<?php echo $matiere['nom_matiere']; ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" name="modifier" class="btn btn-primary">Modifier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Suppression -->
        <div class="modal fade" id="supprimerModal<?php echo $matiere['id_matiere']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Êtes-vous sûr de vouloir supprimer la matière "<?php echo $matiere['nom_matiere']; ?>" ?
                    </div>
                    <div class="modal-footer">
                        <form method="POST">
                            <input type="hidden" name="id_matiere" value="<?php echo $matiere['id_matiere']; ?>">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" name="supprimer" class="btn btn-danger">Supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once('includes/footer.php'); ?>
