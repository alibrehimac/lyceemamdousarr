<?php
require_once 'config.php';
require_once('fpdf/fpdf.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$idclasse = isset($_GET['idclasse']) ? intval($_GET['idclasse']) : null;
$idperiode = isset($_GET['idperiode']) ? intval($_GET['idperiode']) : null;

if (!$idclasse || !$idperiode) {
    die("Paramètres manquants");
}

try {
    // Récupérer tous les élèves de la classe
    $stmt = $conn->prepare("SELECT e.* 
                           FROM eleves e 
                           JOIN inscrire i ON e.matricule = i.matricule 
                           WHERE i.idClasse = ? 
                           ORDER BY e.nom, e.prenom");
    $stmt->execute([$idclasse]);
    $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($eleves)) {
        die("Aucun élève trouvé dans cette classe");
    }

    // D'abord, calculons les moyennes pour tous les élèves
    $moyennes_eleves = [];
    
    foreach($eleves as $eleve) {
        $stmt = $conn->prepare("
            SELECT m.nom_matiere, a.coefficient,
                   n.note_classe, n.note_examen
            FROM matieres m
            INNER JOIN associer a ON m.id_matiere = a.id_matiere
            INNER JOIN classe c ON c.code_filiere = a.code_filiere
            LEFT JOIN notes n ON n.id_matiere = m.id_matiere 
                AND n.matricule = ? 
                AND n.idperiode = ?
            WHERE c.idClasse = ?
            ORDER BY m.nom_matiere");

        $stmt->execute([$eleve['matricule'], $idperiode, $idclasse]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculer la moyenne de l'élève
        $total_points = 0;
        $total_coef = 0;
        
        foreach($notes as $note) {
            if (!empty($note['note_examen'])) {
                $moyenne = 0;
                if (empty($note['note_classe'])) {
                    $moyenne = $note['note_examen'];
                } else {
                    $moyenne = ($note['note_classe'] + 2 * $note['note_examen']) / 3;
                }
                
                $produit = $moyenne * $note['coefficient'];
                $total_points += $produit;
                $total_coef += $note['coefficient'];
            }
        }
        
        // Stocker la moyenne de l'élève
        $moyenne_generale = $total_coef > 0 ? round($total_points / $total_coef, 2) : 0;
        $moyennes_eleves[] = [
            'matricule' => $eleve['matricule'],
            'moyenne' => $moyenne_generale
        ];
    }
    
    // Trier les moyennes par ordre décroissant
    usort($moyennes_eleves, function($a, $b) {
        return $b['moyenne'] <=> $a['moyenne'];
    });
    
    // Attribuer les rangs avec gestion des ex-aequo
    $rangs = [];
    $rang_actuel = 1;
    $position = 0;
    $prev_moyenne = null;
    $nb_ex_aequo = 1;
    
    foreach($moyennes_eleves as $index => $eleve) {
        $position++;
        
        if ($prev_moyenne !== null && $eleve['moyenne'] < $prev_moyenne) {
            // Si la moyenne est différente, on prend la position actuelle
            $rang_actuel = $position;
            $nb_ex_aequo = 1;
        } else if ($prev_moyenne !== null && $eleve['moyenne'] == $prev_moyenne) {
            // Ex-aequo : même rang que le précédent
            $nb_ex_aequo++;
        }
        
        $rangs[$eleve['matricule']] = [
            'rang' => $rang_actuel,
            'ex_aequo' => $nb_ex_aequo > 1
        ];
        
        $prev_moyenne = $eleve['moyenne'];
    }

    // Créer le PDF
    class PDF extends FPDF {
        function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
            $txt = utf8_decode($txt);
            parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
        }

        function BulletinHeader($y_start, $is_second_bulletin) {
            if ($is_second_bulletin) {
                $y_start = 148;
            }
            
            $this->SetY($y_start);
            
            // Paramètres du logo
            $logo_size = 20; // Augmentation de la taille du logo
            $x_logo = 15;
            $y_logo = $y_start + 5;
            
            // Créer un masque circulaire pour le logo
            $this->_out('q');
            $this->_out(sprintf('q %.2F %.2F %.2F %.2F re W n',
                $x_logo * $this->k,
                ($this->h - ($y_logo + $logo_size)) * $this->k,
                $logo_size * $this->k,
                $logo_size * $this->k
            ));
            
            // Dessiner un cercle blanc pour le fond
            $radius = $logo_size / 2;
            $cx = $x_logo + $radius;
            $cy = $y_logo + $radius;
            
            // Créer le chemin du cercle
            $this->_out(sprintf('%.2F %.2F m', 
                ($cx + $radius * cos(0)) * $this->k, 
                ($this->h - ($cy + $radius * sin(0))) * $this->k));
            
            for($i = 1; $i <= 360; $i++) {
                $angle = $i * M_PI / 180;
                $this->_out(sprintf('%.2F %.2F l',
                    ($cx + $radius * cos($angle)) * $this->k,
                    ($this->h - ($cy + $radius * sin($angle))) * $this->k
                ));
            }
            
            $this->_out('h W n');
            
            // Insérer le logo
            $this->Image('assets/img/logo.jpg', $x_logo, $y_logo, $logo_size, $logo_size);
            
            $this->_out('Q');
            
            // En-tête du bulletin (décalé à droite du logo)
            $this->SetFont('Times', 'B', 11);
            $this->SetX($x_logo + $logo_size + 5); // Décalage après le logo
            $this->Cell(105, 6, 'LYCEE MAMADOU SARR', 0, 0, 'L');
            $this->Cell(60, 6, 'LMSARR # ' . $this->trimestre, 0, 1, 'R');
            
            // Informations de l'élève
            $this->SetFont('Times', 'B', 10);
            $this->SetX($x_logo + $logo_size + 5);
            $this->Cell(165, 6, 'Nom et Prénoms : ' . $this->nom_complet, 0, 1, 'L');
            $this->SetFont('Times', '', 10);
            $this->SetX($x_logo + $logo_size + 5);
            $this->Cell(165, 6, 'Matricule : ' . $this->matricule . ' né(e) le ' . $this->date_naiss . ' à ' . $this->lieu_naiss, 0, 1, 'L');
            $this->SetX($x_logo + $logo_size + 5);
            $this->Cell(165, 6, 'Classe : ' . $this->classe, 0, 1, 'L');
            
            // Tableau des notes
            $this->Ln(2);
            
            // En-tête du tableau
            $this->SetFont('Times', 'B', 10);
            
            // Largeurs des colonnes
            $w1 = 70; // Matières
            $w2 = 20; // Moy.Cls
            $w3 = 20; // Moy.Comp
            $w4 = 15; // Coef
            $w5 = 15; // Moy
            $w6 = 20; // Produit
            $w7 = 30; // Appréciation
            
            $this->Cell($w1, 6, 'Matières', 1, 0, 'L');
            $this->Cell($w2, 6, 'Moy.Clss', 1, 0, 'C');
            $this->Cell($w3, 6, 'Moy.Comp', 1, 0, 'C');
            $this->Cell($w4, 6, 'Coef', 1, 0, 'C');
            $this->Cell($w5, 6, 'Moy', 1, 0, 'C');
            $this->Cell($w6, 6, 'Produit', 1, 0, 'C');
            $this->Cell($w7, 6, 'Appréciations', 1, 1, 'C');
        }

        public $trimestre;
        public $matricule;
        public $nom_complet;
        public $classe;
        public $date_naiss;
        public $lieu_naiss;

        // Ajouter une méthode pour calculer la hauteur du bulletin
        function CalculateHeight($nb_matieres) {
            $header_height = 25; // Hauteur de l'en-tête
            $row_height = 5; // Hauteur d'une ligne de matière
            $footer_height = 20; // Hauteur du pied de bulletin
            
            return $header_height + ($nb_matieres * $row_height) + $footer_height;
        }
    }

    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->SetAutoPageBreak(true, 10);
    $bulletins_par_page = 2;
    $current_y = 10;

    // Ajouter la première page avant de commencer
    $pdf->AddPage();

    // Pour chaque élève
    foreach($eleves as $index => $eleve) {
        // Nouvelle page si nécessaire
        if ($index % 2 == 0) {
            $pdf->AddPage();
            $current_y = 10;
        }
        
        // Déterminer si c'est le deuxième bulletin de la page
        $is_second_bulletin = ($index % 2 == 1);
        
        // Récupérer d'abord les notes pour calculer la hauteur nécessaire
        $stmt = $conn->prepare("
            SELECT m.nom_matiere, a.coefficient,
                   n.note_classe, n.note_examen
            FROM matieres m
            INNER JOIN associer a ON m.id_matiere = a.id_matiere
            INNER JOIN classe c ON c.code_filiere = a.code_filiere
            LEFT JOIN notes n ON n.id_matiere = m.id_matiere 
                AND n.matricule = ? 
                AND n.idperiode = ?
            WHERE c.idClasse = ?
            ORDER BY m.nom_matiere");

        $stmt->execute([$eleve['matricule'], $idperiode, $idclasse]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculer la hauteur nécessaire pour ce bulletin
        $bulletin_height = $pdf->CalculateHeight(count($notes));
        
        // Récupérer les informations complètes de l'élève
        $stmt = $conn->prepare("SELECT e.*, c.nom_classe, f.nom_filiere, p.annee_scolaire, t.trimestre
                               FROM eleves e 
                               JOIN inscrire i ON e.matricule = i.matricule
                               JOIN classe c ON i.idClasse = c.idClasse
                               JOIN filiere f ON c.code_filiere = f.code_filiere
                               JOIN promotion p ON i.idpromotion = p.idpromotion
                               JOIN trimestres t ON t.idperiode = ?
                               WHERE e.matricule = ?");
        $stmt->execute([$idperiode, $eleve['matricule']]);
        $info_eleve = $stmt->fetch(PDO::FETCH_ASSOC);

        // Configurer les propriétés du PDF pour cet élève
        $pdf->trimestre = $info_eleve['trimestre'] . ' ' . $info_eleve['annee_scolaire'];
        $pdf->matricule = $eleve['matricule'];
        $pdf->nom_complet = $eleve['nom'] . ' ' . $eleve['prenom'];
        $pdf->classe = $info_eleve['nom_classe'] . ' ' . $info_eleve['nom_filiere'];
        $pdf->date_naiss = date('d/m/Y', strtotime($info_eleve['date_naiss']));
        $pdf->lieu_naiss = $info_eleve['lieu_naiss'];

        // Afficher le bulletin
        $pdf->BulletinHeader($current_y, $is_second_bulletin);
        $pdf->SetFont('Times', '', 9);

        // Initialiser les totaux
        $total_points = 0;
        $total_coef = 0;

        // Afficher les notes avec des dimensions réduites
        foreach($notes as $note) {
            // Largeurs des colonnes
            $w1 = 70; // Matières
            $w2 = 20; // Moy.Cls
            $w3 = 20; // Moy.Comp
            $w4 = 15; // Coef
            $w5 = 15; // Moy
            $w6 = 20; // Produit
            $w7 = 30; // Appréciation
            
            // Affichage des données
            $pdf->Cell($w1, 5, utf8_decode($note['nom_matiere']), 1, 0, 'L');
            $pdf->Cell($w2, 5, $note['note_classe'] ?: '-', 1, 0, 'C');
            $pdf->Cell($w3, 5, $note['note_examen'] ?: '-', 1, 0, 'C');
            $pdf->Cell($w4, 5, $note['coefficient'], 1, 0, 'C');
            
            if (!empty($note['note_examen'])) {
                $moyenne = 0;
                if (empty($note['note_classe'])) {
                    // Si pas de note de classe, on prend la note d'examen
                    $moyenne = $note['note_examen'];
                } else {
                    // Si on a les deux notes, on applique la formule
                    $moyenne = ($note['note_classe'] + 2 * $note['note_examen']) / 3;
                }
                
                $produit = $moyenne * $note['coefficient'];
                $total_points += $produit;
                $total_coef += $note['coefficient'];
                
                $pdf->Cell($w5, 5, number_format($moyenne, 2), 1, 0, 'C');
                $pdf->Cell($w6, 5, number_format($produit, 2), 1, 0, 'C');
            } else {
                $pdf->Cell($w5, 5, '-', 1, 0, 'C');
                $pdf->Cell($w6, 5, '-', 1, 0, 'C');
            }
            
            // Appréciation
            $appreciation = 'Insuffisant'; // Valeur par défaut
            if (!empty($note['note_examen'])) {
                if ($moyenne >= 17) $appreciation = 'Excellent';
                elseif ($moyenne >= 15) $appreciation = 'Très Bien';
                elseif ($moyenne >= 14) $appreciation = 'Bien';
                elseif ($moyenne >= 12) $appreciation = 'Assez Bien';
                elseif ($moyenne >= 10) $appreciation = 'Passable';
                elseif ($moyenne >= 7) $appreciation = 'Médiocre';
                else $appreciation = 'Insuffisant';
            }
            
            $pdf->Cell($w7, 5, utf8_decode($appreciation), 1, 1, 'C');
        }

        // Ligne des totaux
        $pdf->SetFont('Times', '', 10);
        $w_total = $w1 + $w2 + $w3 + $w4; // Somme des 4 premières colonnes
        $pdf->Cell($w_total, 5, 'Total des coeff ' . $total_coef . '     Total des notes : ' . number_format($total_points, 2), 1, 0, 'L');
        $w_moy = $w5 + $w6 + $w7; // Somme des 3 dernières colonnes
        $moyenne_generale = $total_coef > 0 ? number_format($total_points / $total_coef, 2) : 0;
        $rang_info = isset($rangs[$eleve['matricule']]) ? $rangs[$eleve['matricule']] : ['rang' => count($eleves), 'ex_aequo' => false];
        $rang = $rang_info['rang'];
        $est_ex_aequo = $rang_info['ex_aequo'];
        $rang_suffix = ($rang == 1) ? 'er' : 'ème';
        $moyenne = isset($moyennes_eleves[array_search($eleve['matricule'], array_column($moyennes_eleves, 'matricule'))]['moyenne']) 
            ? number_format($moyennes_eleves[array_search($eleve['matricule'], array_column($moyennes_eleves, 'matricule'))]['moyenne'], 2) 
            : '-';
        $pdf->Cell($w_moy, 5, 'Moyenne trimestrielle: ' . $moyenne_generale . ' / 20', 1, 1, 'L');

        // Rang et effectif
        $rang_texte = sprintf('Rang : %d%s%s / %d classés', 
            $rang,
            $rang_suffix,
            $est_ex_aequo ? ' ex' : '',
            count($eleves)
        );
        $pdf->Cell(190, 5, $rang_texte, 1, 1, 'L');

        // Signature du proviseur (alignée à droite)
        $pdf->Ln(1);
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(130);
        $pdf->Cell(60, 4, 'Proviseur', 0, 1, 'R');
        $pdf->Cell(130);
        $pdf->Cell(60, 4, 'Amadou HAIDARA', 0, 1, 'R');

        // Ajouter la note en bas du bulletin
        $pdf->Ln(2);
        $pdf->SetFont('Times', '', 8); // Petits caractères
        $pdf->Cell(190, 3, 'NB : L\'utilisation du correcteur, les ratures et surcharges rendent le bulletin nul.', 0, 1, 'L');
        $pdf->Cell(190, 3, 'Le bulletin n\'est délivré qu\'une seule fois.', 0, 1, 'L');

        // Mettre à jour la position Y pour le prochain bulletin
        $current_y += $bulletin_height + 20;
        
        // Si c'est le premier bulletin de la page, ajouter la ligne de séparation
        if (!$is_second_bulletin && $index < count($eleves) - 1) {
            $pdf->Line(10, 145, 200, 145);
        }
    }

    // Générer le PDF
    $pdf->Output('Bulletins_Classe_' . $idclasse . '_' . $idperiode . '.pdf', 'I');
    exit();

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?> 