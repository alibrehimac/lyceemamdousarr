<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['id_user'])) {
    exit('Accès non autorisé');
}

if (!isset($_POST['idClasse']) || !isset($_POST['idperiode'])) {
    exit('Paramètres manquants');
}

$idClasse = $_POST['idClasse'];
$idperiode = $_POST['idperiode'];
$preview = isset($_POST['preview']);

try {
    // Récupérer les informations de la classe avec sa filière et promotion
    $sql = "SELECT c.*, f.nom_filiere, f.code_filiere, s.series, p.annee_scolaire, t.nom as nom_trimestre
            FROM classe c 
            JOIN filiere f ON c.code_filiere = f.code_filiere 
            JOIN series s ON f.idserie = s.idserie 
            JOIN promotion p ON c.idpromotion = p.idpromotion 
            JOIN trimestres t ON t.idperiode = ?
            WHERE c.idClasse = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$idperiode, $idClasse]);
    $classe = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les matières avec leurs coefficients pour cette filière
    $sql = "SELECT m.id_matiere, m.nom_matiere, a.coefficient 
            FROM matieres m 
            JOIN associer a ON m.id_matiere = a.id_matiere 
            WHERE a.code_filiere = ? 
            ORDER BY m.nom_matiere";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$classe['code_filiere']]);
    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les élèves de la classe
    $sql = "SELECT e.* 
            FROM eleves e 
            JOIN inscrire i ON e.matricule = i.matricule 
            WHERE i.idClasse = ? 
            ORDER BY e.nom, e.prenom";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$idClasse]);
    $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque élève, récupérer ses notes
    foreach ($eleves as &$eleve) {
        // Récupérer les notes avec note_classe et note_examen
        $sql = "SELECT n.id_matiere, n.note_classe, n.note_examen,
                       (SELECT AVG((n2.note_classe + 2*n2.note_examen)/3)
                        FROM notes n2 
                        WHERE n2.id_matiere = n.id_matiere 
                        AND n2.idperiode = n.idperiode 
                        AND n2.matricule IN (
                            SELECT matricule FROM inscrire WHERE idClasse = ?
                        )) as moyenne_classe
                FROM notes n 
                WHERE n.matricule = ? 
                AND n.idperiode = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$idClasse, $eleve['matricule'], $idperiode]);
        $notes_brutes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser les notes par matière
        $eleve['notes'] = [];
        $totalPoints = 0;
        $totalCoef = 0;

        foreach ($matieres as $matiere) {
            $note_matiere = [
                'coefficient' => $matiere['coefficient'],
                'note_classe' => null,
                'note_examen' => null,
                'moyenne' => null,
                'moyenne_classe' => null
            ];

            foreach ($notes_brutes as $note) {
                if ($note['id_matiere'] == $matiere['id_matiere']) {
                    $note_matiere['note_classe'] = $note['note_classe'];
                    $note_matiere['note_examen'] = $note['note_examen'];
                    // Calcul de la moyenne (note_classe compte pour 1/3, note_examen pour 2/3)
                    if ($note['note_classe'] !== null && $note['note_examen'] !== null) {
                        $note_matiere['moyenne'] = ($note['note_classe'] + 2 * $note['note_examen']) / 3;
                        $totalPoints += $note_matiere['moyenne'] * $matiere['coefficient'];
                        $totalCoef += $matiere['coefficient'];
                    }
                    $note_matiere['moyenne_classe'] = $note['moyenne_classe'];
                    break;
                }
            }
            $eleve['notes'][$matiere['id_matiere']] = $note_matiere;
        }

        // Calculer la moyenne générale
        $eleve['moyenne_generale'] = $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : null;
    }

    // Trier les élèves par moyenne générale décroissante
    usort($eleves, function($a, $b) {
        if (!isset($a['moyenne_generale']) || !isset($b['moyenne_generale'])) {
            return 0;
        }
        return $b['moyenne_generale'] <=> $a['moyenne_generale'];
    });

    // Calculer les rangs
    $rang = 1;
    $precedent = null;
    $rang_precedent = 1;
    foreach ($eleves as &$eleve) {
        if ($precedent !== null && $eleve['moyenne_generale'] == $precedent) {
            $eleve['rang'] = $rang_precedent;
        } else {
            $eleve['rang'] = $rang;
            $rang_precedent = $rang;
        }
        $precedent = $eleve['moyenne_generale'];
        $rang++;
    }

    if ($preview) {
        include 'templates/bulletin_template.php';
    } else {
        // Configuration pour l'impression PDF
        require_once 'vendor/autoload.php';
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15
        ]);

        ob_start();
        foreach ($eleves as $eleve) {
            include 'templates/bulletin_template.php';
        }
        $html = ob_get_clean();
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('bulletins.pdf', 'I');
    }

} catch(PDOException $e) {
    exit('Erreur : ' . $e->getMessage());
}
?> 