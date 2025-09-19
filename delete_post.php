<?php
session_start();
include 'db.php';

// Only admin can delete
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Prepared statement for safety
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php?msg=Post+deleted+successfully");
        exit();
    } else {
        header("Location: index.php?error=Unable+to+delete+post");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
