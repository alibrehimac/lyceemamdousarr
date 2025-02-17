<?php
require_once('includes/header.php');
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les filtres
$filiere_id = isset($_GET['filiere']) ? $_GET['filiere'] : null;
$classe_id = isset($_GET['classe']) ? $_GET['classe'] : null;

// Récupérer toutes les filières
try {
    $sql = "SELECT DISTINCT f.code_filiere, f.nom_filiere 
            FROM filiere f 
            JOIN classe c ON f.code_filiere = c.code_filiere 
            ORDER BY f.nom_filiere";
    $stmt = $conn->query($sql);
    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des filières";
}

// Récupérer les classes (toutes ou par filière)
try {
    $sql = "SELECT c.idClasse, c.nom_classe, f.nom_filiere, p.annee_scolaire 
            FROM classe c 
            JOIN filiere f ON c.code_filiere = f.code_filiere 
            JOIN promotion p ON c.idpromotion = p.idpromotion";
    if ($filiere_id) {
        $sql .= " WHERE f.code_filiere = :filiere_id";
    }
    $sql .= " ORDER BY p.annee_scolaire DESC, c.nom_classe";
    
    $stmt = $conn->prepare($sql);
    if ($filiere_id) {
        $stmt->bindParam(':filiere_id', $filiere_id);
    }
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des classes";
}

// Construire la requête pour les élèves selon les filtres
try {
    $sql = "SELECT DISTINCT e.*, c.nom_classe, f.nom_filiere, p.annee_scolaire
            FROM eleves e
            LEFT JOIN inscrire i ON e.matricule = i.matricule
            LEFT JOIN classe c ON i.idClasse = c.idClasse
            LEFT JOIN filiere f ON c.code_filiere = f.code_filiere
            LEFT JOIN promotion p ON c.idpromotion = p.idpromotion";
    
    $params = [];
    $where = [];
    
    if ($filiere_id) {
        $where[] = "f.code_filiere = :filiere_id";
        $params[':filiere_id'] = $filiere_id;
    }
    if ($classe_id) {
        $where[] = "c.idClasse = :classe_id";
        $params[':classe_id'] = $classe_id;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY e.nom, e.prenom";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des élèves";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Élèves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Liste des Élèves</h1>
            <a href="ajouter_eleve.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Ajouter un élève
            </a>
        </div>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="filiere" class="form-label">Filière</label>
                        <select class="form-select" id="filiere" name="filiere" onchange="this.form.submit()">
                            <option value="">Toutes les filières</option>
                            <?php foreach ($filieres as $filiere): ?>
                                <option value="<?php echo $filiere['code_filiere']; ?>"
                                        <?php echo $filiere_id == $filiere['code_filiere'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($filiere['nom_filiere']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="classe" class="form-label">Classe</label>
                        <select class="form-select" id="classe" name="classe" onchange="this.form.submit()">
                            <option value="">Toutes les classes</option>
                            <?php foreach ($classes as $classe): ?>
                                <option value="<?php echo $classe['idClasse']; ?>"
                                        <?php echo $classe_id == $classe['idClasse'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($classe['nom_classe'] . ' ' . 
                                                             $classe['nom_filiere'] . ' (' . 
                                                             $classe['annee_scolaire'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="eleve_liste.php" class="btn btn-secondary">
                            <i class="fas fa-sync-alt me-2"></i>Réinitialiser les filtres
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Après le formulaire de filtrage, avant le tableau -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Télécharger la liste</h5>
                <div class="btn-group">
                    <a href="generer_liste_pdf.php" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>
                        Tous les élèves
                    </a>
                    <?php if ($filiere_id): ?>
                        <a href="generer_liste_pdf.php?filiere=<?php echo $filiere_id; ?>" class="btn btn-success">
                            <i class="fas fa-download me-2"></i>
                            Cette filière
                        </a>
                    <?php endif; ?>
                    <?php if ($classe_id): ?>
                        <a href="generer_liste_pdf.php?classe=<?php echo $classe_id; ?>" class="btn btn-info">
                            <i class="fas fa-download me-2"></i>
                            Cette classe
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tableau des élèves -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Classe</th>
                                <th>Filière</th>
                                <th>Date de naissance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($eleves)): ?>
                                <?php foreach ($eleves as $eleve): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($eleve['matricule']); ?></td>
                                        <td><?php echo htmlspecialchars($eleve['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($eleve['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($eleve['nom_classe'] ?? 'Non assigné'); ?></td>
                                        <td><?php echo htmlspecialchars($eleve['nom_filiere'] ?? 'Non assigné'); ?></td>
                                        <td><?php echo $eleve['date_naiss'] ? date('d/m/Y', strtotime($eleve['date_naiss'])) : 'Non renseigné'; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="voir_eleve.php?matricule=<?php echo $eleve['matricule']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="modifier_eleve.php?matricule=<?php echo $eleve['matricule']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="supprimer_eleve.php?matricule=<?php echo $eleve['matricule']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet élève ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucun élève trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
