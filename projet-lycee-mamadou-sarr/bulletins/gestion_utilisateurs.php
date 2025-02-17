<?php
// Définir la page active pour le menu
$page = 'administration';
$subpage = 'users';

require_once('config.php');
require_once('includes/header.php');

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Traitement de l'ajout d'un utilisateur
if (isset($_POST['ajouter'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    
    try {
        $sql = "INSERT INTO login (username, password, role, nom, prenom) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username, $password, $role, $nom, $prenom]);
        
        $_SESSION['message'] = "Utilisateur ajouté avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur";
    }
    
    header('Location: gestion_utilisateurs.php');
    exit();
}

// Traitement de la modification d'un utilisateur
if (isset($_POST['modifier'])) {
    $id_user = $_POST['id_user'];
    $username = htmlspecialchars($_POST['username']);
    $role = $_POST['role'];
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    
    try {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE login SET username = ?, password = ?, role = ?, nom = ?, prenom = ? WHERE id_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $password, $role, $nom, $prenom, $id_user]);
        } else {
            $sql = "UPDATE login SET username = ?, role = ?, nom = ?, prenom = ? WHERE id_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $role, $nom, $prenom, $id_user]);
        }
        
        $_SESSION['message'] = "Utilisateur modifié avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification de l'utilisateur";
    }
    
    header('Location: gestion_utilisateurs.php');
    exit();
}

// Traitement de la suppression d'un utilisateur
if (isset($_POST['supprimer'])) {
    $id_user = $_POST['id_user'];
    
    try {
        $sql = "DELETE FROM login WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_user]);
        
        $_SESSION['message'] = "Utilisateur supprimé avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur";
    }
    
    header('Location: gestion_utilisateurs.php');
    exit();
}

// Récupération de la liste des utilisateurs
$sql = "SELECT * FROM login ORDER BY username";
$stmt = $conn->query($sql);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Contenu principal -->
    <div class="container mt-4">
    <h2 class="mb-4">
        <i class="fas fa-users-cog me-2"></i>
        Gestion des utilisateurs
    </h2>
        
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

        <!-- Bouton d'ajout -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#ajoutModal">
        <i class="fas fa-plus me-2"></i>
            Ajouter un utilisateur
        </button>

        <!-- Tableau des utilisateurs -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Nom d'utilisateur</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $user): ?>
                <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['nom']); ?></td>
                            <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                data-bs-target="#modifierModal<?php echo $user['id_user']; ?>">
                                    <i class="fas fa-edit me-1"></i> Modifier
                        </button>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                    data-bs-target="#supprimerModal<?php echo $user['id_user']; ?>">
                                    <i class="fas fa-trash me-1"></i> Supprimer
                            </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
            </div>
        </div>
    </div>

        <!-- Modal Ajout -->
        <div class="modal fade" id="ajoutModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un utilisateur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" name="password" required>
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
                                <label class="form-label">Rôle</label>
                                <select class="form-control" name="role" required>
                                <option value="user">Utilisateur</option>
                                    <option value="admin">Administrateur</option>
                                </select>
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

    <!-- Modals Modification et Suppression -->
        <?php foreach ($utilisateurs as $user): ?>
            <!-- Modal Modification -->
            <div class="modal fade" id="modifierModal<?php echo $user['id_user']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier l'utilisateur</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="id_user" value="<?php echo $user['id_user']; ?>">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nom d'utilisateur</label>
                                    <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <div class="mb-3">
                                <label class="form-label">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                                <input type="password" class="form-control" name="password">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nom</label>
                                    <input type="text" class="form-control" name="nom" 
                                       value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" class="form-control" name="prenom" 
                                       value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Rôle</label>
                                    <select class="form-control" name="role" required>
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>
                                        Utilisateur
                                    </option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                            Administrateur
                                        </option>
                                    </select>
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
            <div class="modal fade" id="supprimerModal<?php echo $user['id_user']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmer la suppression</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                        Êtes-vous sûr de vouloir supprimer l'utilisateur "<?php echo htmlspecialchars($user['username']); ?>" ?
                        </div>
                        <div class="modal-footer">
                            <form method="POST">
                                <input type="hidden" name="id_user" value="<?php echo $user['id_user']; ?>">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" name="supprimer" class="btn btn-danger">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php require_once('includes/footer.php'); ?>
