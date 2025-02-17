<?php
require_once 'config.php';
$page_title = "Bulletin de Notes";
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Récupérer la liste des années scolaires
try {
    $sql = "SELECT DISTINCT idpromotion, annee_scolaire 
            FROM promotion 
            ORDER BY annee_scolaire DESC";
    $stmt = $conn->query($sql);
    $annees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des années scolaires";
}
?>

<div class="container mt-4">
    <h1 class="mb-4">Bulletin de Notes</h1>

    <!-- Formulaire de sélection -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="selectionForm" class="row g-3">
                <!-- Sélection de l'année scolaire -->
                <div class="col-md-3">
                    <label for="annee" class="form-label">Année scolaire</label>
                    <select class="form-select" id="annee" name="annee" required>
                        <option value="">Sélectionner une année</option>
                        <?php foreach ($annees as $annee): ?>
                            <option value="<?php echo $annee['idpromotion']; ?>">
                                <?php echo htmlspecialchars($annee['annee_scolaire']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sélection de la filière -->
                <div class="col-md-3">
                    <label for="filiere" class="form-label">Filière</label>
                    <select class="form-select" id="filiere" name="filiere" required disabled>
                        <option value="">Sélectionner une filière</option>
                    </select>
                </div>

                <!-- Sélection de la classe -->
                <div class="col-md-3">
                    <label for="classe" class="form-label">Classe</label>
                    <select class="form-select" id="classe" name="classe" required disabled>
                        <option value="">Sélectionner une classe</option>
                    </select>
                </div>

                <!-- Sélection de la période -->
                <div class="col-md-3">
                    <label for="periode" class="form-label">Période</label>
                    <select class="form-select" id="periode" name="periode" required disabled>
                        <option value="">Sélectionner une période</option>
                    </select>
                </div>

                <!-- Sélection de l'élève -->
                <div class="col-md-12">
                    <label for="eleve" class="form-label">Élève</label>
                    <select class="form-select" id="eleve" name="eleve" required disabled>
                        <option value="">Sélectionner un élève</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulletin -->
    <div id="bulletinContainer"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const anneeSelect = document.getElementById('annee');
    const filiereSelect = document.getElementById('filiere');
    const classeSelect = document.getElementById('classe');
    const periodeSelect = document.getElementById('periode');
    const eleveSelect = document.getElementById('eleve');
    const bulletinContainer = document.getElementById('bulletinContainer');

    // Fonction pour afficher les alertes
    function showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.card'));
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    // Charger les filières
    function loadFilieres(anneeId) {
        const formData = new FormData();
        formData.append('action', 'get_filieres_by_annee');
        formData.append('annee_id', anneeId);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                filiereSelect.innerHTML = '<option value="">Sélectionner une filière</option>';
                data.data.forEach(filiere => {
                    filiereSelect.innerHTML += `
                        <option value="${filiere.code_filiere}">
                            ${filiere.nom_filiere}
                        </option>`;
                });
                filiereSelect.disabled = false;
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des filières', 'danger');
        });
    }

    // Charger les classes
    function loadClasses(anneeId, codeFiliere) {
        const formData = new FormData();
        formData.append('action', 'get_classes_by_filiere');
        formData.append('annee_id', anneeId);
        formData.append('code_filiere', codeFiliere);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                classeSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
                data.data.forEach(classe => {
                    classeSelect.innerHTML += `
                        <option value="${classe.idClasse}">
                            ${classe.nom_classe}
                        </option>`;
                });
                classeSelect.disabled = false;
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des classes', 'danger');
        });
    }

    // Charger les périodes
    function loadPeriodes(anneeId) {
        const formData = new FormData();
        formData.append('action', 'get_periodes');
        formData.append('annee_id', anneeId);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                periodeSelect.innerHTML = '<option value="">Sélectionner une période</option>';
                data.data.forEach(periode => {
                    periodeSelect.innerHTML += `
                        <option value="${periode.idperiode}">
                            ${periode.trimestre}
                        </option>`;
                });
                periodeSelect.disabled = false;
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des périodes', 'danger');
        });
    }

    // Charger les élèves
    function loadEleves(classeId) {
        const formData = new FormData();
        formData.append('action', 'get_eleves_classe');
        formData.append('classe_id', classeId);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                eleveSelect.innerHTML = '<option value="">Sélectionner un élève</option>';
                data.data.forEach(eleve => {
                    eleveSelect.innerHTML += `
                        <option value="${eleve.matricule}">
                            ${eleve.nom} ${eleve.prenom}
                        </option>`;
                });
                eleveSelect.disabled = false;
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des élèves', 'danger');
        });
    }

    // Générer le bulletin
    function generateBulletin(matricule, codeFiliere, periodeId) {
        const formData = new FormData();
        formData.append('action', 'get_matieres_filiere_bulletin');
        formData.append('matricule', matricule);
        formData.append('code_filiere', codeFiliere);
        formData.append('periode_id', periodeId);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let totalPoints = 0;
                let totalCoefficients = 0;
                
                let html = `
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Bulletin de Notes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Matière</th>
                                            <th>Coefficient</th>
                                            <th>Note de classe</th>
                                            <th>Note d'examen</th>
                                            <th>Moyenne</th>
                                            <th>Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                
                data.data.forEach(matiere => {
                    const moyenne = matiere.moyenne || 0;
                    const points = moyenne * matiere.coefficient;
                    totalPoints += points;
                    totalCoefficients += parseFloat(matiere.coefficient);
                    
                    html += `
                        <tr>
                            <td>${matiere.nom_matiere}</td>
                            <td>${matiere.coefficient}</td>
                            <td>${matiere.note_classe || '-'}</td>
                            <td>${matiere.note_examen || '-'}</td>
                            <td>${moyenne.toFixed(2)}</td>
                            <td>${points.toFixed(2)}</td>
                        </tr>`;
                });
                
                const moyenneGenerale = totalPoints / totalCoefficients;
                
                html += `
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="5">Total des coefficients</th>
                                            <td>${totalCoefficients}</td>
                                        </tr>
                                        <tr>
                                            <th colspan="5">Total des points</th>
                                            <td>${totalPoints.toFixed(2)}</td>
                                        </tr>
                                        <tr>
                                            <th colspan="5">Moyenne générale</th>
                                            <td>${moyenneGenerale.toFixed(2)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>`;
                
                bulletinContainer.innerHTML = html;
            }
        })
        .catch(error => {
            showAlert('Erreur lors de la génération du bulletin', 'danger');
        });
    }

    // Événements de changement
    anneeSelect.addEventListener('change', function() {
        if (this.value) {
            loadFilieres(this.value);
            loadPeriodes(this.value);
            classeSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
            classeSelect.disabled = true;
            periodeSelect.innerHTML = '<option value="">Sélectionner une période</option>';
            periodeSelect.disabled = true;
            eleveSelect.innerHTML = '<option value="">Sélectionner un élève</option>';
            eleveSelect.disabled = true;
            bulletinContainer.innerHTML = '';
        }
    });

    filiereSelect.addEventListener('change', function() {
        if (this.value) {
            loadClasses(anneeSelect.value, this.value);
            eleveSelect.innerHTML = '<option value="">Sélectionner un élève</option>';
            eleveSelect.disabled = true;
            bulletinContainer.innerHTML = '';
        }
    });

    classeSelect.addEventListener('change', function() {
        if (this.value) {
            loadEleves(this.value);
            bulletinContainer.innerHTML = '';
        }
    });

    eleveSelect.addEventListener('change', function() {
        if (this.value && periodeSelect.value) {
            generateBulletin(this.value, filiereSelect.value, periodeSelect.value);
        }
    });

    periodeSelect.addEventListener('change', function() {
        if (this.value && eleveSelect.value) {
            generateBulletin(eleveSelect.value, filiereSelect.value, this.value);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
