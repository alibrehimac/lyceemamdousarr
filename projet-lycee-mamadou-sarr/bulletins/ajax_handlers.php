<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => null];

if (!isset($_POST['action'])) {
    echo json_encode(['error' => 'Action non spécifiée']);
    exit();
}

try {
    switch ($_POST['action']) {
        case 'get_classes':
            if (!empty($_POST['annee_id'])) {
                $sql = "SELECT c.idClasse, c.nom_classe, f.nom_filiere, f.code_filiere 
                        FROM classe c 
                        JOIN filiere f ON c.code_filiere = f.code_filiere 
                        WHERE c.idpromotion = ?
                        ORDER BY c.nom_classe";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_POST['annee_id']]);
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            }
            break;

        case 'get_matieres_filiere':
            if (!empty($_POST['code_filiere'])) {
                $sql = "SELECT m.id_matiere, m.nom_matiere, a.coefficient 
                        FROM matieres m 
                        INNER JOIN associer a ON m.id_matiere = a.id_matiere 
                        WHERE a.code_filiere = ? 
                        ORDER BY m.nom_matiere";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_POST['code_filiere']]);
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            }
            break;

        case 'get_periodes':
            if (!empty($_POST['annee_id'])) {
                $sql = "SELECT * FROM trimestres WHERE idpromotion = ? ORDER BY idperiode";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_POST['annee_id']]);
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            }
            break;

        case 'get_eleves_notes':
            if (!empty($_POST['classe_id']) && !empty($_POST['matiere_id']) && !empty($_POST['periode_id'])) {
                $sql = "SELECT e.*, n.note_classe, n.note_examen, i.idpromotion
                        FROM eleves e
                        JOIN inscrire i ON e.matricule = i.matricule
                        LEFT JOIN notes n ON e.matricule = n.matricule 
                            AND n.id_matiere = ? AND n.idperiode = ?
                        WHERE i.idClasse = ?
                        ORDER BY e.nom, e.prenom";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_POST['matiere_id'], $_POST['periode_id'], $_POST['classe_id']]);
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            }
            break;

        case 'save_notes':
            if (!empty($_POST['notes']) && !empty($_POST['classe_id']) && 
                !empty($_POST['matiere_id']) && !empty($_POST['periode_id'])) {
                
                $conn->beginTransaction();
                
                // Récupérer l'idpromotion de la classe
                $stmt = $conn->prepare("SELECT idpromotion FROM classe WHERE idClasse = ?");
                $stmt->execute([$_POST['classe_id']]);
                $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
                $idpromotion = $promotion['idpromotion'];
                
                $notes = json_decode($_POST['notes'], true);
                foreach ($notes as $matricule => $note_data) {
                    $note_classe = !empty($note_data['classe']) ? $note_data['classe'] : null;
                    $note_examen = !empty($note_data['examen']) ? $note_data['examen'] : null;

                    // Vérifier si une note existe déjà
                    $stmt = $conn->prepare("SELECT id_note FROM notes 
                                          WHERE matricule = ? AND id_matiere = ? AND idperiode = ?");
                    $stmt->execute([$matricule, $_POST['matiere_id'], $_POST['periode_id']]);
                    $existing_note = $stmt->fetch();

                    if ($existing_note) {
                        $sql = "UPDATE notes SET note_classe = ?, note_examen = ? 
                                WHERE matricule = ? AND id_matiere = ? AND idperiode = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$note_classe, $note_examen, $matricule, 
                                      $_POST['matiere_id'], $_POST['periode_id']]);
                    } else {
                        $sql = "INSERT INTO notes (matricule, id_matiere, idperiode, idpromotion, 
                                                 note_classe, note_examen) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$matricule, $_POST['matiere_id'], $_POST['periode_id'], 
                                      $idpromotion, $note_classe, $note_examen]);
                    }
                }
                
                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Les notes ont été enregistrées avec succès";
            }
            break;

        case 'get_coefficient':
            if (!empty($_POST['code_filiere']) && !empty($_POST['id_matiere'])) {
                $sql = "SELECT coefficient 
                        FROM associer 
                        WHERE code_filiere = ? AND id_matiere = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_POST['code_filiere'], $_POST['id_matiere']]);
                $response['data'] = $stmt->fetch(PDO::FETCH_ASSOC);
                $response['success'] = true;
            }
            break;

        case 'add_filiere_to_serie':
            if (!empty($_POST['idserie']) && !empty($_POST['code_filiere']) && !empty($_POST['nom_filiere'])) {
                try {
                    // Vérifier si la filière existe déjà
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM filiere WHERE code_filiere = ?");
                    $stmt->execute([$_POST['code_filiere']]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $response['success'] = false;
                        $response['message'] = "Ce code de filière existe déjà";
                    } else {
                        // Ajouter la nouvelle filière
                        $stmt = $conn->prepare("INSERT INTO filiere (code_filiere, nom_filiere, idserie) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $_POST['code_filiere'],
                            $_POST['nom_filiere'],
                            $_POST['idserie']
                        ]);
                        
                        $response['success'] = true;
                        $response['message'] = "Filière ajoutée avec succès";
                    }
                } catch(PDOException $e) {
                    $response['success'] = false;
                    $response['message'] = "Erreur lors de l'ajout de la filière : " . $e->getMessage();
                }
            } else {
                $response['success'] = false;
                $response['message'] = "Tous les champs sont requis";
            }
            break;

        case 'get_series':
            try {
                $stmt = $conn->query("SELECT * FROM series ORDER BY series");
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            } catch(PDOException $e) {
                $response['success'] = false;
                $response['message'] = "Erreur lors de la récupération des séries : " . $e->getMessage();
            }
            break;

        case 'get_filieres_by_serie':
            try {
                $sql = "SELECT f.*, s.series 
                        FROM filiere f 
                        JOIN series s ON f.idserie = s.idserie";
                
                if (!empty($_POST['idserie'])) {
                    $sql .= " WHERE f.idserie = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$_POST['idserie']]);
                } else {
                    $stmt = $conn->query($sql);
                }
                
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            } catch(PDOException $e) {
                $response['success'] = false;
                $response['message'] = "Erreur lors de la récupération des filières : " . $e->getMessage();
            }
            break;

        case 'get_filieres_by_annee':
            if (!empty($_POST['annee_id'])) {
                $sql = "SELECT DISTINCT f.* 
                        FROM filiere f 
                        JOIN classe c ON f.code_filiere = c.code_filiere 
                        WHERE c.idpromotion = ?
                        ORDER BY f.nom_filiere";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_POST['annee_id']]);
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            }
            break;

        case 'get_classes_by_filiere':
            if (!empty($_POST['annee_id']) && !empty($_POST['code_filiere'])) {
                $sql = "SELECT c.* 
                        FROM classe c 
                        WHERE c.idpromotion = ? AND c.code_filiere = ?
                        ORDER BY c.nom_classe";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_POST['annee_id'], $_POST['code_filiere']]);
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            }
            break;

        case 'get_eleves_non_inscrits':
            if (!empty($_POST['classe_id'])) {
                // Récupérer d'abord l'année scolaire de la classe
                $stmt = $conn->prepare("SELECT idpromotion FROM classe WHERE idClasse = ?");
                $stmt->execute([$_POST['classe_id']]);
                $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($promotion) {
                    // Récupérer les élèves non inscrits pour cette année
                    $sql = "SELECT e.* 
                            FROM eleves e 
                            WHERE e.matricule NOT IN (
                                SELECT i.matricule 
                                FROM inscrire i 
                                JOIN classe c ON i.idClasse = c.idClasse 
                                WHERE c.idpromotion = ?
                            )
                            ORDER BY e.nom, e.prenom";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$promotion['idpromotion']]);
                    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $response['success'] = true;
                }
            }
            break;

        case 'inscrire_eleve':
            if (!empty($_POST['matricule']) && !empty($_POST['classe_id'])) {
                // Vérifier si l'élève n'est pas déjà inscrit dans une classe pour cette année
                $stmt = $conn->prepare("
                    SELECT i.* 
                    FROM inscrire i 
                    JOIN classe c ON i.idClasse = c.idClasse 
                    WHERE i.matricule = ? AND c.idpromotion = (
                        SELECT idpromotion FROM classe WHERE idClasse = ?
                    )
                ");
                $stmt->execute([$_POST['matricule'], $_POST['classe_id']]);
                
                if ($stmt->fetch()) {
                    $response['message'] = "Cet élève est déjà inscrit dans une classe pour cette année scolaire";
                } else {
                    // Récupérer la filière de la classe
                    $stmt = $conn->prepare("
                        SELECT code_filiere, idpromotion 
                        FROM classe 
                        WHERE idClasse = ?
                    ");
                    $stmt->execute([$_POST['classe_id']]);
                    $classe = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Inscrire l'élève
                    $sql = "INSERT INTO inscrire (matricule, idClasse, code_filiere) 
                            VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        $_POST['matricule'],
                        $_POST['classe_id'],
                        $classe['code_filiere']
                    ]);
                    
                    $response['success'] = true;
                    $response['message'] = "Élève inscrit avec succès";
                }
            }
            break;

        case 'get_matieres_filiere_bulletin':
            if (!empty($_POST['code_filiere']) && !empty($_POST['periode_id']) && !empty($_POST['matricule'])) {
                $sql = "SELECT DISTINCT m.id_matiere, m.nom_matiere, a.coefficient,
                        n.note_classe, n.note_examen,
                        ((n.note_classe + n.note_examen) / 2) as moyenne
                        FROM matieres m 
                        INNER JOIN associer a ON m.id_matiere = a.id_matiere 
                        LEFT JOIN notes n ON m.id_matiere = n.id_matiere 
                            AND n.matricule = ? AND n.idperiode = ?
                        WHERE a.code_filiere = ? 
                        ORDER BY m.nom_matiere";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $_POST['matricule'],
                    $_POST['periode_id'],
                    $_POST['code_filiere']
                ]);
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
            }
            break;
    }
} catch(PDOException $e) {
    $response['message'] = "Une erreur est survenue : " . $e->getMessage();
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
}

echo json_encode($response);
