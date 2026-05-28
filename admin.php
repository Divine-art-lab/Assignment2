<?php
// ==============================================
// admin.php – Loan admin panel (mobile friendly)
// ==============================================
session_start();

// --- HARDCODED LOGIN CREDENTIALS (change these!) ---
$valid_username = 'admin';
$valid_password = 'admin123'; // plain text for demo

// --- DATABASE CONFIGURATION (SAME AS IN submit.php) ---
$host   = 'localhost';
$dbname = 'your_database';
$user   = 'your_username';
$pass   = 'your_password';

// Connect to DB
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// --- HANDLE STATUS UPDATE (APPROVE/REJECT) ---
if (isset($_GET['action'], $_GET['id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];

    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
        $stmt = $pdo->prepare("UPDATE loans SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $id]);
    }
    // Redirect to avoid refresh re‑applying action
    header('Location: admin.php');
    exit;
}

// --- LOGIN CHECK ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Show login form
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        if ($_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
            $_SESSION['logged_in'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }

    // Display simple login page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <style>
            body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
            .login-box { max-width: 400px; margin: 100px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            input { width: 100%; padding: 10px; margin: 10px 0; border:1px solid #ccc; border-radius:5px; }
            input[type="submit"] { background: navy; color: #fff; font-weight: bold; cursor: pointer; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Admin Login</h2>
            <?php if ($error) echo "<p class='error'>$error</p>"; ?>
            <form method="post">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="submit" value="Log In">
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- ADMIN DASHBOARD (LOGGED IN) ---
// Fetch all loan applications
$stmt = $pdo->query("SELECT * FROM loans ORDER BY created_at DESC");
$loans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Applications</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f8f8f8;
            margin: 0;
            padding: 0;
        }
        .header {
            background: navy;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .header a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            margin-left: 10px;
        }
        .container {
            padding: 15px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin: 0 0 5px 0;
            color: navy;
        }
        .card p {
            margin: 4px 0;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            margin: 8px 0;
        }
        .status-Pending { background: #f0ad4e; }
        .status-Approved { background: #5cb85c; }
        .status-Rejected { background: #d9534f; }
        .actions {
            margin-top: 12px;
        }
        .actions a {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            margin-right: 8px;
            font-size: 14px;
        }
        .approve { background: #5cb85c; }
        .reject  { background: #d9534f; }
        .file-link {
            color: navy;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>📋 Loan Applications</h2>
        <nav>
            <a href="admin.php">Refresh</a>
            <a href="?logout">Logout</a>
        </nav>
    </div>

    <div class="container">
        <?php if (empty($loans)): ?>
            <p>No applications yet.</p>
        <?php else: ?>
            <?php foreach ($loans as $loan): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($loan['fname'] . ' ' . $loan['lname']) ?></h3>
                    <p><strong>Date of Birth:</strong> <?= htmlspecialchars($loan['dob']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($loan['phone']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($loan['contact_address']) ?></p>
                    <p><strong>Village:</strong> <?= htmlspecialchars($loan['village']) ?></p>
                    <p><strong>School:</strong> <?= htmlspecialchars($loan['school']) ?></p>
                    <hr>
                    <p><strong>Father:</strong> <?= htmlspecialchars($loan['father_name']) ?> (<?= htmlspecialchars($loan['father_address']) ?>)</p>
                    <p><strong>Mother:</strong> <?= htmlspecialchars($loan['mother_name']) ?> (<?= htmlspecialchars($loan['mother_address']) ?>)</p>
                    <hr>
                    <p><strong>Guarantor:</strong> <?= htmlspecialchars($loan['guarantor_name']) ?>, <?= htmlspecialchars($loan['guarantor_phone']) ?><br>
                    <?= htmlspecialchars($loan['guarantor_address']) ?></p>
                    <p><strong>Witness:</strong> <?= htmlspecialchars($loan['witness_name']) ?>, <?= htmlspecialchars($loan['witness_phone']) ?></p>
                    <hr>
                    <p>
                        <strong>Passport:</strong> <a class="file-link" href="uploads/<?= htmlspecialchars($loan['passport_file']) ?>" target="_blank">View</a><br>
                        <strong>ID Card:</strong> <a class="file-link" href="uploads/<?= htmlspecialchars($loan['idcard_file']) ?>" target="_blank">View</a>
                    </p>
                    <span class="status-badge status-<?= htmlspecialchars($loan['status']) ?>">
                        <?= htmlspecialchars($loan['status']) ?>
                    </span>
                    <div class="actions">
                        <?php if ($loan['status'] === 'Pending'): ?>
                            <a class="approve" href="?action=approve&id=<?= $loan['id'] ?>">✅ Approve</a>
                            <a class="reject" href="?action=reject&id=<?= $loan['id'] ?>">❌ Reject</a>
                        <?php else: ?>
                            <small>Status already set to <?= htmlspecialchars($loan['status']) ?></small>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:12px; color:#666;">Submitted: <?= htmlspecialchars($loan['created_at']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>