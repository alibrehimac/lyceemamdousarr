<?php
// Ajouter ces variables avant d'inclure le header pour indiquer la page active
$page = 'administration';
$subpage = 'matieres';

require_once('config.php');
require_once('includes/header.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID de la série
$idserie = isset($_GET['idserie']) ? intval($_GET['idserie']) : 0;

// Récupérer les filières de la série
$stmt = $conn->prepare("SELECT * FROM filiere WHERE idserie = ?");
$stmt->execute([$idserie]);
$filieres = $stmt->fetchAll();

// Récupérer l'ID de la filière sélectionnée
$code_filiere = isset($_GET['code_filiere']) ? intval($_GET['code_filiere']) : 0;

// Récupérer les informations de la série
$stmt = $conn->prepare("SELECT * FROM series WHERE idserie = ?");
$stmt->execute([$idserie]);
$serie = $stmt->fetch();

if (!$serie) {
    $_SESSION['error'] = "Série non trouvée";
    header('Location: gestion_series.php');
    exit();
}

// Modifier la requête pour récupérer les matières et leurs coefficients
$sql = "SELECT m.*, a.coefficient 
        FROM matieres m 
        LEFT JOIN associer a ON m.id_matiere = a.id_matiere 
        AND a.code_filiere = :code_filiere 
        ORDER BY m.nom_matiere";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute(['code_filiere' => $code_filiere]);
    $matieres = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des matières : " . $e->getMessage();
}

// Modifier la partie traitement des coefficients
if (isset($_POST['save_coefficients'])) {
    try {
        $conn->beginTransaction();
        
        // Supprimer les anciens coefficients
        $delete = $conn->prepare("DELETE FROM associer WHERE code_filiere = ?");
        $delete->execute([$code_filiere]);
        
        // Insérer les nouveaux coefficients uniquement pour les matières cochées
        if (isset($_POST['matieres']) && is_array($_POST['matieres'])) {
            $insert = $conn->prepare("INSERT INTO associer (code_filiere, id_matiere, coefficient) VALUES (?, ?, ?)");
            
            foreach ($_POST['matieres'] as $id_matiere) {
                if (isset($_POST['coefficient'][$id_matiere])) {
                    $coef = floatval($_POST['coefficient'][$id_matiere]);
                    if ($coef > 0) {
                        $insert->execute([$code_filiere, $id_matiere, $coef]);
                    }
                }
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "Les coefficients ont été enregistrés avec succès";
        header("Location: gestion_matieres_serie.php?idserie=" . $idserie . "&code_filiere=" . $code_filiere);
        exit();
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Erreur lors de l'enregistrement des coefficients : " . $e->getMessage();
    }
}
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-3">
                <i class="fas fa-book-reader me-2"></i>
                Gestion des matières
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="gestion_series.php">Séries</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($serie['series']); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Sélecteur de filière -->
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-filter me-1"></i>
                    Sélectionner une filière
                </label>
                <select onchange="window.location.href='gestion_matieres_serie.php?idserie=<?php echo $idserie; ?>&code_filiere=' + this.value" 
                        class="form-select" style="max-width: 400px;">
                    <option value="">Choisir une filière</option>
                    <?php foreach ($filieres as $filiere): ?>
                        <option value="<?php echo $filiere['code_filiere']; ?>" 
                                <?php echo ($code_filiere == $filiere['code_filiere']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($filiere['nom_filiere']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if ($code_filiere): ?>
                <form method="post">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll">
                                        </div>
                                    </th>
                                    <th>Matière</th>
                                    <th style="width: 150px;">Coefficient</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matieres as $matiere): ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input matiere-checkbox" type="checkbox" 
                                                   name="matieres[]" 
                                                   value="<?php echo $matiere['id_matiere']; ?>"
                                                   <?php echo isset($matiere['coefficient']) ? 'checked' : ''; ?>>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($matiere['nom_matiere']); ?></td>
                                    <td>
                                        <input type="number" 
                                               class="form-control coefficient-input" 
                                               name="coefficient[<?php echo $matiere['id_matiere']; ?>]" 
                                               value="<?php echo $matiere['coefficient'] ?? '1'; ?>"
                                               min="0.5" 
                                               step="0.5"
                                               <?php echo !isset($matiere['coefficient']) ? 'disabled' : ''; ?>>
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
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Veuillez sélectionner une filière pour gérer les matières
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });

    // Gestion du "Tout cocher/décocher"
    const checkAll = document.getElementById('checkAll');
    const matiereCheckboxes = document.querySelectorAll('.matiere-checkbox');
    const coefficientInputs = document.querySelectorAll('.coefficient-input');

    // Initialiser l'état de la case "Tout cocher"
    const updateCheckAllState = () => {
        const allChecked = Array.from(matiereCheckboxes).every(cb => cb.checked);
        const someChecked = Array.from(matiereCheckboxes).some(cb => cb.checked);
        checkAll.checked = allChecked;
        checkAll.indeterminate = someChecked && !allChecked;
    };

    // Initialiser l'état au chargement
    updateCheckAllState();

    checkAll.addEventListener('change', function() {
        matiereCheckboxes.forEach((checkbox, index) => {
            checkbox.checked = this.checked;
            coefficientInputs[index].disabled = !this.checked;
            if (this.checked && coefficientInputs[index].value === '') {
                coefficientInputs[index].value = '1';
            }
        });
    });

    // Gestion des cases à cocher individuelles
    matiereCheckboxes.forEach((checkbox, index) => {
        checkbox.addEventListener('change', function() {
            coefficientInputs[index].disabled = !this.checked;
            if (this.checked && coefficientInputs[index].value === '') {
                coefficientInputs[index].value = '1';
            }
            updateCheckAllState();
        });
    });

    // Validation du formulaire
    document.querySelector('form').addEventListener('submit', function(e) {
        const checkedBoxes = document.querySelectorAll('.matiere-checkbox:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Veuillez sélectionner au moins une matière.');
        }
    });
});
</script>

<?php
require_once('includes/footer.php');
?> 