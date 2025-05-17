<?php
require '../db.php';

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = $search ? "WHERE question LIKE :search" : "";
$params = $search ? [':search' => '%' . $search . '%'] : [];

// Count total results
$stmt = $pdo->prepare("SELECT COUNT(*) FROM polls $search_sql");
$stmt->execute($params);
$total_polls = $stmt->fetchColumn();
$total_pages = ceil($total_polls / $limit);

// Fetch paginated, filtered polls
$sql = "SELECT * FROM polls $search_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-semibold text-gray-800">Poll Dashboard</h1>
    <a href="create_poll.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Create Poll</a>
</div>

<!-- Search bar -->
<form method="GET" class="mb-6 flex gap-3">
    <input type="text" name="search" placeholder="Search polls..." value="<?= htmlspecialchars($search) ?>"
        class="px-4 py-2 border border-gray-300 rounded w-full">
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">Search</button>
</form>

<?php if (count($polls) === 0): ?>
    <p class="text-gray-600">No polls found.</p>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($polls as $poll):
            $options = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
            $options->execute([$poll['id']]);
            $options_data = $options->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="bg-white shadow rounded-lg p-5 flex flex-col md:flex-row md:justify-between md:items-center">
                <!-- Improved Left Side -->
                <div class="flex-1 text-lg leading-relaxed">
                    <h2 class="text-2xl font-extrabold mb-2 text-gray-900"><?= htmlspecialchars($poll['question']) ?></h2>
                    <p class="text-sm text-gray-500 italic mb-4">Created on <?= date('F j, Y, g:i a', strtotime($poll['created_at'])) ?></p>

                    <ul class="list-disc list-inside text-gray-700 mb-5 max-w-lg space-y-1">
                        <?php foreach ($options_data as $opt): ?>
                            <li class="font-medium"><?= htmlspecialchars($opt['option_text']) ?> <span class="text-indigo-600">(Votes: <?= $opt['votes'] ?>)</span></li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="flex flex-wrap items-center gap-5 mt-3">
                        <a href="edit_poll.php?id=<?= $poll['id'] ?>" class="text-blue-600 font-semibold hover:underline">Edit</a>
                        <button onclick="confirmDelete(<?= $poll['id'] ?>)" class="text-red-600 font-semibold hover:underline">Delete</button>
                        <a href="../vote.php?id=<?= $poll['id'] ?>" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition">
                            Vote
                        </a>
                    </div>
                </div>

                <!-- Right Side (Progress Bars) unchanged -->
                <div class="w-full md:w-96 md:ml-12 mt-6 md:mt-0 bg-white p-4 rounded-lg shadow">
                    <h3 class="font-semibold text-gray-800 mb-4">Results</h3>
                    <div class="space-y-5">
                        <?php
                        $totalVotes = array_sum(array_column($options_data, 'votes')) ?: 1;
                        foreach ($options_data as $opt):
                            $percent = round(($opt['votes'] / $totalVotes) * 100);
                        ?>
                            <div>
                                <div class="flex justify-between mb-1 font-semibold text-gray-700">
                                    <span><?= htmlspecialchars($opt['option_text']) ?></span>
                                    <span><?= $opt['votes'] ?> votes (<?= $percent ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-300 rounded-full h-6">
                                    <div class="bg-indigo-600 h-6 rounded-full transition-all duration-500" style="width: <?= $percent ?>%;" aria-label="<?= $percent ?> percent votes"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination controls -->
    <div class="mt-6 flex justify-center gap-2">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
               class="px-3 py-1 rounded <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirm Deletion</h2>
        <p class="mb-6 text-gray-600">Are you sure you want to delete this poll? This action cannot be undone.</p>
        <div class="flex justify-end gap-4">
            <button onclick="hideModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
            <a id="confirmDeleteBtn" href="#" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</a>
        </div>
    </div>
</div>

<!-- JS logic -->
<script>
function confirmDelete(pollId) {
    document.getElementById("confirmDeleteBtn").href = "delete_poll.php?id=" + pollId;
    document.getElementById("deleteModal").classList.remove("hidden");
    document.getElementById("deleteModal").classList.add("flex");
}
function hideModal() {
    document.getElementById("deleteModal").classList.add("hidden");
}
</script>

<?php
$content = ob_get_clean();
$title = "Dashboard";
require 'layout.php';
?>
