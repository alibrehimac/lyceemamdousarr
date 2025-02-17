<?php
require_once 'config.php';
require_once('includes/header.php');
require_once('vendor/phpqrcode/qrlib.php');

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Récupérer la liste des classes
$sql_classes = "SELECT c.idClasse, c.nom_classe, f.nom_filiere, p.annee_scolaire 
                FROM classe c
                JOIN filiere f ON c.code_filiere = f.code_filiere
                JOIN promotion p ON c.idpromotion = p.idpromotion
                ORDER BY p.annee_scolaire DESC, c.nom_classe";
$stmt_classes = $conn->query($sql_classes);
$classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

// Si une classe est sélectionnée
$idclasse = isset($_GET['idclasse']) ? intval($_GET['idclasse']) : null;

if ($idclasse) {
    // Récupérer les élèves de la classe
    $sql_eleves = "SELECT e.*, c.nom_classe, f.nom_filiere, p.annee_scolaire
                   FROM eleves e
                   JOIN inscrire i ON e.matricule = i.matricule
                   JOIN classe c ON i.idClasse = c.idClasse
                   JOIN filiere f ON c.code_filiere = f.code_filiere
                   JOIN promotion p ON c.idpromotion = p.idpromotion
                   WHERE c.idClasse = ?
                   ORDER BY e.nom, e.prenom";
    $stmt_eleves = $conn->prepare($sql_eleves);
    $stmt_eleves->execute([$idclasse]);
    $eleves = $stmt_eleves->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour générer le QR code
function generateQRCode($data, $matricule) {
    // Créer le dossier cache s'il n'existe pas
    if (!file_exists('temp/qrcodes/')) {
        mkdir('temp/qrcodes/', 0777, true);
    }
    
    // Nom du fichier QR code
    $qrFile = 'temp/qrcodes/qr_'.$matricule.'.png';
    
    // Générer le QR code
    QRcode::png($data, $qrFile, QR_ECLEVEL_M, 4);
    
    return $qrFile;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartes Scolaires</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <script src="assets/js/qrcode.js"></script>
    <style>
        .card-scolaire {
            width: 360px;
            height: 220px;
            padding: 12px;
            margin: 10px;
            position: relative;
            background: linear-gradient(45deg, #2196F3 0%, #1976D2 100%);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            page-break-inside: avoid;
            overflow: hidden;
        }

        /* Ajout du fond en filigrane */
        .card-scolaire::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('assets/img/background.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.1; /* Ajustez cette valeur pour la transparence */
            mix-blend-mode: overlay;
            z-index: 0;
        }

        /* Assurer que tout le contenu est au-dessus du filigrane */
        .header-section,
        .identity-section,
        .student-section,
        .card-footer,
        .mali-flag,
        .qr-code {
            position: relative;
            z-index: 1;
        }

        /* Version bleue et grise avec filigrane */
        .card-scolaire.blue {
            background: linear-gradient(45deg, rgba(33, 150, 243, 0.95) 0%, rgba(25, 118, 210, 0.95) 100%);
            color: white;
        }
        
        .card-scolaire.gray {
            background: linear-gradient(45deg, rgba(117, 117, 117, 0.95) 0%, rgba(66, 66, 66, 0.95) 100%);
            color: white;
        }

        .header-section {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            position: relative;
            height: 40px;
        }

        .logo-container {
            width: 35px;
            height: 35px;
            margin-right: 10px;
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .school-header {
            flex-grow: 1;
            text-align: center;
        }

        .school-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 0;
            line-height: 1.2;
        }

        .school-contact {
            font-size: 10px;
            line-height: 1;
        }

        .mali-flag {
            position: absolute;
            top: 0;
            right: 0;
            width: 30px;
            height: 20px;
            background: linear-gradient(to right, 
                #14B53A 33.33%,
                #FCD116 33.33%,
                #FCD116 66.66%,
                #CE1126 66.66%
            );
            border-radius: 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .identity-section {
            background: #1a237e;
            padding: 6px 8px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .identity-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
            line-height: 1;
        }

        .identity-section div:last-child {
            font-size: 11px;
        }

        .student-section {
            display: flex;
            margin-bottom: 8px;
        }

        .student-photo {
            width: 70px;
            height: 85px;
            margin-right: 10px;
        }

        .student-info {
            flex-grow: 1;
            font-size: 11px;
            margin-right: 70px; /* Espace pour le QR code */
        }

        .student-info p {
            margin: 3px 0;
            line-height: 1.2;
        }

        .student-info strong {
            width: 65px;
            display: inline-block;
        }

        .card-footer {
            position: absolute;
            bottom: 8px;
            left: 12px;
            right: 12px;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 6px;
            font-size: 9px;
        }

        .website, .contact-info {
            line-height: 1.2;
        }

        .expiry-date {
            font-size: 9px;
        }

        .qr-code {
            position: absolute;
            right: 12px;
            top: 50px;
            width: 60px;
            height: 60px;
            background: white;
            padding: 4px;
            border-radius: 4px;
        }

        @media print {
            .no-print {
                display: none;
            }
            .card-scolaire {
                break-inside: avoid;
                margin: 0;
                page-break-inside: avoid;
            }
            .row {
                margin: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Sélection de classe -->
        <div class="row mb-4 no-print">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sélectionner une classe</h5>
                        <form method="GET" class="d-flex gap-2">
                            <select name="idclasse" class="form-select">
                                <option value="">Choisir une classe</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo $classe['idClasse']; ?>"
                                            <?php if ($idclasse == $classe['idClasse']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($classe['nom_classe'] . ' - ' . 
                                                                   $classe['nom_filiere'] . ' (' . 
                                                                   $classe['annee_scolaire'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Afficher</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php if ($idclasse): ?>
            <div class="col-md-6">
                <button onclick="window.print()" class="btn btn-success">
                    <i class="fas fa-print me-2"></i>Imprimer les cartes
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Affichage des cartes -->
        <?php if ($idclasse && !empty($eleves)): ?>
            <div class="row">
                <?php foreach ($eleves as $index => $eleve): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card-scolaire <?php echo $index % 2 == 0 ? 'blue' : 'gray'; ?>">
                            <div class="header-section">
                                <div class="logo-container">
                                    <img src="assets/img/logo.jpg" alt="Logo">
                                </div>
                                <div class="school-header">
                                    <div class="school-name">LYCÉE MAMADOU SARR</div>
                                    <div class="school-contact">Bamako, Mali</div>
                                </div>
                                <div class="mali-flag"></div>
                            </div>

                            <div class="identity-section">
                                <div class="identity-title">CARTE D'IDENTITÉ SCOLAIRE</div>
                                <div>ID: <?php echo htmlspecialchars($eleve['matricule']); ?></div>
                            </div>

                            <div class="student-section">
                                <div class="student-photo">
                                    <i class="fas fa-user fa-2x text-muted"></i>
                                </div>
                                <div class="student-info">
                                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($eleve['nom']); ?></p>
                                    <p><strong>Prénom:</strong> <?php echo htmlspecialchars($eleve['prenom']); ?></p>
                                    <p><strong>Classe:</strong> <?php echo htmlspecialchars($eleve['nom_classe']); ?></p>
                                    <p><strong>Né(e) le:</strong> <?php echo $eleve['date_naiss'] ? date('d/m/Y', strtotime($eleve['date_naiss'])) : 'Non renseignée'; ?></p>
                                </div>
                                <div class="qr-code">
                                    <?php
                                    // Création des données pour le QR code
                                    $qrData = json_encode([
                                        'matricule' => $eleve['matricule'],
                                        'nom' => $eleve['nom'],
                                        'prenom' => $eleve['prenom'],
                                        'classe' => $eleve['nom_classe'],
                                        'dateNaissance' => $eleve['date_naiss'] ? date('d/m/Y', strtotime($eleve['date_naiss'])) : '',
                                        'anneeScolaire' => $eleve['annee_scolaire'],
                                        'ecole' => 'LYCÉE MAMADOU SARR'
                                    ]);
                                    
                                    // Générer le QR code
                                    $qrFile = generateQRCode($qrData, $eleve['matricule']);
                                    ?>
                                    <img src="<?php echo $qrFile; ?>" alt="QR Code" style="width: 100%; height: 100%;">
                                </div>
                            </div>

                            <div class="card-footer">
                                <div>
                                    <div class="website">www.lyceemamadousarr.edu.ml</div>
                                    <div class="contact-info">En cas de perte: +223 96 93 93 96</div>
                                </div>
                                <div class="expiry-date">
                                    Expire le: <?php 
                                        $annee_scolaire = explode('-', $eleve['annee_scolaire']);
                                        echo "30/06/" . $annee_scolaire[1];
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($idclasse): ?>
            <div class="alert alert-info">Aucun élève trouvé dans cette classe.</div>
        <?php endif; ?>
    </div>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html> 