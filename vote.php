<?php
require 'db.php';
session_start();

$poll_id = $_GET['id'] ?? null;
if (!$poll_id) {
    header('Location: index.php');
    exit;
}

// Fetch poll
$stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
$stmt->execute([$poll_id]);
$poll = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$poll) {
    echo "<div class='text-center mt-20 text-red-600 font-bold'>Poll not found.</div>";
    exit;
}

// Fetch options
$stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
$stmt->execute([$poll_id]);
$options = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user has voted (using session)
$voted_poll_ids = $_SESSION['voted_polls'] ?? [];
$has_voted = in_array($poll_id, $voted_poll_ids);

$success = false;
$selected_option_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_voted) {
    $selected_option_id = $_POST['option'] ?? null;
    $valid_option = false;
    foreach ($options as $opt) {
        if ($opt['id'] == $selected_option_id) {
            $valid_option = true;
            break;
        }
    }

    if ($valid_option) {
        $stmt = $pdo->prepare("UPDATE options SET votes = votes + 1 WHERE id = ?");
        $stmt->execute([$selected_option_id]);

        $voted_poll_ids[] = $poll_id;
        $_SESSION['voted_polls'] = $voted_poll_ids;
        $has_voted = true;
        $success = true;

        // Refresh options with updated votes
        $stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
        $stmt->execute([$poll_id]);
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

ob_start();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Vote: <?= htmlspecialchars($poll['question']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

<div class="max-w-xl w-full bg-white rounded-xl shadow-xl p-8">
    <h1 class="text-3xl font-extrabold text-indigo-700 mb-8 text-center tracking-wide drop-shadow-md">
        <?= htmlspecialchars($poll['question']) ?>
    </h1>

    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-100 text-green-800 rounded text-center font-semibold">
            Thank you for voting! Your vote has been recorded.
        </div>

        <div class="space-y-5">
            <?php
            $totalVotes = array_sum(array_column($options, 'votes')) ?: 1;
            foreach ($options as $opt):
                $percentage = round(($opt['votes'] / $totalVotes) * 100, 1);
            ?>
                <div>
                    <div class="flex justify-between mb-1 font-semibold text-gray-700">
                        <span><?= htmlspecialchars($opt['option_text']) ?></span>
                        <span><?= $opt['votes'] ?> votes (<?= $percentage ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-300 rounded-full h-6">
                        <div
                            class="bg-indigo-600 h-6 rounded-full transition-all duration-500"
                            style="width: <?= $percentage ?>%;"
                            aria-label="<?= $percentage ?> percent votes"
                        ></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($has_voted): ?>
        <div class="mb-6 p-4 bg-yellow-100 text-yellow-800 rounded text-center font-semibold">
            You have already voted in this poll.
        </div>

        <a href="results.php?id=<?= $poll_id ?>" class="block text-center mt-4 text-indigo-700 font-semibold hover:underline">
            View Poll Results
        </a>

    <?php else: ?>
        <form id="voteForm" method="POST" class="space-y-6">
            <?php foreach ($options as $opt): ?>
                <label
                    for="option-<?= $opt['id'] ?>"
                    class="block cursor-pointer rounded-lg border border-gray-300 p-4 hover:bg-indigo-50 flex items-center gap-4 transition"
                >
                    <input
                        required
                        type="radio"
                        name="option"
                        id="option-<?= $opt['id'] ?>"
                        value="<?= $opt['id'] ?>"
                        class="form-radio text-indigo-600"
                    />
                    <span class="text-lg font-medium text-gray-900"><?= htmlspecialchars($opt['option_text']) ?></span>
                </label>
            <?php endforeach; ?>

            <button
                type="submit"
                class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition-transform transform hover:scale-105 font-semibold shadow-lg focus:outline-none focus:ring-4 focus:ring-indigo-400"
            >
                Submit Vote
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 max-w-sm w-full shadow-lg">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Confirm Vote</h2>
        <p class="mb-6 text-gray-600">Are you sure you want to submit your vote?</p>
        <div class="flex justify-end gap-4">
            <button
                onclick="hideModal()"
                class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 focus:outline-none"
            >
                Cancel
            </button>
            <button
                id="confirmBtn"
                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 focus:outline-none"
            >
                Yes, Vote
            </button>
        </div>
    </div>
</div>

<script>
const form = document.getElementById('voteForm');
const modal = document.getElementById('confirmModal');
const confirmBtn = document.getElementById('confirmBtn');

if (form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        modal.classList.remove('hidden');
    });

    confirmBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        form.submit();
    });
}

function hideModal() {
    modal.classList.add('hidden');
}
</script>

</body>
</html>

<?php
echo ob_get_clean();
?>
