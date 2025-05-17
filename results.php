<?php
require 'db.php';

$poll_id = $_GET['id'] ?? null;
if (!$poll_id) {
  die("Poll ID missing.");
}

// Fetch poll
$pollStmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
$pollStmt->execute([$poll_id]);
$poll = $pollStmt->fetch();

if (!$poll) {
  die("Poll not found.");
}

// Fetch options
$optionsStmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
$optionsStmt->execute([$poll_id]);
$options = $optionsStmt->fetchAll();

$totalVotes = array_sum(array_column($options, 'votes'));
if ($totalVotes === 0) $totalVotes = 1; // avoid division by zero

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Poll Results</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

  <div class="max-w-xl w-full bg-white rounded-xl shadow-xl p-8">
    <h1 class="text-3xl font-extrabold text-indigo-700 mb-8 text-center tracking-wide drop-shadow-md">
      <?= htmlspecialchars($poll['question']) ?>
    </h1>

    <div class="space-y-5">
      <?php foreach ($options as $option): 
        $voteCount = (int)$option['votes'];
        $percent = round(($voteCount / $totalVotes) * 100);
      ?>
        <div>
          <div class="flex justify-between mb-1 font-semibold text-gray-700">
            <span><?= htmlspecialchars($option['option_text']) ?></span>
            <span><?= $voteCount ?> vote<?= $voteCount !== 1 ? 's' : '' ?> (<?= $percent ?>%)</span>
          </div>
          <div class="w-full bg-gray-300 rounded-full h-6">
            <div class="bg-indigo-600 h-6 rounded-full transition-all duration-500" 
                 style="width: <?= $percent ?>%;" 
                 aria-label="<?= $percent ?> percent votes"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-8">
      <a href="index.php" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition duration-200">
        Back to Voting
      </a>
    </div>
  </div>

</body>
</html>
