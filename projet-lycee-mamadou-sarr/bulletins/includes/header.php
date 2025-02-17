<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Fonction pour afficher les messages d'erreur
function displayErrors($errors) {
    if (empty($errors)) return;
    
    echo '<div class="alert alert-danger alert-dismissible fade show">';
    if (is_array($errors)) {
        echo '<ul class="mb-0">';
        foreach ($errors as $error) {
            if (is_array($error)) {
                foreach ($error as $err) {
                    echo '<li>' . htmlspecialchars($err) . '</li>';
                }
            } else {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
        }
        echo '</ul>';
    } else {
        echo htmlspecialchars($errors);
    }
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// Fonction pour afficher les messages de succès
function displaySuccess($message) {
    if (empty($message)) return;
    
    echo '<div class="alert alert-success alert-dismissible fade show">';
    if (is_array($message)) {
        echo '<ul class="mb-0">';
        foreach ($message as $msg) {
            echo '<li>' . htmlspecialchars($msg) . '</li>';
        }
        echo '</ul>';
    } else {
        echo htmlspecialchars($message);
    }
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Gestion Scolaire'; ?></title>
    <!-- CSS local -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Le CSS Font Awesome local -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">

    <link href="assets/fonts/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- JavaScript local -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Gestion Scolaire</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="gestion_promotions.php">Années scolaires</a></li>
                            <li><a class="dropdown-item" href="gestion_series.php">Séries</a></li>
                            <li><a class="dropdown-item" href="gestion_filieres.php">Filières</a></li>
                            <li><a class="dropdown-item" href="gestion_classes.php">Classes</a></li>
                            <li><a class="dropdown-item" href="gestion_matieres.php">Matières</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown2" role="button" 
                           data-bs-toggle="dropdown">
                            Élèves
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="gestion_eleves.php">Gestion des élèves</a></li>
                            <li><a class="dropdown-item" href="import_eleves.php">Import CSV</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gestion_notes.php">Notes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gestion_bulletins.php">Bulletins</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="gestion_utilisateurs.php">Gestion utilisateurs</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Conteneur principal -->
    <div class="container mt-4">
        <?php 
        // Afficher les messages de succès
        if (isset($_SESSION['message'])) {
            displaySuccess($_SESSION['message']);
            unset($_SESSION['message']);
        }

        // Afficher les messages d'erreur
        if (isset($_SESSION['error'])) {
            displayErrors($_SESSION['error']);
            unset($_SESSION['error']);
        }
        ?>
    </div>
</body>
</html> 