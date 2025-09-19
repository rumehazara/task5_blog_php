<?php
session_start();
include 'db.php';

// Check login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Search
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM posts WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC LIMIT ?, ?");
    $searchTerm = "%$search%";
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM posts WHERE title LIKE ? OR content LIKE ?");
    $countStmt->bind_param("ss", $searchTerm, $searchTerm);
    $countStmt->execute();
    $totalPosts = $countStmt->get_result()->fetch_assoc()['total'];
} else {
    $stmt = $conn->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT ?, ?");
    $stmt->bind_param("ii", $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM posts");
    $countStmt->execute();
    $totalPosts = $countStmt->get_result()->fetch_assoc()['total'];
}

$totalPages = ceil($totalPosts / $limit);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blog Posts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">My Blog</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'editor'): ?>
          <li class="nav-item"><a class="nav-link" href="add_post.php">Add Post</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <h2 class="mb-4">Welcome, <?php echo $_SESSION['username']; ?> 👋</h2>

    <!-- Search -->
    <form method="GET" action="index.php" class="mb-3 d-flex" style="max-width:400px;">
        <input type="text" name="search" class="form-control me-2" placeholder="Search posts..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Posts Table -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Content</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo substr(htmlspecialchars($row['content']), 0, 50) . "..."; ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'editor'): ?>
                        <a href="edit_post.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="delete_post.php?id=<?php echo $row['id']; ?>" 
                           class="btn btn-danger btn-sm delete-btn">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav>
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo $search; ?>">Previous</a></li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo $search; ?>">Next</a></li>
        <?php endif; ?>
      </ul>
    </nav>

</div>

<!-- SweetAlert Delete -->
<script>
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let link = this.getAttribute('href');

        Swal.fire({
            title: 'Are you sure?',
            text: "This post will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link;
            }
        });
    });
});
</script>

</body>
</html>
