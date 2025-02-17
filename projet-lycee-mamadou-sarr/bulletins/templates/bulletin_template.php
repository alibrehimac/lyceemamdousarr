<div class="bulletin" style="page-break-after: always;">
    <div class="header">
        <div class="row">
            <div class="col-3 text-start">
                <img src="assets/img/logo.jpg" alt="Logo du lycée" style="max-width: 100px; margin-right: 20px;">
            </div>
            <div class="col-9 text-center">
                <h4 class="mb-0">RÉPUBLIQUE DE CÔTE D'IVOIRE</h4>
                <p class="mb-0">Union - Discipline - Travail</p>
                <h4 class="mb-2">MINISTÈRE DE L'ÉDUCATION NATIONALE</h4>
                <h4 class="mb-0">LYCÉE MODERNE ABOBO</h4>
                <p class="mb-0">BP V 123 Abidjan - Côte d'Ivoire</p>
                <p class="mb-3">Tel: (+225) 27 XX XX XX XX</p>
            </div>
        </div>
        <div class="text-center">
            <h2 class="mb-3">BULLETIN DE NOTES</h2>
            <h3><?php echo $classe['nom_trimestre'] . ' - ' . $classe['annee_scolaire']; ?></h3>
            <h4><?php echo $classe['nom_classe'] . ' ' . $classe['nom_filiere'] . ' (' . $classe['series'] . ')'; ?></h4>
        </div>
    </div>

    <div class="student-info">
        <p><strong>Élève :</strong> <?php echo $eleve['nom'] . ' ' . $eleve['prenom']; ?></p>
        <p><strong>Matricule :</strong> <?php echo $eleve['matricule']; ?></p>
        <p><strong>Né(e) le :</strong> <?php echo date('d/m/Y', strtotime($eleve['date_naiss'])); ?> 
           <strong>à</strong> <?php echo $eleve['lieu_naiss']; ?></p>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th rowspan="2">Matières</th>
                <th rowspan="2">Coef</th>
                <th colspan="2">Notes</th>
                <th rowspan="2">Moyenne</th>
                <th rowspan="2">Moy. Classe</th>
                <th rowspan="2">Appréciation</th>
            </tr>
            <tr>
                <th>Classe</th>
                <th>Examen</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_points = 0;
            $total_coef = 0;
            foreach ($matieres as $matiere): 
                $note = $eleve['notes'][$matiere['id_matiere']];
            ?>
                <tr>
                    <td><?php echo $matiere['nom_matiere']; ?></td>
                    <td class="text-center"><?php echo $matiere['coefficient']; ?></td>
                    <td class="text-center"><?php echo $note['note_classe'] ? number_format($note['note_classe'], 2) : '-'; ?></td>
                    <td class="text-center"><?php echo $note['note_examen'] ? number_format($note['note_examen'], 2) : '-'; ?></td>
                    <td class="text-center"><?php echo $note['moyenne'] ? number_format($note['moyenne'], 2) : '-'; ?></td>
                    <td class="text-center"><?php echo $note['moyenne_classe'] ? number_format($note['moyenne_classe'], 2) : '-'; ?></td>
                    <td>
                        <?php
                        if ($note['moyenne']) {
                            if ($note['moyenne'] >= 17) echo "Excellent";
                            elseif ($note['moyenne'] >= 15) echo "Très Bien";
                            elseif ($note['moyenne'] >= 13) echo "Bien";
                            elseif ($note['moyenne'] >= 11) echo "Assez Bien";
                            elseif ($note['moyenne'] >= 10) echo "Passable";
                            else echo "Insuffisant";
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Moyenne Générale</th>
                <th class="text-center"><?php echo number_format($eleve['moyenne_generale'], 2); ?></th>
                <th colspan="2">Rang : <?php echo $eleve['rang']; ?> sur <?php echo count($eleves); ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="observations mt-4">
        <h5>Observations du conseil de classe :</h5>
        <?php
        $moyenne = $eleve['moyenne_generale'];
        if ($moyenne >= 14) {
            echo "Excellent trimestre, félicitations !";
        } elseif ($moyenne >= 12) {
            echo "Bon trimestre, continuez ainsi.";
        } elseif ($moyenne >= 10) {
            echo "Trimestre satisfaisant, des efforts à poursuivre.";
        } else {
            echo "Résultats insuffisants, un sérieux effort s'impose.";
        }
        ?>
    </div>

    <div class="signatures mt-4">
        <div class="row text-center">
            <div class="col-4">
                <p>Le Professeur Principal</p>
                <p class="mt-4">________________</p>
            </div>
            <div class="col-4">
                <p>Le Proviseur</p>
                <p class="mt-4">M. Amadou HAIDARA</p>
            </div>
            <div class="col-4">
                <p>Les Parents</p>
                <p class="mt-4">________________</p>
            </div>
        </div>
    </div>
</div> 