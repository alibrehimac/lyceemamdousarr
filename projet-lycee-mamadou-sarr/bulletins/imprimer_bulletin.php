<?php
require_once('includes/header.php');
require_once 'config.php';
require_once('fpdf/fpdf.php'); // Assurez-vous d'avoir installé FPDF

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$matricule = isset($_GET['matricule']) ? $_GET['matricule'] : null;
$idperiode = isset($_GET['idperiode']) ? intval($_GET['idperiode']) : null;

if (!$matricule || !$idperiode) {
    die("Paramètres manquants");
}

try {
    // Récupérer les informations de l'élève
    $stmt = $conn->prepare("SELECT e.*, i.idClasse, c.nom_classe, f.nom_filiere, p.annee_scolaire, t.trimestre
                           FROM eleves e 
                           JOIN inscrire i ON e.matricule = i.matricule
                           JOIN classe c ON i.idClasse = c.idClasse
                           JOIN filiere f ON c.code_filiere = f.code_filiere
                           JOIN promotion p ON i.idpromotion = p.idpromotion
                           JOIN trimestres t ON t.idperiode = ?
                           WHERE e.matricule = ?");
    $stmt->execute([$idperiode, $matricule]);
    $eleve = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$eleve) {
        die("Élève non trouvé");
    }

    // Calculer les moyennes de tous les élèves de la classe
    $stmt = $conn->prepare("
        SELECT e.matricule, e.nom, e.prenom,
               SUM(((n.note_classe + 2*n.note_examen)/3) * mc.coefficient) as total_points,
               SUM(mc.coefficient) as total_coef,
               SUM(((n.note_classe + 2*n.note_examen)/3) * mc.coefficient) / SUM(mc.coefficient) as moyenne_generale
        FROM eleves e
        JOIN inscrire i ON e.matricule = i.matricule
        JOIN matiere_classe mc ON mc.idClasse = i.idClasse
        JOIN notes n ON n.matricule = e.matricule 
            AND n.id_matiere = mc.id_matiere
            AND n.idperiode = ?
            AND n.idpromotion = i.idpromotion
        WHERE i.idClasse = ?
        GROUP BY e.matricule
        ORDER BY moyenne_generale DESC, e.nom, e.prenom");
    
    $stmt->execute([$idperiode, $eleve['idClasse']]);
    $classement = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Trouver le rang de l'élève
    $rang = 1;
    $total_eleves = count($classement);
    foreach($classement as $pos => $el) {
        if($el['matricule'] === $matricule) {
            $rang = $pos + 1;
            break;
        }
    }

    // Récupérer les notes de l'élève
    $stmt = $conn->prepare("
        SELECT m.nom_matiere, a.coefficient,
               n.note_classe, n.note_examen,
               (SELECT COUNT(*) 
                FROM notes n2 
                JOIN inscrire i2 ON n2.matricule = i2.matricule 
                WHERE n2.id_matiere = m.id_matiere 
                AND n2.idperiode = ? 
                AND i2.idClasse = ?) as total_notes,
               (SELECT AVG((n2.note_classe + 2*n2.note_examen)/3)
                FROM notes n2 
                JOIN inscrire i2 ON n2.matricule = i2.matricule
                WHERE n2.id_matiere = m.id_matiere 
                AND n2.idperiode = ? 
                AND i2.idClasse = ?) as moyenne_classe
        FROM matieres m
        INNER JOIN associer a ON m.id_matiere = a.id_matiere
        INNER JOIN classe c ON c.code_filiere = a.code_filiere
        LEFT JOIN notes n ON n.id_matiere = m.id_matiere 
            AND n.matricule = ?
            AND n.idperiode = ?
        WHERE c.idClasse = ?
        ORDER BY m.nom_matiere");

    $stmt->execute([$idperiode, $eleve['idClasse'], $idperiode, $eleve['idClasse'], 
                    $matricule, $idperiode, $eleve['idClasse']]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Créer le PDF
    class PDF extends FPDF {
        function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
            $txt = utf8_decode($txt);
            parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
        }

        function Header() {
            // En-tête avec le nom du lycée
            $this->SetFont('Times', 'B', 10);
            $this->Cell(60, 5, 'LYCEE MAMADOU SARR', 0, 0, 'L');
            $this->Cell(70, 5, '', 0, 0);
            $this->Cell(60, 5, 'LMSARR # ' . $this->trimestre, 0, 1, 'R');
            
            // Infos de l'élève
            $this->SetFont('Times', '', 9);
            $this->Cell(190, 5, $this->nom_complet, 0, 1, 'L');
            $this->Cell(190, 5, 'Classe de : ' . $this->classe, 0, 1, 'L');
            
            // En-tête du tableau
            $this->SetFont('Times', 'B', 9);
            $this->Ln(5);
            
            // Largeurs des colonnes
            $w1 = 70; // Matières
            $w2 = 20; // Note classe
            $w3 = 20; // Note examen
            $w4 = 15; // Coef
            $w5 = 20; // Moyenne
            $w6 = 20; // Produit
            $w7 = 25; // Appréciation
            
            // En-têtes des colonnes
            $this->Cell($w1, 8, 'Matières', 1, 0, 'L');
            $this->Cell($w2, 8, 'Moy.Cls', 1, 0, 'C');
            $this->Cell($w3, 8, 'Moy.Comp', 1, 0, 'C');
            $this->Cell($w4, 8, 'Coef', 1, 0, 'C');
            $this->Cell($w5, 8, 'Moy', 1, 0, 'C');
            $this->Cell($w6, 8, 'Produit', 1, 0, 'C');
            $this->Cell($w7, 8, 'Appréciation', 1, 1, 'C');
        }
        
        // Propriétés pour stocker les infos de l'élève
        public $trimestre;
        public $nom_complet;
        public $classe;
    }

    $pdf = new PDF();
    $pdf->trimestre = $eleve['trimestre'] . ' ' . $eleve['annee_scolaire'];
    $pdf->nom_complet = $eleve['nom'] . ' ' . $eleve['prenom'] . ' : ' . $eleve['matricule'] . ' née à : ' . $eleve['lieu_naiss'] . ' le : ' . date('d/m/Y', strtotime($eleve['date_naiss']));
    $pdf->classe = $eleve['nom_classe'] . ' ' . $eleve['nom_filiere'];

    $pdf->AddPage();
    $pdf->SetFont('Times', '', 9);

    // Initialiser les totaux
    $total_points = 0;
    $total_coef = 0;

    // Pour chaque matière
    foreach($notes as $note) {
        // Largeurs des colonnes (mêmes valeurs que dans Header)
        $w1 = 70; // Matières
        $w2 = 20; // Note classe
        $w3 = 20; // Note examen
        $w4 = 15; // Coef
        $w5 = 20; // Moyenne
        $w6 = 20; // Produit
        $w7 = 25; // Appréciation
        
        $pdf->SetFont('Times', '', 9);
        
        // Affichage des données avec les nouvelles largeurs
        $pdf->Cell($w1, 6, utf8_decode($note['nom_matiere']), 1, 0, 'L');
        $pdf->Cell($w2, 6, $note['note_classe'] ?: '-', 1, 0, 'C');
        $pdf->Cell($w3, 6, $note['note_examen'] ?: '-', 1, 0, 'C');
        $pdf->Cell($w4, 6, $note['coefficient'], 1, 0, 'C');
        
        if (!empty($note['note_classe']) && !empty($note['note_examen'])) {
            $moyenne = ($note['note_classe'] + 2 * $note['note_examen']) / 3;
            $produit = $moyenne * $note['coefficient'];
            $pdf->Cell($w5, 6, number_format($moyenne, 2), 1, 0, 'C');
            $pdf->Cell($w6, 6, number_format($produit, 2), 1, 0, 'C');
        } else {
            $pdf->Cell($w5, 6, '-', 1, 0, 'C');
            $pdf->Cell($w6, 6, '-', 1, 0, 'C');
        }
        
        // Appréciation
        $appreciation = '-';
        if (isset($moyenne)) {
            if ($moyenne >= 17) $appreciation = 'Excellent';
            elseif ($moyenne >= 15) $appreciation = 'Très Bien';
            elseif ($moyenne >= 13) $appreciation = 'Bien';
            elseif ($moyenne >= 11) $appreciation = 'Assez Bien';
            elseif ($moyenne >= 10) $appreciation = 'Passable';
            elseif ($moyenne > 0) $appreciation = 'Médiocre';
            else $appreciation = 'Nul';
        }
        
        $pdf->Cell($w7, 6, utf8_decode($appreciation), 1, 1, 'C');
    }

    // Ligne des totaux avec les nouvelles largeurs
    $pdf->SetFont('Times', 'B', 9);
    $w_total = $w1 + $w2 + $w3 + $w4; // Somme des 4 premières colonnes
    $pdf->Cell($w_total, 6, 'Total des coeff ' . $total_coef . '     Total des notes : ' . number_format($total_points, 2), 1, 0, 'L');
    $w_moy = $w5 + $w6 + $w7; // Somme des 3 dernières colonnes
    $moyenne_generale = $total_coef > 0 ? number_format($total_points / $total_coef, 2) : 0;
    $pdf->Cell($w_moy, 6, 'Moyenne trimestrielle / 20 : ' . $moyenne_generale, 1, 1, 'L');

    // Rang et effectif
    $pdf->Cell(190, 6, 'Rang : ' . $rang . ' ème / ' . $total_eleves . ' classés', 1, 1, 'L');

    // Signature du proviseur
    $pdf->Ln(10);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(0, 5, 'Proviseur', 0, 1, 'R');
    $pdf->Cell(0, 5, ' Amadou HAIDARA', 0, 1, 'R');

    // Avant de générer le PDF, s'assurer qu'aucun contenu n'a été envoyé
    if (headers_sent()) {
        die("Impossible de générer le PDF : des données ont déjà été envoyées au navigateur.");
    }
    
    // Nettoyer tout buffer de sortie existant
    ob_clean();
    
    // Générer le PDF
    $pdf->Output('Bulletin_' . $matricule . '_' . $idperiode . '.pdf', 'I');
    exit();

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Récupérer les classes avec leurs filières et séries
try {
    $sql = "SELECT c.idClasse, c.nom_classe, f.nom_filiere, s.series, p.annee_scolaire 
            FROM classe c 
            JOIN filiere f ON c.code_filiere = f.code_filiere 
            JOIN series s ON f.idserie = s.idserie 
            JOIN promotion p ON c.idpromotion = p.idpromotion 
            ORDER BY p.annee_scolaire DESC, s.series, f.nom_filiere, c.nom_classe";
    $stmt = $conn->query($sql);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des classes";
}

// Récupérer les périodes (trimestres)
try {
    $sql = "SELECT t.*, p.annee_scolaire 
            FROM trimestres t 
            JOIN promotion p ON t.idpromotion = p.idpromotion 
            ORDER BY p.annee_scolaire DESC, t.numero";
    $stmt = $conn->query($sql);
    $periodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des périodes";
}

// Ajouter des styles CSS pour le bulletin
$styles = "
<style>
    .bulletin {
        font-family: Arial, sans-serif;
        padding: 20px;
    }
    .header img {
        max-width: 100px;
        height: auto;
    }
    .header {
        margin-bottom: 30px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    th, td {
        border: 1px solid #000;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f0f0f0;
    }
    .signatures {
        margin-top: 50px;
    }
    .text-center {
        text-align: center;
    }
    .mb-0 { margin-bottom: 0; }
    .mb-2 { margin-bottom: 10px; }
    .mb-3 { margin-bottom: 15px; }
    .mt-4 { margin-top: 20px; }
</style>
";
?>

<div class="container mt-4">
    <h1>Impression des bulletins</h1>

    <div class="card">
        <div class="card-body">
            <form id="bulletinForm" method="POST" action="generer_bulletin.php" target="_blank">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Classe</label>
                            <select class="form-select" name="idClasse" id="classeSelect" required>
                                <option value="">Sélectionner une classe</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo $classe['idClasse']; ?>">
                                        <?php echo $classe['nom_classe'] . ' ' . $classe['nom_filiere'] . 
                                                 ' (' . $classe['annee_scolaire'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Période</label>
                            <select class="form-select" name="idperiode" required>
                                <option value="">Sélectionner une période</option>
                                <?php foreach ($periodes as $periode): ?>
                                    <option value="<?php echo $periode['idperiode']; ?>">
                                        <?php echo $periode['nom'] . ' - ' . $periode['annee_scolaire']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Action</label>
                            <div>
                                <button type="button" class="btn btn-info" onclick="previsualiserBulletins()">
                                    <i class="fas fa-eye"></i> Prévisualiser
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-print"></i> Imprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Zone de prévisualisation -->
    <div id="previewZone" class="mt-4" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h5>Prévisualisation des bulletins</h5>
            </div>
            <div class="card-body" id="previewContent">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<script>
function previsualiserBulletins() {
    const form = document.getElementById('bulletinForm');
    const formData = new FormData(form);
    formData.append('preview', 'true');

    fetch('generer_bulletin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('previewContent').innerHTML = html;
        document.getElementById('previewZone').style.display = 'block';
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la prévisualisation');
    });
}
</script>

<?php require_once('includes/footer.php'); ?> 