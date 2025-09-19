<?php
session_start();
include 'db.php';

// Restrict access: only admin/editor
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'editor'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

// Fetch post securely
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    // Validation
    if (empty($title) || empty($content)) {
        $error = "Both title and content are required!";
    } else {
        // Update with prepared statement
        $update = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        $update->bind_param("ssi", $title, $content, $id);

        if ($update->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5" style="max-width:700px;">

    <h2 class="mb-4">Edit Post</h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-warning">Update Post</button>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </form>

</body>
</html>
