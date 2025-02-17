<?php
// Démarrer la mise en tampon de sortie
ob_start();

require_once('includes/header.php');
require_once 'config.php';
require_once('fpdf/fpdf.php'); // Téléchargez FPDF et placez-le dans un dossier fpdf

// Vider le tampon de sortie
ob_clean();

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Récupération des filtres
$filiere_filter = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$classe_filter = isset($_GET['classe']) ? $_GET['classe'] : '';

// Construction de la requête
$sql = "SELECT DISTINCT e.*, c.nom_classe, f.nom_filiere, p.annee_scolaire 
        FROM eleves e 
        LEFT JOIN inscrire i ON e.matricule = i.matricule 
        LEFT JOIN classe c ON i.idClasse = c.idClasse 
        LEFT JOIN filiere f ON c.code_filiere = f.code_filiere 
        LEFT JOIN promotion p ON c.idpromotion = p.idpromotion";

$params = [];
if ($classe_filter) {
    $sql .= " WHERE i.idClasse = ?";
    $params[] = $classe_filter;
} elseif ($filiere_filter) {
    $sql .= " WHERE f.code_filiere = ?";
    $params[] = $filiere_filter;
}

$sql .= " ORDER BY e.nom, e.prenom";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('Liste des élèves'), 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Création du PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Titre spécifique si filtre
if ($classe_filter && !empty($eleves)) {
    $pdf->Cell(0, 10, utf8_decode('Classe : ' . $eleves[0]['nom_classe'] . ' ' . $eleves[0]['nom_filiere']), 0, 1, 'C');
} elseif ($filiere_filter && !empty($eleves)) {
    $pdf->Cell(0, 10, utf8_decode('Filière : ' . $eleves[0]['nom_filiere']), 0, 1, 'C');
}

// En-têtes du tableau
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 7, 'Matricule', 1);
$pdf->Cell(40, 7, 'Nom', 1);
$pdf->Cell(40, 7, utf8_decode('Prénom'), 1);
$pdf->Cell(30, 7, 'Date naiss.', 1);
$pdf->Cell(30, 7, 'Classe', 1);
$pdf->Cell(20, 7, utf8_decode('Tél.'), 1);
$pdf->Ln();

// Données
$pdf->SetFont('Arial', '', 10);
foreach($eleves as $eleve) {
    $pdf->Cell(30, 6, $eleve['matricule'], 1);
    $pdf->Cell(40, 6, utf8_decode($eleve['nom']), 1);
    $pdf->Cell(40, 6, utf8_decode($eleve['prenom']), 1);
    $pdf->Cell(30, 6, $eleve['date_naiss'], 1);
    $pdf->Cell(30, 6, utf8_decode($eleve['nom_classe'] ?? '-'), 1);
    $pdf->Cell(20, 6, $eleve['tel'], 1);
    $pdf->Ln();
}

// Vider tout tampon de sortie restant
ob_end_clean();

// Sortie du PDF
$pdf->Output('liste_eleves.pdf', 'I'); 