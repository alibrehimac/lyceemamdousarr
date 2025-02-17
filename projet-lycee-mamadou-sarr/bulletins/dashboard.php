<?php
require_once('includes/header.php');
require_once 'config.php';
$page_title = "Tableau de bord"; // Définir le titre de la page

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Récupère les informations de l'utilisateur connecté
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Récupérer les statistiques
try {
    // Nombre total de classes
    $stmt = $conn->query("SELECT COUNT(*) as total_classes FROM classe");
    $total_classes = $stmt->fetch(PDO::FETCH_ASSOC)['total_classes'];

    // Nombre d'élèves par filière
    $sql_filiere = "SELECT f.nom_filiere, COUNT(DISTINCT i.matricule) as nb_eleves
                    FROM filiere f
                    LEFT JOIN inscrire i ON f.code_filiere = i.code_filiere
                    GROUP BY f.code_filiere, f.nom_filiere
                    ORDER BY f.nom_filiere";
    $stmt_filiere = $conn->query($sql_filiere);
    $eleves_par_filiere = $stmt_filiere->fetchAll(PDO::FETCH_ASSOC);

    // Nombre d'élèves par classe avec indice
    $sql_classe = "SELECT 
                    c.nom_classe,
                    f.nom_filiere,
                    COUNT(DISTINCT i.matricule) as nb_eleves,
                    c.idClasse
                   FROM classe c
                   LEFT JOIN filiere f ON c.code_filiere = f.code_filiere
                   LEFT JOIN inscrire i ON c.idClasse = i.idClasse
                   GROUP BY c.idClasse, c.nom_classe, f.nom_filiere
                   ORDER BY c.nom_classe, f.nom_filiere";
    $stmt_classe = $conn->query($sql_classe);
    $eleves_par_classe = $stmt_classe->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter l'indice manuellement
    $classes_indices = [];
    foreach ($eleves_par_classe as $classe) {
        $key = $classe['nom_classe'] . ' ' . $classe['nom_filiere'];
        if (!isset($classes_indices[$key])) {
            $classes_indices[$key] = 1;
        } else {
            $classes_indices[$key]++;
        }
        $classe['classe_complete'] = $classe['nom_classe'] . ' ' . $classe['nom_filiere'] . ' ' . $classes_indices[$key];
        $classes_formatees[] = $classe;
    }

    // Nombre total d'élèves
    $stmt = $conn->query("SELECT COUNT(*) as total_eleves FROM eleves");
    $total_eleves = $stmt->fetch(PDO::FETCH_ASSOC)['total_eleves'];

    // Nombre d'élèves sans matricule
    $stmt = $conn->query("SELECT COUNT(*) as total_sans_matricule FROM eleves_sans_matricule");
    $total_sans_matricule = $stmt->fetch(PDO::FETCH_ASSOC)['total_sans_matricule'];

} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des statistiques";
}

// Initialize $menu_items as an empty array if not already set
$menu_items = isset($menu_items) ? $menu_items : [];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Gestion Scolaire</title>
    
    <!-- Bootstrap CSS local -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome local -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    
    <style>
        .card {
            transition: transform 0.2s;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .bg-gradient-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.05);
        }
        .nav-link {
            color: #1e40af;
        }
        .nav-link:hover {
            color: #2563eb;
        }
        .sidebar {
            min-height: 100vh;
            border-right: 1px solid rgba(0,0,0,.1);
        }
        
        .nav-link {
            padding: .5rem 1rem;
            color: #333;
            border-radius: 5px;
            margin: 2px 10px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background-color: rgba(37, 99, 235, 0.1);
            color: #2563eb;
        }
        
        .nav-link.active {
            background-color: #2563eb;
            color: white;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .nav-divider hr {
            margin: 1rem 10px;
            opacity: 0.1;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                z-index: 1000;
                background: white;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                display: none;
            }
            
            .sidebar.show {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-white shadow-sm sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="assets/img/logo.jpg" alt="Logo" class="img-fluid" style="max-width: 120px;">
                        <h5 class="mt-3">Gestion Scolaire</h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_eleves.php">
                                <i class="fas fa-users me-2"></i>
                                Gestion des élèves
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_notes.php">
                                <i class="fas fa-star me-2"></i>
                                Gestion des notes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_classes.php">
                                <i class="fas fa-school me-2"></i>
                                Gestion des classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_trimestres.php">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Gestion des trimestres
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cartes_scolaires.php">
                                <i class="fas fa-id-card me-2"></i>
                                Cartes scolaires
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="imprimer_bulletins_classe.php">
                                <i class="fas fa-print me-2"></i>
                                Bulletins de notes
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-divider">
                            <hr class="my-3">
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_matieres.php">
                                <i class="fas fa-book me-2"></i>
                                Gestion des matières
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_filieres.php">
                                <i class="fas fa-graduation-cap me-2"></i>
                                Gestion des filières
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_utilisateurs.php">
                                <i class="fas fa-user-cog me-2"></i>
                                Gestion des utilisateurs
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-divider">
                            <hr class="my-3">
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tableau de bord</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i> Exporter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-school fa-fw"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0 text-muted">Classes</h6>
                                        <h3 class="mb-0"><?php echo $total_classes; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-users fa-fw"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0 text-muted">Élèves</h6>
                                        <h3 class="mb-0"><?php echo $total_eleves; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-exclamation-triangle fa-fw"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0 text-muted">Sans matricule</h6>
                                        <h3 class="mb-0"><?php echo $total_sans_matricule; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Section -->
                <div class="row g-4 mb-4">
                    <!-- Élèves par filière -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-graduation-cap me-2 text-primary"></i>
                                    Élèves par filière
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Filière</th>
                                                <th class="text-end">Effectif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($eleves_par_filiere as $filiere): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($filiere['nom_filiere']); ?></td>
                                                <td class="text-end">
                                                    <span class="badge bg-success rounded-pill">
                                                        <?php echo $filiere['nb_eleves']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Élèves par classe -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2 text-success"></i>
                                    Élèves par classe
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Classe</th>
                                                <th>Filière</th>
                                                <th class="text-end">Effectif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($classes_formatees as $classe): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($classe['classe_complete']); ?></td>
                                                <td><?php echo htmlspecialchars($classe['nom_filiere']); ?></td>
                                                <td class="text-end">
                                                    <span class="badge bg-success rounded-pill">
                                                        <?php echo $classe['nb_eleves']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>