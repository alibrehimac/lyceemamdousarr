<?php
// URLs des fichiers à télécharger
$dependencies = [
    'bootstrap_css' => [
        'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
        'path' => 'assets/css/bootstrap.min.css'
    ],
    'bootstrap_js' => [
        'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
        'path' => 'assets/js/bootstrap.bundle.min.js'
    ],
    'fontawesome_css' => [
        'url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
        'path' => 'assets/fonts/fontawesome/css/all.min.css'
    ]
];

// Créer les dossiers nécessaires
$directories = [
    'assets/css',
    'assets/js',
    'assets/fonts/fontawesome/css'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Télécharger les fichiers
foreach ($dependencies as $name => $file) {
    if (!file_exists($file['path'])) {
        $content = file_get_contents($file['url']);
        if ($content !== false) {
            file_put_contents($file['path'], $content);
            echo "Téléchargement réussi : " . $file['path'] . "<br>";
        } else {
            echo "Erreur lors du téléchargement : " . $file['path'] . "<br>";
        }
    } else {
        echo "Le fichier existe déjà : " . $file['path'] . "<br>";
    }
}

echo "<br>Processus terminé. Vérifiez que tous les fichiers ont été téléchargés correctement.";
?> 