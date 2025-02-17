<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Gestion Scolaire'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        }
    </style>
    <?php echo $additional_head ?? ''; ?>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-school text-2xl"></i>
                    <h1 class="text-xl font-bold">Gestion Scolaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">admin admin</span>
                    <img src="avatar.png" class="w-8 h-8 rounded-full">
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <?php echo $content; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t mt-8">
        <div class="container mx-auto px-4 py-4">
            <p class="text-center text-gray-600 text-sm">
                © 2024 Gestion Scolaire. Tous droits réservés. Version 1.0
            </p>
        </div>
    </footer>
</body>
</html> 