<?php
require '../db.php';

$poll_id = $_GET['id'] ?? null;
if (!$poll_id) {
    header('Location: dashboard.php');
    exit;
}

// Fetch poll and options
$stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
$stmt->execute([$poll_id]);
$poll = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$poll) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
$stmt->execute([$poll_id]);
$options = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $new_options = array_filter(array_map('trim', $_POST['options'] ?? []));

    if (!$question) {
        $errors[] = "Poll question is required.";
    }

    if (count($new_options) < 2) {
        $errors[] = "Please provide at least two options.";
    }

    if (!$errors) {
        // Update question
        $stmt = $pdo->prepare("UPDATE polls SET question = ? WHERE id = ?");
        $stmt->execute([$question, $poll_id]);

        // Delete existing options
        $stmt = $pdo->prepare("DELETE FROM options WHERE poll_id = ?");
        $stmt->execute([$poll_id]);

        // Insert new options with zero votes
        $stmt = $pdo->prepare("INSERT INTO options (poll_id, option_text, votes) VALUES (?, ?, 0)");
        foreach ($new_options as $opt) {
            $stmt->execute([$poll_id, $opt]);
        }

        $success = true;

        // Refresh options to show updated list
        $stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
        $stmt->execute([$poll_id]);
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $poll['question'] = $question;
    }
}

ob_start();
?>

<div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-center text-blue-600">Edit Poll</h1>

    <?php if ($success): ?>
        <div class="mb-6 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
            Poll updated successfully! <a href="dashboard.php" class="underline text-blue-600">Back to dashboard</a>.
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="mb-6 p-4 text-red-700 bg-red-100 border border-red-300 rounded">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" id="editPollForm" class="space-y-6">
        <div>
            <label for="question" class="block mb-2 font-semibold text-gray-700">Poll Question</label>
            <input
                type="text"
                id="question"
                name="question"
                value="<?= htmlspecialchars($poll['question']) ?>"
                class="w-full px-4 py-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="What do you want to ask?"
                required
            >
        </div>

        <div>
            <label class="block mb-2 font-semibold text-gray-700">Options</label>
            <div id="optionsContainer" class="space-y-3">
                <?php
                $oldOptions = $_POST['options'] ?? array_map(fn($o) => $o['option_text'], $options);
                $oldOptions = count($oldOptions) < 2 ? ['', ''] : $oldOptions;
                foreach ($oldOptions as $index => $opt):
                ?>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            name="options[]"
                            value="<?= htmlspecialchars($opt) ?>"
                            class="flex-grow px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Option text"
                            required
                        >
                        <button type="button" class="removeOptionBtn text-red-500 hover:text-red-700 font-bold" title="Remove option">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="addOptionBtn" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">+ Add Option</button>
        </div>

        <button
            type="submit"
            class="w-full py-3 bg-green-600 text-white font-semibold rounded hover:bg-green-700 transition"
        >
            Update Poll
        </button>
    </form>
</div>

<script>
// Add/remove option inputs dynamically
document.getElementById('addOptionBtn').addEventListener('click', () => {
    const container = document.getElementById('optionsContainer');
    const optionDiv = document.createElement('div');
    optionDiv.classList.add('flex', 'gap-2');

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'options[]';
    input.placeholder = 'Option text';
    input.required = true;
    input.className = 'flex-grow px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500';

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'removeOptionBtn text-red-500 hover:text-red-700 font-bold';
    removeBtn.title = 'Remove option';
    removeBtn.textContent = '×';
    removeBtn.addEventListener('click', () => optionDiv.remove());

    optionDiv.appendChild(input);
    optionDiv.appendChild(removeBtn);
    container.appendChild(optionDiv);
});

document.querySelectorAll('.removeOptionBtn').forEach(btn => {
    btn.addEventListener('click', e => {
        const container = document.getElementById('optionsContainer');
        if (container.children.length > 2) {
            e.target.parentElement.remove();
        } else {
            alert('A poll needs at least two options.');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
$title = "Edit Poll";
require 'layout.php';
