<?php
require_once 'config.php';
$page_title = "Titre de la Page"; // Personnaliser pour chaque page
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Code spécifique à la page...
?>

<!-- En-tête de la page avec fond et ombre -->
<div class="bg-white shadow-sm rounded p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">
            <i class="fas fa-[icone] text-primary me-2"></i>
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

<!-- Contenu de la page -->
<div class="container-fluid">
    <!-- Code HTML spécifique à la page... -->
</div>

<?php require_once 'includes/footer.php'; ?> 