<?php
$required_files = [
    'assets/css/bootstrap.min.css',
    'assets/js/bootstrap.bundle.min.js',
    'assets/fonts/fontawesome/css/all.min.css',
    'assets/css/style.css'
];

$missing_files = [];
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $missing_files[] = $file;
    }
}

if (!empty($missing_files)) {
    echo "<h2>Fichiers manquants :</h2>";
    echo "<ul>";
    foreach ($missing_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    echo "<p>Veuillez exécuter le script download_dependencies.php pour télécharger les fichiers manquants.</p>";
    exit;
}

echo "<h2 style='color: green;'>✓ Toutes les dépendances sont présentes.</h2>";
?> 