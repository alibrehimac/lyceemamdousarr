<?php
require_once('includes/header.php');
require_once 'config.php';
$page_title = "Gestion des Matières par Filière";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$code_filiere = isset($_GET['code_filiere']) ? $_GET['code_filiere'] : null;
$idserie = isset($_GET['idserie']) ? intval($_GET['idserie']) : 0;

if (!$code_filiere || !$idserie) {
    $_SESSION['error'] = "Paramètres manquants";
    header('Location: gestion_series.php');
    exit();
}

try {
    // Récupérer les informations de la série et de la filière avec toutes les classes
    $stmt = $conn->prepare("SELECT s.series, f.nom_filiere, f.code_filiere, 
                                  GROUP_CONCAT(DISTINCT c.indice ORDER BY c.indice) as indices
                           FROM series s 
                           INNER JOIN filiere f ON s.idserie = f.idserie
                           LEFT JOIN classe c ON f.code_filiere = c.code_filiere
                           WHERE s.idserie = ? AND f.code_filiere = ?
                           GROUP BY s.idserie, f.code_filiere");
    $stmt->execute([$idserie, $code_filiere]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les indices existants pour cette filière
    $stmt = $conn->prepare("SELECT indice, nom_classe 
                           FROM classe 
                           WHERE code_filiere = ? 
                           ORDER BY indice");
    $stmt->execute([$code_filiere]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les matières avec leurs coefficients
    $stmt = $conn->prepare("SELECT m.*, mf.coefficient 
                           FROM matieres m
                           LEFT JOIN matiere_filiere mf ON m.id_matiere = mf.id_matiere 
                               AND mf.code_filiere = ?
                           ORDER BY m.nom_matiere");
    $stmt->execute([$code_filiere]);
    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header('Location: gestion_matieres_serie.php?idserie=' . $idserie);
    exit();
}
?>

<!-- En-tête de la page avec fond et ombre -->
<div class="bg-white shadow-sm rounded p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">
            <i class="fas fa-book text-primary me-2"></i>
            <?php echo $page_title; ?>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="gestion_series.php">Gestion des séries</a></li>
                <li class="breadcrumb-item">
                    <a href="gestion_matieres_serie.php?idserie=<?php echo $idserie; ?>">
                        Gestion des matières par série
                    </a>
                </li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($info['nom_filiere']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <?php echo htmlspecialchars($info['series']); ?> - 
                <?php echo htmlspecialchars($info['nom_filiere']); ?>
                <small class="text-muted ms-2">
                    (<?php echo htmlspecialchars($info['indices']); ?>)
                </small>
            </h4>
        </div>
        <div class="card-body">
            <form method="POST" action="traitement_matieres_filiere.php">
                <input type="hidden" name="code_filiere" value="<?php echo $code_filiere; ?>">
                <input type="hidden" name="idserie" value="<?php echo $idserie; ?>">
                
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Matière</th>
                            <th>Coefficient</th>
                            <th class="text-center">Inclure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matieres as $matiere): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($matiere['nom_matiere']); ?></td>
                            <td style="width: 150px;">
                                <input type="number" 
                                       name="coef[<?php echo $matiere['id_matiere']; ?>]" 
                                       value="<?php echo $matiere['coefficient'] ?: ''; ?>" 
                                       class="form-control" 
                                       min="0" 
                                       max="10">
                            </td>
                            <td class="text-center">
                                <div class="form-check d-flex justify-content-center">
                                    <input type="checkbox" 
                                           name="matieres[]" 
                                           value="<?php echo $matiere['id_matiere']; ?>"
                                           <?php echo $matiere['coefficient'] ? 'checked' : ''; ?>
                                           class="form-check-input">
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 