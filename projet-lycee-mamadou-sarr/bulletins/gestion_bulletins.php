<?php
require_once('includes/header.php');
require_once 'config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Récupération et validation des paramètres
$idpromotion = isset($_GET['idpromotion']) ? intval($_GET['idpromotion']) : null;
$idclasse = isset($_GET['idclasse']) ? intval($_GET['idclasse']) : null;
$idperiode = isset($_GET['idperiode']) ? intval($_GET['idperiode']) : null;

// Vérifier si les paramètres requis sont présents
if (!$idpromotion) {
    $_SESSION['error'] = "Promotion non spécifiée";
    header('Location: select_classe.php');
    exit();
}

// Récupérer la promotion
try {
    $stmt = $conn->prepare("SELECT * FROM promotion WHERE idpromotion = ?");
    $stmt->execute([$idpromotion]);
    $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promotion) {
        $_SESSION['error'] = "Promotion non trouvée";
        header('Location: select_classe.php');
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération de la promotion";
    header('Location: select_classe.php');
    exit();
}

// Récupérer les classes de la promotion
try {
    $stmt = $conn->prepare("SELECT c.*, f.nom_filiere 
                           FROM classe c 
                           JOIN filiere f ON c.code_filiere = f.code_filiere 
                           WHERE c.idpromotion = ?");
    $stmt->execute([$idpromotion]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des classes";
    $classes = [];
}

// Récupérer les périodes (trimestres)
try {
    // Vérifier d'abord si des trimestres existent pour cette promotion
    $check = $conn->prepare("SELECT COUNT(*) FROM trimestres WHERE idpromotion = ?");
    $check->execute([$idpromotion]);
    $trimestres_count = $check->fetchColumn();
    
    if ($trimestres_count == 0) {
        // Si aucun trimestre n'existe, les créer automatiquement
        $conn->beginTransaction();
        
        $insert = $conn->prepare("INSERT INTO trimestres (trimestre, idpromotion) VALUES (?, ?)");
        
        // Créer les trois trimestres
        $insert->execute(['1er Trimestre', $idpromotion]);
        $insert->execute(['2ème Trimestre', $idpromotion]);
        $insert->execute(['3ème Trimestre', $idpromotion]);
        
        $conn->commit();
    }
    
    // Récupérer les trimestres
    $stmt = $conn->prepare("SELECT * FROM trimestres WHERE idpromotion = ? ORDER BY idperiode");
    $stmt->execute([$idpromotion]);
    $periodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($periodes)) {
        throw new Exception("Aucune période trouvée pour cette promotion");
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des périodes : " . $e->getMessage();
    $periodes = [];
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $periodes = [];
}

// Si une classe est sélectionnée, récupérer les élèves
$eleves = [];
if ($idclasse) {
    try {
        $stmt = $conn->prepare("SELECT e.*, i.idClasse 
                               FROM eleves e 
                               JOIN inscrire i ON e.matricule = i.matricule 
                               WHERE i.idClasse = ? AND i.idpromotion = ? 
                               ORDER BY e.nom, e.prenom");
        $stmt->execute([$idclasse, $idpromotion]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la récupération des élèves";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des bulletins</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Gestion des bulletins</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Sélection de la classe et de la période -->
        <form method="GET" class="row g-3 mb-4">
            <input type="hidden" name="idpromotion" value="<?php echo $idpromotion; ?>">
            
            <div class="col-md-4">
                <label class="form-label">Classe</label>
                <select name="idclasse" class="form-select" onchange="this.form.submit()">
                    <option value="">Sélectionner une classe</option>
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?php echo $classe['idClasse']; ?>" 
                                <?php echo $idclasse == $classe['idClasse'] ? 'selected' : ''; ?>>
                            <?php echo $classe['nom_classe'] . ' ' . $classe['nom_filiere']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Période</label>
                <?php if (!empty($periodes)): ?>
                    <select name="idperiode" class="form-select" onchange="this.form.submit()">
                        <option value="">Sélectionner une période</option>
                        <?php foreach ($periodes as $periode): ?>
                            <option value="<?php echo $periode['idperiode']; ?>" 
                                    <?php echo $idperiode == $periode['idperiode'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($periode['trimestre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Aucune période n'est configurée pour cette promotion.
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($idclasse && $idperiode && !empty($eleves)): ?>
            <!-- Bouton pour imprimer tous les bulletins -->
            <div class="mb-3">
                <a href="imprimer_bulletins_classe.php?idclasse=<?php echo $idclasse; ?>&idperiode=<?php echo $idperiode; ?>" 
                   class="btn btn-success" target="_blank">
                    <i class="fas fa-print me-2"></i>
                    Imprimer tous les bulletins de la classe
                </a>
            </div>

            <!-- Affichage des élèves et leurs bulletins -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Date de naissance</th>
                            <th>Lieu de naissance</th>
                            <th>Sexe</th>
                            <th>Contact</th>
                            <th>Parents</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eleves as $eleve): ?>
                            <tr>
                                <td><?php echo $eleve['matricule']; ?></td>
                                <td><?php echo $eleve['nom']; ?></td>
                                <td><?php echo $eleve['prenom']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($eleve['date_naiss'])); ?></td>
                                <td><?php echo $eleve['lieu_naiss']; ?></td>
                                <td><?php echo $eleve['sexe']; ?></td>
                                <td>
                                    <small>
                                        <i class="fas fa-phone me-1"></i><?php echo $eleve['tel']; ?><br>
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo $eleve['adresse']; ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <strong>Père:</strong> <?php echo $eleve['pere']; ?><br>
                                        <strong>Mère:</strong> <?php echo $eleve['mere']; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="bulletin.php?matricule=<?php echo $eleve['matricule']; ?>&idperiode=<?php echo $idperiode; ?>" 
                                           class="btn btn-primary btn-sm" target="_blank">
                                            <i class="fas fa-eye me-1"></i>
                                            Voir
                                        </a>
                                        <a href="imprimer_bulletin.php?matricule=<?php echo $eleve['matricule']; ?>&idperiode=<?php echo $idperiode; ?>" 
                                           class="btn btn-info btn-sm" target="_blank">
                                            <i class="fas fa-print me-1"></i>
                                            Imprimer
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Résumé de la classe -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations sur la classe
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Nombre d'élèves :</strong> <?php echo count($eleves); ?></p>
                            <p><strong>Garçons :</strong> 
                                <?php echo count(array_filter($eleves, function($e) { return $e['sexe'] == 'M'; })); ?>
                            </p>
                            <p><strong>Filles :</strong> 
                                <?php echo count(array_filter($eleves, function($e) { return $e['sexe'] == 'F'; })); ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Classe :</strong> <?php echo $classes[0]['nom_classe']; ?></p>
                            <p><strong>Filière :</strong> <?php echo $classes[0]['nom_filiere']; ?></p>
                            <p><strong>Année scolaire :</strong> <?php echo $promotion['annee_scolaire']; ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Période :</strong> 
                                <?php 
                                $periode_info = array_filter($periodes, function($p) use ($idperiode) { 
                                    return $p['idperiode'] == $idperiode; 
                                });
                                echo reset($periode_info)['trimestre'];
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($idclasse && empty($eleves)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucun élève trouvé dans cette classe.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 