<?php
// Définir la page active pour le menu
$page = 'administration';
$subpage = 'classes';

require_once('config.php');
require_once('includes/header.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Ajouter au début du fichier
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Récupération des filières pour le formulaire
try {
    $sql_filieres = "SELECT f.*, s.series FROM filiere f 
                     JOIN series s ON f.idserie = s.idserie 
                     ORDER BY s.series, f.nom_filiere";
    $stmt_filieres = $conn->query($sql_filieres);
    $filieres = $stmt_filieres->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des filières";
    // Gérer l'erreur de manière appropriée
}

// Récupération des promotions pour le formulaire
try {
    $sql_promotions = "SELECT * FROM promotion ORDER BY annee_scolaire DESC";
    $stmt_promotions = $conn->query($sql_promotions);
    $promotions = $stmt_promotions->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des promotions";
    $promotions = [];
}

// Traitement de l'ajout d'une classe
if (isset($_POST['ajouter'])) {
    if (empty($_POST['nom_classe']) || !is_numeric($_POST['code_filiere']) || !is_numeric($_POST['idpromotion'])) {
        $_SESSION['error'] = "Données invalides";
        header('Location: gestion_classes.php');
        exit();
    }
    
    $nom_classe = htmlspecialchars($_POST['nom_classe']);
    $code_filiere = $_POST['code_filiere'];
    $idpromotion = $_POST['idpromotion'];
    
    try {
        // Compter le nombre de classes existantes pour cette filière et promotion
        $check = $conn->prepare("
            SELECT COUNT(*) as nb_classes 
            FROM classe 
            WHERE code_filiere = ? 
            AND idpromotion = ? 
            AND nom_classe LIKE ?
        ");
        
        // Retirer les indices numériques existants du nom de la classe
        $nom_base = preg_replace('/\s*\d+$/', '', $nom_classe);
        $check->execute([$code_filiere, $idpromotion, $nom_base . '%']);
        $result = $check->fetch(PDO::FETCH_ASSOC);
        
        // Ajouter l'indice si nécessaire
        if ($result['nb_classes'] > 0) {
            $nouvel_indice = $result['nb_classes'] + 1;
            $nom_classe = $nom_base . ' ' . $nouvel_indice;
        }
        
        // Insérer la nouvelle classe
        $sql = "INSERT INTO classe (nom_classe, code_filiere, idpromotion) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nom_classe, $code_filiere, $idpromotion]);
        
        $_SESSION['message'] = "Classe ajoutée avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de la classe: " . $e->getMessage();
    }
    
    header('Location: gestion_classes.php');
    exit();
}

// Traitement de la modification d'une classe
if (isset($_POST['modifier'])) {
    $idClasse = $_POST['idClasse'];
    $nom_classe = htmlspecialchars($_POST['nom_classe']);
    $code_filiere = $_POST['code_filiere'];
    $idpromotion = $_POST['idpromotion'];
    
    try {
        // Vérifier si on change de filière
        if ($code_filiere != $_POST['ancien_code_filiere']) {
            // Compter le nombre de classes existantes dans la nouvelle filière
            $check = $conn->prepare("
                SELECT COUNT(*) as nb_classes 
                FROM classe 
                WHERE code_filiere = ? 
                AND idpromotion = ? 
                AND nom_classe LIKE ?
            ");
            
            // Retirer les indices numériques existants du nom de la classe
            $nom_base = preg_replace('/\s*\d+$/', '', $nom_classe);
            $check->execute([$code_filiere, $idpromotion, $nom_base . '%']);
            $result = $check->fetch(PDO::FETCH_ASSOC);
            
            // Ajouter l'indice si nécessaire
            if ($result['nb_classes'] > 0) {
                $nouvel_indice = $result['nb_classes'] + 1;
                $nom_classe = $nom_base . ' ' . $nouvel_indice;
            }
        }
        
        $sql = "UPDATE classe SET nom_classe = ?, code_filiere = ?, idpromotion = ? WHERE idClasse = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nom_classe, $code_filiere, $idpromotion, $idClasse]);
        
        // Réorganiser les indices des classes restantes dans l'ancienne filière
        if ($code_filiere != $_POST['ancien_code_filiere']) {
            reorganiserIndicesClasses($conn, $_POST['ancien_code_filiere'], $idpromotion);
        }
        
        $_SESSION['message'] = "Classe modifiée avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification de la classe: " . $e->getMessage();
    }
    
    header('Location: gestion_classes.php');
    exit();
}

// Fonction pour réorganiser les indices des classes
function reorganiserIndicesClasses($conn, $code_filiere, $idpromotion) {
    try {
        // Récupérer toutes les classes de la filière
        $sql = "SELECT idClasse, nom_classe 
                FROM classe 
                WHERE code_filiere = ? 
                AND idpromotion = ? 
                ORDER BY nom_classe";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$code_filiere, $idpromotion]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mettre à jour les indices
        $indice = 1;
        foreach ($classes as $classe) {
            $nom_base = preg_replace('/\s*\d+$/', '', $classe['nom_classe']);
            $nouveau_nom = $nom_base;
            if (count($classes) > 1) {
                $nouveau_nom .= ' ' . $indice;
            }
            
            if ($nouveau_nom != $classe['nom_classe']) {
                $update = $conn->prepare("UPDATE classe SET nom_classe = ? WHERE idClasse = ?");
                $update->execute([$nouveau_nom, $classe['idClasse']]);
            }
            $indice++;
        }
    } catch(PDOException $e) {
        throw $e;
    }
}

// Traitement de la suppression d'une classe
if (isset($_POST['supprimer'])) {
    $idClasse = $_POST['idClasse'];
    
    try {
        $sql = "DELETE FROM classe WHERE idClasse = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$idClasse]);
        
        $_SESSION['message'] = "Classe supprimée avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression de la classe";
    }
    
    header('Location: gestion_classes.php');
    exit();
}

// Récupération de la liste des classes avec leurs filières et promotions
try {
    $sql = "SELECT c.*, f.nom_filiere, s.series, p.idpromotion, p.annee_scolaire 
            FROM classe c 
            JOIN filiere f ON c.code_filiere = f.code_filiere 
            JOIN series s ON f.idserie = s.idserie 
            JOIN promotion p ON c.idpromotion = p.idpromotion 
            ORDER BY p.annee_scolaire DESC, s.series, f.nom_filiere, c.nom_classe";
    $stmt = $conn->query($sql);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des classes";
    $classes = [];
}

?>

<!-- Contenu principal -->
<div class="container mt-4">
    <h2 class="mb-4">
        <i class="fas fa-chalkboard me-2"></i>
        Gestion des classes
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
        Ajouter une classe
    </button>

    <!-- Tableau des classes -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Série</th>
                            <th>Filière</th>
                            <th>Année scolaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($classes)): ?>
                            <?php foreach ($classes as $classe): ?>
                            <tr>
                                <td><?php echo $classe['nom_classe']; ?></td>
                                <td><?php echo $classe['series']; ?></td>
                                <td><?php echo $classe['nom_filiere']; ?></td>
                                <td><?php echo $classe['annee_scolaire']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                            data-bs-target="#modifierModal<?php echo $classe['idClasse']; ?>">
                                        <i class="fas fa-edit me-1"></i> Modifier
                                    </button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                            data-bs-target="#supprimerModal<?php echo $classe['idClasse']; ?>">
                                        <i class="fas fa-trash me-1"></i> Supprimer
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Aucune classe trouvée</td>
                            </tr>
                        <?php endif; ?>
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
                    <h5 class="modal-title">Ajouter une classe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de la classe</label>
                            <input type="text" class="form-control" name="nom_classe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Filière</label>
                            <select class="form-control" name="code_filiere" required>
                                <?php if (!empty($filieres)): ?>
                                    <?php foreach ($filieres as $filiere): ?>
                                        <option value="<?php echo $filiere['code_filiere']; ?>">
                                            <?php echo $filiere['series'] . ' - ' . $filiere['nom_filiere']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">Aucune filière disponible</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Année scolaire</label>
                            <select class="form-control" name="idpromotion" required>
                                <?php if (!empty($promotions)): ?>
                                    <?php foreach ($promotions as $promotion): ?>
                                        <option value="<?php echo $promotion['idpromotion']; ?>">
                                            <?php echo $promotion['annee_scolaire']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">Aucune année scolaire disponible</option>
                                <?php endif; ?>
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

    <!-- Modals Modification et Suppression pour chaque classe -->
    <?php foreach ($classes as $classe): ?>
        <!-- Modal Modification -->
        <div class="modal fade" id="modifierModal<?php echo $classe['idClasse']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la classe</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="idClasse" value="<?php echo $classe['idClasse']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nom de la classe</label>
                                <input type="text" class="form-control" name="nom_classe" 
                                       value="<?php echo $classe['nom_classe']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Filière</label>
                                <select class="form-control" name="code_filiere" required>
                                    <?php if (!empty($filieres)): ?>
                                        <?php foreach ($filieres as $filiere): ?>
                                            <option value="<?php echo $filiere['code_filiere']; ?>"
                                                <?php echo ($filiere['code_filiere'] == $classe['code_filiere']) ? 'selected' : ''; ?>>
                                                <?php echo $filiere['series'] . ' - ' . $filiere['nom_filiere']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">Aucune filière disponible</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Année scolaire</label>
                                <select class="form-control" name="idpromotion" required>
                                    <?php if (!empty($promotions)): ?>
                                        <?php foreach ($promotions as $promotion): ?>
                                            <option value="<?php echo $promotion['idpromotion']; ?>"
                                                <?php echo ($promotion['idpromotion'] == $classe['idpromotion']) ? 'selected' : ''; ?>>
                                                <?php echo $promotion['annee_scolaire']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">Aucune année scolaire disponible</option>
                                    <?php endif; ?>
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
        <div class="modal fade" id="supprimerModal<?php echo $classe['idClasse']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Êtes-vous sûr de vouloir supprimer la classe "<?php echo $classe['nom_classe']; ?>" ?
                    </div>
                    <div class="modal-footer">
                        <form method="POST">
                            <input type="hidden" name="idClasse" value="<?php echo $classe['idClasse']; ?>">
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
