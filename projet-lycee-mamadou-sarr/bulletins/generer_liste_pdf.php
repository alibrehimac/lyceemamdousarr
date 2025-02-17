<?php
require_once 'config.php';
require_once 'vendor/autoload.php'; // Pour TCPDF

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les paramètres
$filiere_id = isset($_GET['filiere']) ? $_GET['filiere'] : null;
$classe_id = isset($_GET['classe']) ? $_GET['classe'] : null;

// Construire la requête selon les filtres
try {
    $sql = "SELECT DISTINCT e.*, c.nom_classe, f.nom_filiere, p.annee_scolaire
            FROM eleves e
            LEFT JOIN inscrire i ON e.matricule = i.matricule
            LEFT JOIN classe c ON i.idClasse = c.idClasse
            LEFT JOIN filiere f ON c.code_filiere = f.code_filiere
            LEFT JOIN promotion p ON c.idpromotion = p.idpromotion";
    
    $params = [];
    $where = [];
    
    if ($filiere_id) {
        $where[] = "f.code_filiere = :filiere_id";
        $params[':filiere_id'] = $filiere_id;
    }
    if ($classe_id) {
        $where[] = "c.idClasse = :classe_id";
        $params[':classe_id'] = $classe_id;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY e.nom, e.prenom";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Créer le PDF
    class PDF extends TCPDF {
        public function Header() {
            $this->SetFont('helvetica', 'B', 15);
            $this->Cell(0, 15, 'Liste des Élèves - Lycée Mamadou Sarr', 0, true, 'C');
            $this->Ln(10);
        }

        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
        }
    }

    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 11);

    // Ajouter les informations de filtrage
    if ($filiere_id) {
        $stmt = $conn->prepare("SELECT nom_filiere FROM filiere WHERE code_filiere = ?");
        $stmt->execute([$filiere_id]);
        $filiere = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdf->Cell(0, 10, 'Filière : ' . $filiere['nom_filiere'], 0, 1);
    }
    if ($classe_id) {
        $stmt = $conn->prepare("SELECT c.nom_classe, f.nom_filiere FROM classe c JOIN filiere f ON c.code_filiere = f.code_filiere WHERE c.idClasse = ?");
        $stmt->execute([$classe_id]);
        $classe = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdf->Cell(0, 10, 'Classe : ' . $classe['nom_classe'] . ' ' . $classe['nom_filiere'], 0, 1);
    }
    $pdf->Ln(5);

    // En-têtes du tableau
    $header = array('N°', 'Matricule', 'Nom', 'Prénom', 'Classe', 'Contact');
    $w = array(15, 35, 40, 40, 35, 25);
    
    $pdf->SetFillColor(230, 230, 230);
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Données
    $pdf->SetFillColor(255, 255, 255);
    $numero = 1;
    foreach($eleves as $eleve) {
        $pdf->Cell($w[0], 6, $numero, 1);
        $pdf->Cell($w[1], 6, $eleve['matricule'], 1);
        $pdf->Cell($w[2], 6, $eleve['nom'], 1);
        $pdf->Cell($w[3], 6, $eleve['prenom'], 1);
        $pdf->Cell($w[4], 6, $eleve['nom_classe'] ?? '-', 1);
        $pdf->Cell($w[5], 6, $eleve['tel'] ?? '-', 1);
        $pdf->Ln();
        $numero++;
    }

    // Générer le PDF
    $pdf->Output('liste_eleves.pdf', 'D');

} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la génération du PDF : " . $e->getMessage();
    header('Location: eleve_liste.php');
    exit();
} 