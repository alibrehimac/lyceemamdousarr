<?php
require_once 'config.php';
$page_title = "Gestion des Notes";
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

<div class="container">
    <h1 class="mb-4">Gestion des Notes</h1>

    <div class="card mb-4">
        <div class="card-body">
            <form id="noteSelectionForm" class="row g-3">
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

                <!-- Sélection de la matière -->
                <div class="col-md-3">
                    <label for="matiere" class="form-label">Matière</label>
                    <select class="form-select" id="matiere" name="matiere" required disabled>
                        <option value="">Sélectionner une matière</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Zone d'affichage des notes -->
    <div id="notesContainer"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('noteSelectionForm');
    const anneeSelect = document.getElementById('annee');
    const classeSelect = document.getElementById('classe');
    const periodeSelect = document.getElementById('periode');
    const matiereSelect = document.getElementById('matiere');
    const notesContainer = document.getElementById('notesContainer');

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

    // Fonction pour charger les classes
    function loadClasses(anneeId) {
        const formData = new FormData();
        formData.append('action', 'get_classes');
        formData.append('annee_id', anneeId);

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
                        <option value="${classe.idClasse}" 
                                data-filiere="${classe.code_filiere}">
                            ${classe.nom_classe} ${classe.nom_filiere}
                        </option>`;
                });
                classeSelect.disabled = false;
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des classes', 'danger');
        });
    }

    // Fonction pour charger les périodes
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

    // Fonction pour charger les matières
    function loadMatieres(codeFiliere) {
        const formData = new FormData();
        formData.append('action', 'get_matieres_filiere');
        formData.append('code_filiere', codeFiliere);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                matiereSelect.innerHTML = '<option value="">Sélectionner une matière</option>';
                data.data.forEach(matiere => {
                    matiereSelect.innerHTML += `
                        <option value="${matiere.id_matiere}" 
                                data-coefficient="${matiere.coefficient}">
                            ${matiere.nom_matiere} (Coef. ${matiere.coefficient})
                        </option>`;
                });
                matiereSelect.disabled = false;
            } else {
                showAlert('Aucune matière associée à cette filière', 'warning');
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des matières', 'danger');
        });
    }

    // Fonction pour charger les notes
    function loadNotes() {
        const formData = new FormData();
        formData.append('action', 'get_eleves_notes');
        formData.append('classe_id', classeSelect.value);
        formData.append('matiere_id', matiereSelect.value);
        formData.append('periode_id', periodeSelect.value);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const coefficient = matiereSelect.options[matiereSelect.selectedIndex].dataset.coefficient;
                let html = `
                    <div class="card">
                        <div class="card-body">
                            <form id="notesForm">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Matricule</th>
                                                <th>Nom</th>
                                                <th>Prénom</th>
                                                <th>Note de classe</th>
                                                <th>Note d'examen</th>
                                                <th>Moyenne</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                
                data.data.forEach(eleve => {
                    const noteClasse = eleve.note_classe || '';
                    const noteExamen = eleve.note_examen || '';
                    const moyenne = calculateMoyenne(noteClasse, noteExamen);
                    
                    html += `
                        <tr>
                            <td>${eleve.matricule}</td>
                            <td>${eleve.nom}</td>
                            <td>${eleve.prenom}</td>
                            <td>
                                <input type="number" 
                                       class="form-control note-input" 
                                       name="notes[${eleve.matricule}][classe]" 
                                       value="${noteClasse}"
                                       min="0" 
                                       max="20" 
                                       step="0.25"
                                       onchange="validateNote(this)"
                                       oninput="this.value = validateNoteInput(this.value)">
                            </td>
                            <td>
                                <input type="number" 
                                       class="form-control note-input" 
                                       name="notes[${eleve.matricule}][examen]" 
                                       value="${noteExamen}"
                                       min="0" 
                                       max="20" 
                                       step="0.25"
                                       onchange="validateNote(this)"
                                       oninput="this.value = validateNoteInput(this.value)">
                            </td>
                            <td class="moyenne">${moyenne}</td>
                        </tr>`;
                });
                
                html += `
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        Enregistrer les notes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>`;
                
                notesContainer.innerHTML = html;
                
                // Modifier l'écouteur d'événements pour le calcul de la moyenne
                document.querySelectorAll('.note-input').forEach(input => {
                    input.addEventListener('change', function() {
                        const tr = this.closest('tr');
                        const noteClasse = tr.querySelector('input[name$="[classe]"]').value;
                        const noteExamen = tr.querySelector('input[name$="[examen]"]').value;
                        const moyenne = calculateMoyenne(noteClasse, noteExamen);
                        tr.querySelector('.moyenne').textContent = moyenne;
                    });
                });
                
                // Gérer la soumission du formulaire de notes
                document.getElementById('notesForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData();
                    formData.append('action', 'save_notes');
                    formData.append('classe_id', classeSelect.value);
                    formData.append('matiere_id', matiereSelect.value);
                    formData.append('periode_id', periodeSelect.value);
                    
                    const notes = {};
                    this.querySelectorAll('tr').forEach(tr => {
                        const inputs = tr.querySelectorAll('input');
                        if (inputs.length) {
                            const matricule = tr.querySelector('td').textContent;
                            notes[matricule] = {
                                classe: inputs[0].value,
                                examen: inputs[1].value
                            };
                        }
                    });
                    formData.append('notes', JSON.stringify(notes));

                    fetch('ajax_handlers.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(data.message);
                        } else {
                            showAlert(data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        showAlert('Erreur lors de l\'enregistrement des notes', 'danger');
                    });
                });
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des notes', 'danger');
        });
    }

    // Événements de changement
    anneeSelect.addEventListener('change', function() {
        if (this.value) {
            loadClasses(this.value);
            loadPeriodes(this.value);
            classeSelect.value = '';
            periodeSelect.value = '';
            matiereSelect.value = '';
            matiereSelect.disabled = true;
            notesContainer.innerHTML = '';
        }
    });

    classeSelect.addEventListener('change', function() {
        if (this.value) {
            const codeFiliere = this.options[this.selectedIndex].dataset.filiere;
            loadMatieres(codeFiliere);
            matiereSelect.value = '';
            notesContainer.innerHTML = '';
        }
    });

    // Charger les notes quand tous les champs sont sélectionnés
    const checkAndLoadNotes = () => {
        if (classeSelect.value && matiereSelect.value && periodeSelect.value) {
            loadNotes();
        }
    };

    periodeSelect.addEventListener('change', checkAndLoadNotes);
    matiereSelect.addEventListener('change', checkAndLoadNotes);
});

// Remplacer la fonction validateNoteInput existante ou l'ajouter si elle n'existe pas
function validateNoteInput(value) {
    // Empêcher les valeurs négatives et supérieures à 20
    if (value < 0) return 0;
    if (value > 20) return 20;
    return value;
}

// Ajouter la fonction de calcul de la moyenne
function calculateMoyenne(noteClasse, noteExamen) {
    if (!noteExamen) return '';
    
    noteExamen = parseFloat(noteExamen);
    
    if (!noteClasse) {
        return noteExamen.toFixed(2);
    }
    
    noteClasse = parseFloat(noteClasse);
    return ((noteClasse + (noteExamen * 2)) / 3).toFixed(2);
}
</script>

<?php require_once 'includes/footer.php'; ?>