<?php
// show errors for debugging — remove or set to 0 in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// === DB CONFIG - change only if your XAMPP uses different credentials ===
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'blog';

// connect
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// initialize variables
$error = "";
$success = "";

// handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    // server-side validation
    if ($username === '' || $password === '' || $role === '') {
        $error = "All fields are required.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!in_array($role, ['user', 'editor', 'admin'])) {
        $error = "Invalid role selected.";
    } else {
        // check if username already exists (prepared statement)
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if ($check === false) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = "Username already exists. Please choose another.";
            } else {
                // hash password and insert user
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ins = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                if ($ins === false) {
                    $error = "Prepare failed (insert): " . $conn->error;
                } else {
                    $ins->bind_param("sss", $username, $hashed, $role);
                    if ($ins->execute()) {
                        // success
                        $success = "Registered successfully. Redirecting to login...";
                        // redirect after short pause so the user sees the message
                        header("refresh:1;url=login.php?msg=Registered+successfully");
                        // close statements
                        $ins->close();
                        $check->close();
                        $conn->close();
                        // output the page (will redirect)
                    } else {
                        $error = "Insert failed: " . $ins->error;
                    }
                }
            }
            if ($check) $check->close();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .form-box {
      max-width:400px;
      margin:60px auto;
      padding:20px;
      border-radius:10px;
      background:#fff;
      box-shadow:0 4px 12px rgba(0,0,0,0.08);
    }
    body{ background:#f5f7fb; }
  </style>
</head>
<body>
  <div class="form-box">
    <h3 class="text-center mb-3">Register</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
          <option value="user" <?php if(isset($_POST['role']) && $_POST['role']=='user') echo 'selected'; ?>>User</option>
          <option value="editor" <?php if(isset($_POST['role']) && $_POST['role']=='editor') echo 'selected'; ?>>Editor</option>
          <option value="admin" <?php if(isset($_POST['role']) && $_POST['role']=='admin') echo 'selected'; ?>>Admin</option>
        </select>
      </div>

      <button type="submit" class="btn btn-success w-100">Register</button>
      <div class="text-center mt-2">
        <a href="login.php">Already have an account? Login</a>
      </div>
    </form>
  </div>
</body>
</html>
