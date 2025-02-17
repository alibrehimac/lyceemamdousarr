<?php
require_once 'config.php';
$page_title = "Gestion des Matières par Classe";
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Créer la table matiere_classe si elle n'existe pas
try {
    $sql = "CREATE TABLE IF NOT EXISTS matiere_classe (
        id INT AUTO_INCREMENT PRIMARY KEY,
        idClasse INT NOT NULL,
        id_matiere INT NOT NULL,
        coefficient INT NOT NULL DEFAULT 1,
        FOREIGN KEY (idClasse) REFERENCES classe(idClasse) ON DELETE CASCADE,
        FOREIGN KEY (id_matiere) REFERENCES matieres(id_matiere) ON DELETE CASCADE,
        UNIQUE KEY unique_matiere_classe (idClasse, id_matiere)
    )";
    $conn->exec($sql);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la création de la table : " . $e->getMessage();
}

// Récupérer les classes
try {
    $sql = "SELECT c.idClasse, c.nom_classe, f.nom_filiere, p.annee_scolaire 
            FROM classe c 
            JOIN filiere f ON c.code_filiere = f.code_filiere 
            JOIN promotion p ON c.idpromotion = p.idpromotion 
            ORDER BY p.annee_scolaire DESC, c.nom_classe";
    $stmt = $conn->query($sql);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des classes";
}

// Récupérer toutes les matières
try {
    $sql = "SELECT * FROM matieres ORDER BY nom_matiere";
    $stmt = $conn->query($sql);
    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des matières";
}

// Traitement de l'ajout/modification des coefficients
if (isset($_POST['save_coefficients'])) {
    $idClasse = $_POST['idClasse'];
    $coefficients = $_POST['coefficient'];
    $matieres_selectionnees = $_POST['matieres'] ?? [];

    try {
        $conn->beginTransaction();

        // Supprimer les anciennes associations pour cette classe
        $sql_delete = "DELETE FROM matiere_classe WHERE idClasse = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->execute([$idClasse]);

        // Insérer les nouvelles associations
        $sql_insert = "INSERT INTO matiere_classe (idClasse, id_matiere, coefficient) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);

        foreach ($matieres_selectionnees as $id_matiere) {
            $coefficient = $coefficients[$id_matiere] ?? 1;
            $stmt->execute([$idClasse, $id_matiere, $coefficient]);
        }

        $conn->commit();
        $_SESSION['message'] = "Les coefficients ont été enregistrés avec succès";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Erreur lors de l'enregistrement des coefficients : " . $e->getMessage();
    }

    header('Location: gestion_matieres_classe.php');
    exit();
}

// Récupérer les matières d'une classe spécifique
if (isset($_GET['idClasse'])) {
    try {
        $sql = "SELECT mc.*, m.nom_matiere 
                FROM matiere_classe mc 
                JOIN matieres m ON mc.id_matiere = m.id_matiere 
                WHERE mc.idClasse = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_GET['idClasse']]);
        $matieres_classe = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la récupération des matières de la classe";
    }
}
?>

<!-- En-tête de la page avec fond et ombre -->
<div class="bg-white shadow-sm rounded p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-book-reader text-primary me-2"></i>
            Gestion des Matières par Classe
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                <li class="breadcrumb-item active">Matières par classe</li>
            </ol>
        </nav>
    </div>

    <!-- Menu rapide avec badges -->
    <div class="d-flex gap-2 flex-wrap">
        <a href="gestion_matieres.php" class="btn btn-outline-primary position-relative">
            <i class="fas fa-book me-1"></i>
            Matières
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo count($matieres); ?>
            </span>
        </a>
        <a href="gestion_classes.php" class="btn btn-outline-success position-relative">
            <i class="fas fa-chalkboard me-1"></i>
            Classes
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo count($classes); ?>
            </span>
        </a>
        <a href="gestion_notes.php" class="btn btn-outline-info">
            <i class="fas fa-pen me-1"></i>
            Notes
        </a>
        <a href="gestion_bulletins.php" class="btn btn-outline-warning">
            <i class="fas fa-file-alt me-1"></i>
            Bulletins
        </a>
    </div>
</div>

<!-- Messages d'alerte avec icônes -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <div><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    </div>
<?php endif; ?>

<!-- Sélection de classe avec carte moderne -->
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-graduation-cap text-primary me-2"></i>
                    Configuration des matières
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <label for="idClasse" class="form-label text-muted">Sélectionner une classe</label>
                            <select class="form-select form-select-lg" id="idClasse" name="idClasse" onchange="this.form.submit()">
                                <option value="">Choisir une classe</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo $classe['idClasse']; ?>" 
                                            <?php echo (isset($_GET['idClasse']) && $_GET['idClasse'] == $classe['idClasse']) ? 'selected' : ''; ?>>
                                        <?php echo $classe['nom_classe'] . ' ' . $classe['nom_filiere'] . 
                                              ' (' . $classe['annee_scolaire'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>

                <?php if (isset($_GET['idClasse'])): ?>
                    <form method="POST">
                        <input type="hidden" name="idClasse" value="<?php echo $_GET['idClasse']; ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>Matière</th>
                                        <th style="width: 200px">Coefficient</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matieres as $matiere): 
                                        $matiere_classe = array_filter($matieres_classe ?? [], function($mc) use ($matiere) {
                                            return $mc['id_matiere'] == $matiere['id_matiere'];
                                        });
                                        $coefficient = !empty($matiere_classe) ? current($matiere_classe)['coefficient'] : 1;
                                        $checked = !empty($matiere_classe);
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" 
                                                           name="matieres[]" 
                                                           value="<?php echo $matiere['id_matiere']; ?>"
                                                           <?php echo $checked ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-book text-muted me-2"></i>
                                                <?php echo $matiere['nom_matiere']; ?>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" 
                                                           name="coefficient[<?php echo $matiere['id_matiere']; ?>]" 
                                                           value="<?php echo $coefficient; ?>"
                                                           min="1" step="1">
                                                    <span class="input-group-text">coef.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" name="save_coefficients" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Enregistrer les coefficients
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sélectionner/Désélectionner toutes les matières
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="matieres[]"]')
        .forEach(checkbox => checkbox.checked = this.checked);
});
</script> 