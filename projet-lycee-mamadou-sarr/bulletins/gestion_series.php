<?php
require_once 'config.php';
require_once('includes/header.php');
$page_title = "Gestion des Séries";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Traitement de la modification d'une série
if (isset($_POST['modifier'])) {
    $idserie = htmlspecialchars($_POST['idserie']);
    $nom_serie = htmlspecialchars($_POST['nom_serie']);
    
    try {
        // Vérifier si le nouveau nom existe déjà pour une autre série
        $check = $conn->prepare("SELECT COUNT(*) FROM series WHERE series = ? AND idserie != ?");
        $check->execute([$nom_serie, $idserie]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Une série avec ce nom existe déjà";
        } else {
            $sql = "UPDATE series SET series = ? WHERE idserie = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nom_serie, $idserie]);
            $_SESSION['message'] = "Série modifiée avec succès";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification de la série";
    }
    
    header('Location: gestion_series.php');
    exit();
}

// Traitement de la suppression d'une série
if (isset($_POST['supprimer'])) {
    $idserie = htmlspecialchars($_POST['idserie']);
    
    try {
        // Vérifier si la série a des filières associées
        $check = $conn->prepare("SELECT COUNT(*) FROM filiere WHERE idserie = ?");
        $check->execute([$idserie]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Impossible de supprimer cette série car elle contient des filières";
        } else {
            $sql = "DELETE FROM series WHERE idserie = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idserie]);
            $_SESSION['message'] = "Série supprimée avec succès";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression de la série";
    }
    
    header('Location: gestion_series.php');
    exit();
}

// Traitement de l'ajout d'une série
if (isset($_POST['ajouter'])) {
    $nom_serie = htmlspecialchars($_POST['nom_serie']);
    
    try {
        // Vérifier si la série existe déjà
        $check = $conn->prepare("SELECT COUNT(*) FROM series WHERE series = ?");
        $check->execute([$nom_serie]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Cette série existe déjà";
        } else {
            $sql = "INSERT INTO series (series) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nom_serie]);
            $_SESSION['message'] = "Série ajoutée avec succès";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de la série";
    }
    
    header('Location: gestion_series.php');
    exit();
}

// Récupération des séries
try {
    $sql = "SELECT s.*, COUNT(f.code_filiere) as nb_filieres 
            FROM series s 
            LEFT JOIN filiere f ON s.idserie = f.idserie 
            GROUP BY s.idserie 
            ORDER BY s.series";
    $stmt = $conn->query($sql);
    $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des séries";
    $series = [];
}
?>

<!-- En-tête de la page avec fond et ombre -->
<div class="bg-white shadow-sm rounded p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">
            <i class="fas fa-graduation-cap text-primary me-2"></i>
            <?php echo $page_title; ?>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
            </ol>
        </nav>
    </div>
</div>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des séries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Gestion des séries</h1>
        
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
            <div class="card-body">
                <h5 class="card-title">Ajouter une série</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="nom_serie" class="form-label">Nom de la série</label>
                        <input type="text" class="form-control" id="nom_serie" name="nom_serie" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="ajouter" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des séries -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Liste des séries</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom de la série</th>
                            <th>Nombre de filières</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($series as $serie): ?>
                        <tr data-serie-id="<?php echo $serie['idserie']; ?>">
                            <td><?php echo $serie['idserie']; ?></td>
                            <td><?php echo $serie['series']; ?></td>
                            <td><?php echo $serie['nb_filieres']; ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal<?php echo $serie['idserie']; ?>">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                
                                <button type="button" class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal<?php echo $serie['idserie']; ?>">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                                
                                <a href="gestion_matieres_serie.php?idserie=<?php echo $serie['idserie']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-book"></i> Gérer les matières
                                </a>
                            </td>
                        </tr>
                        
                        <!-- Modal Modification -->
                        <div class="modal fade" id="editModal<?php echo $serie['idserie']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier la série</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form class="form-edit-serie" onsubmit="return modifierSerie(event, <?php echo $serie['idserie']; ?>)">
                                        <div class="modal-body">
                                            <input type="hidden" name="idserie" value="<?php echo $serie['idserie']; ?>">
                                            <div class="mb-3">
                                                <label for="nom_serie_edit_<?php echo $serie['idserie']; ?>" class="form-label">Nom de la série</label>
                                                <input type="text" class="form-control" id="nom_serie_edit_<?php echo $serie['idserie']; ?>" 
                                                       name="nom_serie" value="<?php echo $serie['series']; ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Suppression -->
                        <div class="modal fade" id="deleteModal<?php echo $serie['idserie']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirmer la suppression</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Êtes-vous sûr de vouloir supprimer la série "<?php echo $serie['series']; ?>" ?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="button" class="btn btn-danger" onclick="supprimerSerie(<?php echo $serie['idserie']; ?>)">Supprimer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function modifierSerie(event, idserie) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'modifier');

        fetch('ajax_series.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le nom dans le tableau
                const nomSerieCell = document.querySelector(`tr[data-serie-id="${idserie}"] td:nth-child(2)`);
                nomSerieCell.textContent = formData.get('nom_serie');
                
                // Fermer le modal
                bootstrap.Modal.getInstance(document.getElementById(`editModal${idserie}`)).hide();
                
                // Afficher le message de succès
                alert(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
        
        return false;
    }

    function supprimerSerie(idserie) {
        const formData = new FormData();
        formData.append('idserie', idserie);
        formData.append('action', 'supprimer');

        fetch('ajax_series.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Supprimer la ligne du tableau
                document.querySelector(`tr[data-serie-id="${idserie}"]`).remove();
                
                // Fermer le modal
                bootstrap.Modal.getInstance(document.getElementById(`deleteModal${idserie}`)).hide();
                
                // Afficher le message de succès
                alert(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
