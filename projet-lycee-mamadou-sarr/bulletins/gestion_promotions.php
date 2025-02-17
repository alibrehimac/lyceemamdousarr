<?php
require_once('includes/header.php');
require_once 'config.php';
$page_title = "Gestion des Promotions";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Traitement de l'ajout d'une promotion
if (isset($_POST['ajouter'])) {
    $annee_scolaire = htmlspecialchars($_POST['annee_scolaire']);
    
    try {
        // Vérifier si l'année scolaire existe déjà
        $check = $conn->prepare("SELECT COUNT(*) FROM promotion WHERE annee_scolaire = ?");
        $check->execute([$annee_scolaire]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Cette année scolaire existe déjà";
        } else {
            $sql = "INSERT INTO promotion (annee_scolaire) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$annee_scolaire]);
            $_SESSION['message'] = "Promotion ajoutée avec succès";
        }
    } catch(PDOException $e) {
        // Vérifier si l'erreur est due à la colonne manquante
        if (strpos($e->getMessage(), "Unknown column 'annee_scolaire'") !== false) {
            try {
                // Tenter d'ajouter la colonne
                $conn->exec("ALTER TABLE promotion ADD COLUMN annee_scolaire VARCHAR(9) NOT NULL");
                // Réessayer l'insertion
                $sql = "INSERT INTO promotion (annee_scolaire) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$annee_scolaire]);
                $_SESSION['message'] = "Promotion ajoutée avec succès";
            } catch(PDOException $e2) {
                $_SESSION['error'] = "Erreur lors de la modification de la structure de la base de données";
            }
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout de la promotion";
        }
    }
    
    header('Location: gestion_promotions.php');
    exit();
}

// Récupération des promotions
try {
    $sql = "SELECT * FROM promotion ORDER BY annee_scolaire DESC";
    $stmt = $conn->query($sql);
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    if (strpos($e->getMessage(), "Unknown column 'annee_scolaire'") !== false) {
        try {
            // Ajouter la colonne si elle n'existe pas
            $conn->exec("ALTER TABLE promotion ADD COLUMN annee_scolaire VARCHAR(9) NOT NULL");
            $_SESSION['message'] = "La structure de la base de données a été mise à jour. Veuillez rafraîchir la page.";
            $promotions = [];
        } catch(PDOException $e2) {
            $_SESSION['error'] = "Erreur lors de la modification de la structure de la base de données";
            $promotions = [];
        }
    } else {
        $_SESSION['error'] = "Erreur lors de la récupération des promotions";
        $promotions = [];
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestion des Promotions</h1>
    
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

    <!-- Formulaire d'ajout -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus me-1"></i>
            Ajouter une promotion
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="annee_scolaire" class="form-label">Année scolaire</label>
                    <input type="text" class="form-control" id="annee_scolaire" name="annee_scolaire" 
                           placeholder="2023-2024" required pattern="\d{4}-\d{4}">
                    <div class="form-text">Format: AAAA-AAAA (ex: 2023-2024)</div>
                </div>
                <div class="col-12">
                    <button type="submit" name="ajouter" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des promotions -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Liste des promotions
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Année scolaire</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($promotions)): ?>
                        <?php foreach ($promotions as $promotion): ?>
                        <tr>
                            <td><?php echo $promotion['idpromotion']; ?></td>
                            <td><?php echo $promotion['annee_scolaire']; ?></td>
                            <td>
                                <a href="gestion_trimestres.php?idpromotion=<?php echo $promotion['idpromotion']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-calendar-alt"></i> Gérer les trimestres
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">Aucune promotion trouvée</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 