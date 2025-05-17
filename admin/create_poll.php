<?php require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = $_POST['question'];
    $options = array_filter($_POST['options']);

    $stmt = $pdo->prepare("INSERT INTO polls (question) VALUES (?)");
    $stmt->execute([$question]);
    $poll_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO options (poll_id, option_text) VALUES (?, ?)");
    foreach ($options as $opt) {
        $stmt->execute([$poll_id, $opt]);
    }

    echo "Poll created successfully!";
    exit;
}
?>

<form method="post">
    <input type="text" name="question" placeholder="Poll question" required><br>
    <input type="text" name="options[]" placeholder="Option 1"><br>
    <input type="text" name="options[]" placeholder="Option 2"><br>
    <input type="text" name="options[]" placeholder="Option 3"><br>
    <button type="submit">Create Poll</button>
</form>
