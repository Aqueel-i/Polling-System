<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title ?? 'My App') ?></title>
    <link rel="icon" href="/path/to/poll.png" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Optional: Customize Tailwind config here if needed -->
</head>
<body class="bg-gray-50 min-h-screen font-sans text-gray-900">

    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <a href="dashboard.php" class="text-6xl font-bold text-blue-600 hover:text-blue-700">Polling App</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10">
        <?= $content ?>
    </main>

    <footer class="mt-20 mb-8 text-center text-gray-500 text-sm">
        &copy; <?= date('Y') ?>Polling App. All rights reserved.
    </footer>

</body>
</html>
