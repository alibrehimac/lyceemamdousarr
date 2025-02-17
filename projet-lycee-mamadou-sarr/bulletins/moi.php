<?php
require_once('includes/header.php');
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'utilisateur
try {
    $stmt = $conn->prepare("SELECT * FROM login WHERE id_user = ?");
    $stmt->execute([$_SESSION['id_user']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des informations";
}

// Traitement de la mise à jour du profil
if (isset($_POST['update_profile'])) {
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    try {
        if (!empty($new_password)) {
            // Vérifier l'ancien mot de passe
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE login SET nom = ?, prenom = ?, password = ? WHERE id_user = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nom, $prenom, $hashed_password, $_SESSION['id_user']]);
            } else {
                $_SESSION['error'] = "Mot de passe actuel incorrect";
                header('Location: moi.php');
                exit();
            }
        } else {
            $sql = "UPDATE login SET nom = ?, prenom = ? WHERE id_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nom, $prenom, $_SESSION['id_user']]);
        }
        
        $_SESSION['message'] = "Profil mis à jour avec succès";
        $_SESSION['nom'] = $nom;
        $_SESSION['prenom'] = $prenom;
        
        header('Location: moi.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la mise à jour du profil";
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Mon Profil</h1>

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

    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-circle me-1"></i>
                    Informations personnelles
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" value="<?php echo $user['username']; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" value="<?php echo $user['nom']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom" value="<?php echo $user['prenom']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>
                        <hr>
                        <h5>Changer le mot de passe</h5>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" name="current_password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="new_password">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            Mettre à jour le profil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>