<?php
require_once 'config.php';
$page_title = "Inscription des Élèves";
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
    <h1 class="mb-4">Inscription des Élèves</h1>

    <!-- Formulaire de sélection -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="selectionForm" class="row g-3">
                <!-- Sélection de l'année scolaire -->
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label for="filiere" class="form-label">Filière</label>
                    <select class="form-select" id="filiere" name="filiere" required disabled>
                        <option value="">Sélectionner une filière</option>
                    </select>
                </div>

                <!-- Sélection de la classe -->
                <div class="col-md-4">
                    <label for="classe" class="form-label">Classe</label>
                    <select class="form-select" id="classe" name="classe" required disabled>
                        <option value="">Sélectionner une classe</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des élèves à inscrire -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Élèves disponibles pour l'inscription</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="elevesTable">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const anneeSelect = document.getElementById('annee');
    const filiereSelect = document.getElementById('filiere');
    const classeSelect = document.getElementById('classe');
    const elevesTableBody = document.querySelector('#elevesTable tbody');

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

    // Charger les filières pour une année
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
                classeSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
                classeSelect.disabled = true;
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des filières', 'danger');
        });
    }

    // Charger les classes pour une filière
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

    // Charger les élèves non inscrits
    function loadEleves(classeId) {
        const formData = new FormData();
        formData.append('action', 'get_eleves_non_inscrits');
        formData.append('classe_id', classeId);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                elevesTableBody.innerHTML = '';
                data.data.forEach(eleve => {
                    elevesTableBody.innerHTML += `
                        <tr>
                            <td>${eleve.matricule}</td>
                            <td>${eleve.nom}</td>
                            <td>${eleve.prenom}</td>
                            <td>
                                <button class="btn btn-primary btn-sm inscrire-btn" 
                                        data-matricule="${eleve.matricule}">
                                    Inscrire
                                </button>
                            </td>
                        </tr>`;
                });

                // Ajouter les écouteurs d'événements pour les boutons d'inscription
                document.querySelectorAll('.inscrire-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        inscrireEleve(this.dataset.matricule, classeSelect.value);
                    });
                });
            }
        })
        .catch(error => {
            showAlert('Erreur lors du chargement des élèves', 'danger');
        });
    }

    // Inscrire un élève
    function inscrireEleve(matricule, classeId) {
        const formData = new FormData();
        formData.append('action', 'inscrire_eleve');
        formData.append('matricule', matricule);
        formData.append('classe_id', classeId);

        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message);
                // Recharger la liste des élèves
                loadEleves(classeId);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('Erreur lors de l\'inscription', 'danger');
        });
    }

    // Événements de changement
    anneeSelect.addEventListener('change', function() {
        if (this.value) {
            loadFilieres(this.value);
        }
    });

    filiereSelect.addEventListener('change', function() {
        if (this.value) {
            loadClasses(anneeSelect.value, this.value);
        }
    });

    classeSelect.addEventListener('change', function() {
        if (this.value) {
            loadEleves(this.value);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
