<?php
require_once('includes/header.php');
require_once 'config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Traitement du transfert (soumission du formulaire)
if (isset($_POST['transferer'])) {
    $matricule = $_POST['matricule'];
    $nouvelle_classe = $_POST['nouvelle_classe'];

    try {
        // Vérifier si l'élève est déjà inscrit dans une classe
        $check = $conn->prepare("SELECT idClasse FROM inscrire WHERE matricule = ?");
        $check->execute([$matricule]);
        $current_classe = $check->fetchColumn();

        if ($current_classe) {
            // Mise à jour de la classe de l'élève
            $sql = "UPDATE inscrire SET idClasse = ? WHERE matricule = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nouvelle_classe, $matricule]);
        } else {
            // Insertion si l'élève n'est pas encore inscrit
            $sql = "INSERT INTO inscrire (matricule, idClasse) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$matricule, $nouvelle_classe]);
        }

        $_SESSION['message'] = "Élève transféré avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors du transfert de l'élève";
    }

    header('Location: gestion_eleves.php');
    exit();
}

// Vérifier que le matricule est passé en GET
if (!isset($_GET['matricule'])) {
    $_SESSION['error'] = "Matricule non spécifié.";
    header('Location: gestion_eleves.php');
    exit();
}

$matricule = $_GET['matricule'];

// Récupération de la classe actuelle de l'élève
$inscription = $conn->prepare("SELECT idClasse FROM inscrire WHERE matricule = ?");
$inscription->execute([$matricule]);
$current_class_id = $inscription->fetchColumn();

if (!$current_class_id) {
    $_SESSION['error'] = "Cet élève n'est pas inscrit dans une classe. Veuillez l'inscrire d'abord.";
    header('Location: gestion_eleves.php');
    exit();
}

// Récupération des détails de la classe actuelle, y compris l'ID de la promotion (série)
$classQuery = $conn->prepare("SELECT c.idClasse, c.nom_classe, c.idpromotion, p.annee_scolaire 
                              FROM classe c 
                              JOIN promotion p ON c.idpromotion = p.idpromotion 
                              WHERE c.idClasse = ?");
$classQuery->execute([$current_class_id]);
$current_class = $classQuery->fetch(PDO::FETCH_ASSOC);

if (!$current_class) {
    $_SESSION['error'] = "Classe actuelle introuvable.";
    header('Location: gestion_eleves.php');
    exit();
}

$idpromotion = $current_class['idpromotion'];

// Récupération de toutes les classes de la même promotion (même série)
// Même si les filières peuvent différer, seules les classes appartenant à la même série sont proposées.
$classesQuery = $conn->prepare("SELECT c.idClasse, c.nom_classe, f.nom_filiere 
                                FROM classe c 
                                JOIN promotion p ON c.idpromotion = p.idpromotion 
                                JOIN filiere f ON c.code_filiere = f.code_filiere 
                                WHERE p.idpromotion = ? 
                                ORDER BY c.nom_classe");
$classesQuery->execute([$idpromotion]);
$classes = $classesQuery->fetchAll(PDO::FETCH_ASSOC);

// Récupération des informations de l'élève pour affichage (facultatif)
$studentQuery = $conn->prepare("SELECT * FROM eleves WHERE matricule = ?");
$studentQuery->execute([$matricule]);
$student = $studentQuery->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Transfert d'un élève</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Transfert de l'élève</h1>
    <?php if ($student): ?>
        <p><strong>Élève :</strong> <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></p>
        <p><strong>Classe actuelle :</strong> <?php echo htmlspecialchars($current_class['nom_classe']); ?> (<?php echo htmlspecialchars($current_class['annee_scolaire']); ?>)</p>
    <?php endif; ?>

    <form method="POST" action="transfert.php">
        <input type="hidden" name="matricule" value="<?php echo htmlspecialchars($matricule); ?>">
        <div class="mb-3">
            <label for="nouvelle_classe" class="form-label">Nouvelle classe</label>
            <select class="form-select" name="nouvelle_classe" id="nouvelle_classe" required>
                <option value="">-- Sélectionnez une classe --</option>
                <?php foreach ($classes as $classe): ?>
                    <?php if ($classe['idClasse'] != $current_class['idClasse']): ?>
                        <option value="<?php echo $classe['idClasse']; ?>">
                            <?php echo htmlspecialchars($classe['nom_classe'] . ' - ' . $classe['nom_filiere']); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="transferer" class="btn btn-primary">Confirmer le transfert</button>
        <a href="gestion_eleves.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
