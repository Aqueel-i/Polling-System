<?php
require 'db.php';

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = $search ? "WHERE question LIKE :search" : "";
$params = $search ? [':search' => '%' . $search . '%'] : [];

// Count total polls
$stmt = $pdo->prepare("SELECT COUNT(*) FROM polls $search_sql");
$stmt->execute($params);
$total_polls = $stmt->fetchColumn();
$total_pages = ceil($total_polls / $limit);

// Fetch polls
$stmt = $pdo->prepare("SELECT * FROM polls $search_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Polls Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6 flex flex-col items-center">

  <div class="max-w-4xl w-full bg-white rounded-xl shadow-xl p-8">
    <h1 class="text-4xl font-extrabold text-indigo-700 mb-8 text-center tracking-wide drop-shadow-md">
      Poll Dashboard
    </h1>

    <!-- Search Bar -->
    <form method="GET" class="mb-8 flex gap-2">
      <input type="text" name="search" placeholder="Search polls..."
        value="<?= htmlspecialchars($search) ?>"
        class="flex-grow px-4 py-2 border border-gray-300 rounded focus:outline-indigo-500" />
      <button type="submit"
        class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
        Search
      </button>
    </form>

    <?php if (count($polls) === 0): ?>
      <p class="text-center text-gray-600">No polls found.</p>
    <?php else: ?>
      <div class="space-y-6">
        <?php foreach ($polls as $poll):
          // Count total votes for poll options
          $optionsStmt = $pdo->prepare("SELECT SUM(votes) FROM options WHERE poll_id = ?");
          $optionsStmt->execute([$poll['id']]);
          $totalVotes = $optionsStmt->fetchColumn() ?: 0;
        ?>
          <div class="border rounded-lg p-4 shadow-sm hover:shadow-md transition flex flex-col md:flex-row md:justify-between md:items-center">
            <div>
              <h2 class="text-xl font-semibold text-gray-800 mb-2"><?= htmlspecialchars($poll['question']) ?></h2>
              <p class="text-sm text-gray-500">Created: <?= date('F j, Y', strtotime($poll['created_at'])) ?></p>
              <p class="text-sm text-gray-600 mt-1">Total Votes: <?= $totalVotes ?></p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
              <a href="results.php?id=<?= $poll['id'] ?>"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                View Results
              </a>
              <a href="vote.php?id=<?= $poll['id'] ?>"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                Vote
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <div class="mt-8 flex justify-center gap-2">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
            class="px-4 py-2 rounded <?= $i == $page ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  </div>

</body>
</html>
