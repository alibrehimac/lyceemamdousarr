<?php
require_once('includes/header.php');
require_once 'config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Récupérer la promotion active et les autres promotions
try {
    $sql = "SELECT * FROM promotion ORDER BY annee_scolaire DESC";
    $stmt = $conn->query($sql);
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la promotion active (la plus récente)
    $promotion_active = reset($promotions);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des promotions";
}

// Récupérer les classes si une promotion est sélectionnée
$classes = [];
if (isset($_GET['idpromotion'])) {
    try {
        $stmt = $conn->prepare("SELECT c.*, f.nom_filiere 
                               FROM classe c 
                               JOIN filiere f ON c.code_filiere = f.code_filiere 
                               WHERE c.idpromotion = ? 
                               ORDER BY f.nom_filiere, c.nom_classe");
        $stmt->execute([$_GET['idpromotion']]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la récupération des classes";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sélection de la classe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Sélection de la classe</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="gestion_bulletins.php" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Année scolaire</label>
                        <select name="idpromotion" class="form-select" required 
                                onchange="this.form.action='select_classe.php'; this.form.submit();">
                            <option value="">Sélectionner une année scolaire</option>
                            <?php foreach ($promotions as $promotion): ?>
                                <option value="<?php echo $promotion['idpromotion']; ?>"
                                        <?php echo (isset($_GET['idpromotion']) && 
                                                  $_GET['idpromotion'] == $promotion['idpromotion']) ? 'selected' : ''; ?>>
                                    <?php echo $promotion['annee_scolaire']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (isset($_GET['idpromotion']) && !empty($classes)): ?>
                        <div class="col-md-6">
                            <label class="form-label">Classe</label>
                            <select name="idclasse" class="form-select" required>
                                <option value="">Sélectionner une classe</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo $classe['idClasse']; ?>">
                                        <?php echo $classe['nom_classe'] . ' ' . $classe['nom_filiere']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-2"></i>Valider
                            </button>
                        </div>
                    <?php elseif (isset($_GET['idpromotion']) && empty($classes)): ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Aucune classe n'est disponible pour cette année scolaire.
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Lien de retour -->
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 