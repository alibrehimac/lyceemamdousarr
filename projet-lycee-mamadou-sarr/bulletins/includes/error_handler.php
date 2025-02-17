<?php
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    
    // Log l'erreur
    error_log(json_encode($error));
    
    // En mode développement, afficher l'erreur
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>Erreur</h4>";
        echo "<p>Message: {$errstr}</p>";
        echo "<p>Fichier: {$errfile}</p>";
        echo "<p>Ligne: {$errline}</p>";
        echo "</div>";
    } else {
        // En production, afficher un message générique
        echo "<div class='alert alert-danger'>Une erreur est survenue. Veuillez réessayer plus tard.</div>";
    }
    
    return true;
}

set_error_handler('customErrorHandler'); 