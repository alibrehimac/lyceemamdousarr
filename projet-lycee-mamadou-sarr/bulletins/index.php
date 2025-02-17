<?php
require_once 'config.php';
$page_title = "Accueil - Lycée Mamadou Sarr";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fonts/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/images/school-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        .logo-img {
            height: 60px;
            width: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <!-- Ajouter juste après l'ouverture du body -->
    <a href="rapport.html" class="view-report">
        <i class="fas fa-file-alt"></i>
        <span>Voir le Rapport</span>
    </a>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/img/logo.jpg" alt="Lycée Mamadou Sarr" class="logo-img">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">À propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Connexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Bienvenue au Lycée Mamadou Sarr</h1>
            <p class="lead mb-5">Une éducation de qualité pour un avenir brillant</p>
            <a href="login.php" class="btn btn-primary btn-lg">Espace Administration</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Nos Services</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-primary text-white">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h5 class="card-title">Gestion des Élèves</h5>
                            <p class="card-text">Suivi complet du parcours scolaire de chaque élève.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-success text-white">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h5 class="card-title">Suivi des Notes</h5>
                            <p class="card-text">Gestion efficace des évaluations et des bulletins.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-info text-white">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5 class="card-title">Gestion des Classes</h5>
                            <p class="card-text">Organisation optimale des classes et des emplois du temps.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section À propos -->
    <section class="py-5" id="about">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mb-4">À Propos</h2>
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="mb-4">
                                <img src="assets/img/dev.jpg" alt="Ali Brehima CISSE" 
                                     class="rounded-circle img-thumbnail" 
                                     style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                            <h4 class="text-primary mb-3">Ali Brehima CISSE</h4>
                            <p class="lead text-muted mb-4">
                                Cette application est le fruit d'un engagement personnel envers le développement numérique 
                                de notre système éducatif. En tant que jeune développeur passionné, j'ai voulu apporter 
                                ma contribution à la modernisation de la gestion scolaire au Mali.
                            </p>
                            <p class="mb-4">
                                Ce projet reflète non seulement mes compétences techniques, mais surtout ma vision 
                                d'un avenir où la technologie facilite le quotidien de nos établissements scolaires. 
                                Cette initiative individuelle témoigne de mon engagement envers l'innovation et 
                                le développement numérique de notre pays.
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="tel:+22396939396" class="btn btn-outline-primary">
                                    <i class="fas fa-phone me-2"></i>
                                    +223 96 93 93 96
                                </a>
                                <a href="mailto:contact@example.com" class="btn btn-outline-primary">
                                    <i class="fas fa-envelope me-2"></i>
                                    Me contacter
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Lycée Mamadou Sarr</h5>
                    <p>Une éducation de qualité pour tous</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Contact</h5>
                    <p>Email: contact@lyceemamadousarr.edu<br>Tél: +221 xx xx xx xx</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Lycée Mamadou Sarr. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 