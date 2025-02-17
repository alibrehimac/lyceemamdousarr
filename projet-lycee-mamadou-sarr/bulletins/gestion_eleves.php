<?php
session_start(); // S'assurer que la session est démarrée
require_once('includes/header.php');
require_once 'config.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// -------------- GESTION DE L'AJOUT D'UN ÉLÈVE --------------
if (isset($_POST['ajouter'])) {
    $matricule = htmlspecialchars($_POST['matricule']);
    
    // Vérifier si le matricule existe déjà
    $check = $conn->prepare("SELECT COUNT(*) FROM eleves WHERE matricule = ?");
    $check->execute([$matricule]);
    if ($check->fetchColumn() > 0) {
        $_SESSION['error'] = "Ce matricule existe déjà";
        header('Location: gestion_eleves.php');
        exit();
    }
    
    $nom        = htmlspecialchars($_POST['nom']);
    $prenom     = htmlspecialchars($_POST['prenom']);
    $date_naiss = $_POST['date_naiss'];
    $lieu_naiss = htmlspecialchars($_POST['lieu_naiss']);
    $sexe       = $_POST['sexe'];
    $adresse    = htmlspecialchars($_POST['adresse']);
    $tel        = htmlspecialchars($_POST['tel']);
    $pere       = htmlspecialchars($_POST['pere']);
    $mere       = htmlspecialchars($_POST['mere']);

    try {
        $sql = "INSERT INTO eleves (matricule, nom, prenom, date_naiss, lieu_naiss, sexe, adresse, tel, pere, mere) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $matricule, $nom, $prenom, $date_naiss, 
            $lieu_naiss, $sexe, $adresse, $tel, $pere, $mere
        ]);
        
        $_SESSION['message'] = "Élève ajouté avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'élève : " . $e->getMessage();
    }
    
    header('Location: gestion_eleves.php');
    exit();
}

// -------------- GESTION DE LA MODIFICATION D'UN ÉLÈVE --------------
if (isset($_POST['modifier'])) {
    $matricule  = $_POST['matricule'];
    $nom        = htmlspecialchars($_POST['nom']);
    $prenom     = htmlspecialchars($_POST['prenom']);
    $date_naiss = $_POST['date_naiss'];
    $lieu_naiss = htmlspecialchars($_POST['lieu_naiss']);
    $sexe       = $_POST['sexe'];
    $adresse    = htmlspecialchars($_POST['adresse']);
    $tel        = htmlspecialchars($_POST['tel']);
    $pere       = htmlspecialchars($_POST['pere']);
    $mere       = htmlspecialchars($_POST['mere']);

    try {
        $sql = "UPDATE eleves 
                SET nom=?, prenom=?, date_naiss=?, lieu_naiss=?, sexe=?, adresse=?, tel=?, pere=?, mere=? 
                WHERE matricule=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $nom, $prenom, $date_naiss, $lieu_naiss, 
            $sexe, $adresse, $tel, $pere, $mere, $matricule
        ]);
        $_SESSION['message'] = "Élève modifié avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification de l'élève : " . $e->getMessage();
    }

    header('Location: gestion_eleves.php');
    exit();
}

// -------------- GESTION DE LA SUPPRESSION D'UN ÉLÈVE --------------
if (isset($_POST['supprimer'])) {
    $matricule = $_POST['matricule'];
    
    try {
        $sql = "DELETE FROM eleves WHERE matricule=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$matricule]);
        $_SESSION['message'] = "Élève supprimé avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression de l'élève : " . $e->getMessage();
    }
    
    header('Location: gestion_eleves.php');
    exit();
}

// -------------- RÉCUPÉRATION DES CLASSES DISPONIBLES --------------
try {
    $sql_classes = "SELECT c.idClasse, c.nom_classe, f.nom_filiere, p.annee_scolaire 
                    FROM classe c
                    JOIN filiere f ON c.code_filiere = f.code_filiere
                    JOIN promotion p ON c.idpromotion = p.idpromotion
                    ORDER BY p.annee_scolaire DESC, c.nom_classe";
    $stmt_classes = $conn->query($sql_classes);
    $classes_disponibles = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des classes : " . $e->getMessage();
    $classes_disponibles = [];
}

// -------------- RÉCUPÉRATION DE LA RECHERCHE ET DU FILTRE CLASSE --------------
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_classe = isset($_GET['filter_classe']) ? (int) $_GET['filter_classe'] : 0;

// Construction de la requête SQL pour récupérer les élèves
$sql = "SELECT DISTINCT e.*, c.nom_classe, f.nom_filiere, p.annee_scolaire
        FROM eleves e
        LEFT JOIN inscrire i ON e.matricule = i.matricule
        LEFT JOIN classe c   ON i.idClasse = c.idClasse
        LEFT JOIN filiere f  ON c.code_filiere = f.code_filiere
        LEFT JOIN promotion p ON c.idpromotion = p.idpromotion";

$whereClauses = [];
$params       = [];

// Filtre de recherche (par nom, prénom ou matricule)
if ($search) {
    $whereClauses[] = "(e.matricule LIKE ? OR e.nom LIKE ? OR e.prenom LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Filtre par classe
if ($filter_classe > 0) {
    $whereClauses[] = "i.idClasse = ?";
    $params[]       = $filter_classe;
}

// Construire la clause WHERE si nécessaire
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY e.nom, e.prenom";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des élèves</title>
    <!-- Bootstrap CSS local -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Font Awesome local -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    
    <style>
        /* Styles généraux */
        body {
            background-color: #f8f9fa;
        }
        
        /* Style des cartes et conteneurs */
        .card, .table-container, .search-container, .filter-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        /* Style du tableau */
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .table td {
            vertical-align: middle;
        }
        .table tr:hover {
            background-color: #f8f9fa;
        }

        /* Style des boutons */
        .btn {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
        }
        .btn-action {
            margin: 0 0.2rem;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        /* Style des formulaires */
        .form-control {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }

        /* Style des modals */
        .modal-content {
            border-radius: 8px;
            border: none;
        }
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .modal-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        /* Ajustements de l'espacement */
        .container-fluid {
            padding: 2rem;
        }
        .table-container {
            padding: 1rem;
        }
        .search-container {
            padding: 1rem;
        }
        .filter-container {
            padding: 1rem;
        }

        /* Style des badges et alertes */
        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
            .btn-action {
                margin: 0.2rem 0;
            }
            .table-responsive {
                border-radius: 8px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Colonne de filtrage -->
            <div class="col-md-3 mb-4">
                <div class="filter-container">
                    <h5 class="mb-3">Filtrer par classe</h5>
                    <form method="GET" action="gestion_eleves.php">
                        <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        
                        <select name="filter_classe" class="form-select" onchange="this.form.submit()">
                            <option value="0">Toutes les classes</option>
                            <?php foreach ($classes_disponibles as $c): ?>
                                <option value="<?php echo $c['idClasse']; ?>"
                                    <?php if ($filter_classe == $c['idClasse']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($c['nom_classe'].' - '.$c['nom_filiere'].' ('.$c['annee_scolaire'].')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            
            <!-- Contenu principal -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Gestion des élèves</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#ajoutModal">
                            <i class="fas fa-plus me-2"></i>Ajouter un élève
                        </button>
                        <a href="export_pdf.php" class="btn btn-success btn-action">
                            <i class="fas fa-file-pdf me-2"></i>Exporter PDF
                        </a>
                        <a href="valider_inscription.php" class="btn btn-primary btn-action">
                            <i class="fas fa-user-plus me-2"></i>Valider inscriptions
                        </a>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Barre de recherche -->
                <div class="search-container mb-4">
                    <form method="GET" class="d-flex gap-2">
                        <?php if ($filter_classe > 0): ?>
                            <input type="hidden" name="filter_classe" value="<?php echo $filter_classe; ?>">
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Rechercher par nom, prénom ou matricule..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Rechercher
                            </button>
                            <?php if ($search): ?>
                                <a href="gestion_eleves.php<?php echo ($filter_classe > 0 ? '?filter_classe='.$filter_classe : ''); ?>" 
                                   class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Réinitialiser
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Tableau des élèves -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Date de naissance</th>
                                    <th>Classe</th>
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
                                        <td><?php echo date('d/m/Y', strtotime($eleve['date_naiss'])); ?></td>
                                        <td>
                                            <?php 
                                            echo $eleve['nom_classe'] ? 
                                                htmlspecialchars($eleve['nom_classe'] . ' - ' . $eleve['nom_filiere']) : 
                                                '<span class="badge bg-warning text-dark">Non inscrit</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info btn-action" data-bs-toggle="modal" 
                                                        data-bs-target="#modifierModal<?php echo $eleve['matricule']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="transfert.php?matricule=<?php echo urlencode($eleve['matricule']); ?>" 
                                                   class="btn btn-sm btn-warning btn-action">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" 
                                                        data-bs-target="#supprimerModal<?php echo $eleve['matricule']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Aucun élève trouvé.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout -->
    <div class="modal fade" id="ajoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un élève</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Matricule</label>
                            <input type="text" class="form-control" name="matricule" required 
                                   pattern="[A-Za-z0-9]+" title="Le matricule ne doit contenir que des lettres et des chiffres">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" name="date_naiss" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lieu de naissance</label>
                            <input type="text" class="form-control" name="lieu_naiss">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sexe</label>
                            <select class="form-control" name="sexe" required>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="adresse">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text" class="form-control" name="tel">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom du père</label>
                            <input type="text" class="form-control" name="pere">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom de la mère</label>
                            <input type="text" class="form-control" name="mere">
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
    
    <!-- Modals Modification et Suppression pour chaque élève -->
    <?php foreach ($eleves as $eleve): ?>
        <!-- Modal Modification -->
        <div class="modal fade" id="modifierModal<?php echo $eleve['matricule']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier l'élève</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="matricule" value="<?php echo htmlspecialchars($eleve['matricule']); ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="nom" 
                                       value="<?php echo htmlspecialchars($eleve['nom']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="prenom" 
                                       value="<?php echo htmlspecialchars($eleve['prenom']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" name="date_naiss" 
                                       value="<?php echo htmlspecialchars($eleve['date_naiss']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lieu de naissance</label>
                                <input type="text" class="form-control" name="lieu_naiss" 
                                       value="<?php echo htmlspecialchars($eleve['lieu_naiss']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sexe</label>
                                <select class="form-control" name="sexe" required>
                                    <option value="M" <?php if($eleve['sexe'] == 'M') echo 'selected'; ?>>Masculin</option>
                                    <option value="F" <?php if($eleve['sexe'] == 'F') echo 'selected'; ?>>Féminin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Adresse</label>
                                <input type="text" class="form-control" name="adresse" 
                                       value="<?php echo htmlspecialchars($eleve['adresse']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="tel" 
                                       value="<?php echo htmlspecialchars($eleve['tel']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom du père</label>
                                <input type="text" class="form-control" name="pere" 
                                       value="<?php echo htmlspecialchars($eleve['pere']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom de la mère</label>
                                <input type="text" class="form-control" name="mere" 
                                       value="<?php echo htmlspecialchars($eleve['mere']); ?>">
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
        <div class="modal fade" id="supprimerModal<?php echo $eleve['matricule']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Êtes-vous sûr de vouloir supprimer l'élève 
                        <strong><?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></strong> ?
                    </div>
                    <div class="modal-footer">
                        <form method="POST">
                            <input type="hidden" name="matricule" value="<?php echo htmlspecialchars($eleve['matricule']); ?>">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" name="supprimer" class="btn btn-danger">Supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
