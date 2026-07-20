<?php
session_start();

// --- LOGIN CREDENTIALS ---
$valid_username = 'admin';
$valid_password = 'admin123';

// --- DATABASE CONFIGURATION ---
$host   = 'sql300.infinityfree.com';
$dbname = 'if0_42040399_loan_db';
$user   = 'if0_42040399';
$pass   = 'divineongod5';

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

// --- STATUS UPDATE (APPROVE / REJECT) ---
if (isset($_GET['action'], $_GET['id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE loans SET status = 'Approved' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE loans SET status = 'Rejected' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'delete') {
        // Get file names before deleting
        $stmt = $pdo->prepare("SELECT passport_file, idcard_file FROM loans WHERE id = ?");
        $stmt->execute([$id]);
        $files = $stmt->fetch();
        // Delete uploaded files
        if ($files) {
            if (file_exists('uploads/' . $files['passport_file'])) {
                unlink('uploads/' . $files['passport_file']);
            }
            if (file_exists('uploads/' . $files['idcard_file'])) {
                unlink('uploads/' . $files['idcard_file']);
            }
        }
        // Delete record
        $stmt = $pdo->prepare("DELETE FROM loans WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: admin.php');
    exit;
}

// --- LOGIN CHECK ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - CF Loan</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Inter', -apple-system, sans-serif;
                background: linear-gradient(135deg, #1a1a6c, #0d0d4a);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-box {
                background: white;
                border-radius: 16px;
                padding: 30px 24px;
                width: 100%;
                max-width: 360px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .login-box h2 {
                font-size: 20px;
                font-weight: 700;
                color: #1a1a2e;
                margin-bottom: 6px;
                text-align: center;
            }
            .login-box .subtitle {
                font-size: 13px;
                color: #666680;
                text-align: center;
                margin-bottom: 24px;
            }
            .input-group { margin-bottom: 16px; }
            .input-group label {
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: #1a1a2e;
                margin-bottom: 6px;
            }
            .input-group input {
                width: 100%;
                height: 48px;
                padding: 0 14px;
                border: 2px solid #e2e6f0;
                border-radius: 10px;
                font-size: 15px;
                font-family: inherit;
            }
            .input-group input:focus {
                outline: none;
                border-color: #1a1a6c;
            }
            .password-wrapper {
                position: relative;
            }
            .password-wrapper input {
                padding-right: 48px;
            }
            .toggle-password {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #9ca3af;
                padding: 4px;
                line-height: 1;
            }
            .toggle-password:active {
                color: #1a1a6c;
            }
            .error {
                background: #fef2f2;
                color: #dc2626;
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 13px;
                margin-bottom: 16px;
            }
            .btn-login {
                width: 100%;
                height: 48px;
                background: #1a1a6c;
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 15px;
                font-weight: 600;
                font-family: inherit;
                cursor: pointer;
            }
            .btn-login:active {
                background: #15155a;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Admin Login</h2>
            <p class="subtitle">Cooperate Friends Loan System</p>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter username" required autofocus>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="loginPassword" placeholder="Enter password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('loginPassword', this)">&#128065;</button>
                    </div>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>
        <script>
            function togglePassword(inputId, btn) {
                var input = document.getElementById(inputId);
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.innerHTML = '&#128064;';
                } else {
                    input.type = 'password';
                    btn.innerHTML = '&#128065;';
                }
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// --- COUNT STATS ---
$total    = $pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();
$pending  = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'Pending'")->fetchColumn();
$approved = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'Approved'")->fetchColumn();
$rejected = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'Rejected'")->fetchColumn();

// --- SEARCH FUNCTIONALITY ---
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// --- FETCH LOANS ---
if ($search !== '') {
    $search_param = '%' . $search . '%';
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE fname LIKE ? OR lname LIKE ? OR phone LIKE ? ORDER BY created_at DESC");
    $stmt->execute([$search_param, $search_param, $search_param]);
    $loans = $stmt->fetchAll();
} else {
    $stmt  = $pdo->query("SELECT * FROM loans ORDER BY created_at DESC");
    $loans = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CF Loan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f5f7fb;
            color: #1a1a2e;
            min-height: 100vh;
            padding-bottom: 40px;
        }
        .topbar {
            background: #1a1a6c;
            padding: 0 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-inner {
            max-width: 700px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
        }
        .topbar-logo {
            color: white;
            font-weight: 700;
            font-size: 16px;
        }
        .topbar-actions a {
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-left: 12px;
            padding: 6px 12px;
            border-radius: 6px;
        }
        .btn-logout {
            background: rgba(239,68,68,0.2);
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px 16px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 14px 10px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
        }
        .stat-label {
            font-size: 11px;
            color: #666680;
            margin-top: 4px;
        }
        .stat-total .stat-number { color: #1a1a6c; }
        .stat-pending .stat-number { color: #f59e0b; }
        .stat-approved .stat-number { color: #10b981; }
        .stat-rejected .stat-number { color: #ef4444; }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 10px;
            border: 2px solid #e2e6f0;
            padding: 0 12px;
            height: 40px;
            width: 100%;
            max-width: 250px;
        }
        .search-box input {
            flex: 1;
            border: none;
            padding: 8px 0;
            font-size: 13px;
            font-family: inherit;
            outline: none;
        }
        .search-box input::placeholder {
            color: #b0b8c8;
        }
        .search-icon {
            color: #9ca3af;
            font-size: 16px;
            margin-right: 8px;
        }
        .clear-search {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            display: none;
        }
        .clear-search.show {
            display: block;
        }
        .loan-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .card-name {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a6c;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .card-info {
            font-size: 13px;
            color: #4a4a6a;
            margin-bottom: 4px;
        }
        .card-info strong { color: #1a1a2e; }
        .card-divider {
            border: none;
            border-top: 1px solid #e2e6f0;
            margin: 12px 0;
        }
        .subsection-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 4px;
        }
        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        .btn-action {
            flex: 1;
            min-width: 80px;
            padding: 12px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-approve { background: #10b981; }
        .btn-reject { background: #ef4444; }
        .btn-delete { background: #6b7280; }
        .btn-done {
            flex: 1;
            min-width: 80px;
            padding: 12px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            background: #f3f4f6;
            color: #9ca3af;
        }
        .card-time {
            font-size: 11px;
            color: #b0b8c8;
            margin-top: 10px;
        }
        .file-links a {
            display: inline-block;
            font-size: 13px;
            color: #1a1a6c;
            text-decoration: none;
            font-weight: 600;
            background: #f0f1ff;
            padding: 4px 10px;
            border-radius: 6px;
            margin-right: 8px;
        }
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: #666680;
        }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
        @media (max-width: 400px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .card-actions { flex-direction: column; }
            .btn-action, .btn-done { min-width: 100%; }
            .section-header { flex-direction: column; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <span class="topbar-logo">&#128203; Loan Applications</span>
        <div class="topbar-actions">
            <a href="admin.php">&#128260; Refresh</a>
            <a href="?logout" class="btn-logout">&#128682; Logout</a>
        </div>
    </div>
</div>

<div class="container">

    <div class="stats">
        <div class="stat-card stat-total">
            <div class="stat-number"><?php echo $total; ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-number"><?php echo $pending; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card stat-approved">
            <div class="stat-number"><?php echo $approved; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card stat-rejected">
            <div class="stat-number"><?php echo $rejected; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>

    <div class="section-header">
        <h2 class="section-title">All Applications</h2>
        <form method="get" class="search-box">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="search" placeholder="Search by name or phone..." value="<?php echo htmlspecialchars($search); ?>">
            <?php if ($search !== ''): ?>
                <a href="admin.php" class="clear-search show">&#10005;</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($loans)): ?>
        <div class="empty">
            <div class="empty-icon">&#128232;</div>
            <p><?php echo $search !== '' ? 'No results found.' : 'No loan applications yet.'; ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($loans as $loan): ?>
            <div class="loan-card">
                <div class="card-top">
                    <span class="card-name"><?php echo htmlspecialchars($loan['fname'] . ' ' . $loan['lname']); ?></span>
                    <span class="badge badge-<?php echo strtolower($loan['status']); ?>"><?php echo $loan['status']; ?></span>
                </div>

                <p class="card-info"><strong>&#128222; Phone:</strong> <?php echo htmlspecialchars($loan['phone']); ?></p>
                <p class="card-info"><strong>&#128197; DOB:</strong> <?php echo htmlspecialchars($loan['dob']); ?></p>
                <p class="card-info"><strong>&#128205; Address:</strong> <?php echo htmlspecialchars($loan['contact_address']); ?></p>
                <p class="card-info"><strong>&#127968; Village:</strong> <?php echo htmlspecialchars($loan['village']); ?></p>
                <p class="card-info"><strong>&#127891; School:</strong> <?php echo htmlspecialchars($loan['school']); ?></p>

                <hr class="card-divider">

                <div class="subsection-title">&#128106; Parents</div>
                <p class="card-info"><strong>Father:</strong> <?php echo htmlspecialchars($loan['father_name']); ?> - <?php echo htmlspecialchars($loan['father_address']); ?></p>
                <p class="card-info"><strong>Mother:</strong> <?php echo htmlspecialchars($loan['mother_name']); ?> - <?php echo htmlspecialchars($loan['mother_address']); ?></p>

                <div class="subsection-title" style="margin-top:8px;">&#129309; Guarantor & Witness</div>
                <p class="card-info"><strong>Guarantor:</strong> <?php echo htmlspecialchars($loan['guarantor_name']); ?> (<?php echo htmlspecialchars($loan['guarantor_phone']); ?>)</p>
                <p class="card-info"><strong>Witness:</strong> <?php echo htmlspecialchars($loan['witness_name']); ?> (<?php echo htmlspecialchars($loan['witness_phone']); ?>)</p>

                <div class="subsection-title" style="margin-top:8px;">&#128206; Documents</div>
                <div class="file-links">
                    <a href="uploads/<?php echo htmlspecialchars($loan['passport_file']); ?>" target="_blank">&#127917; Passport</a>
                    <a href="uploads/<?php echo htmlspecialchars($loan['idcard_file']); ?>" target="_blank">&#128194; ID Card</a>
                </div>

                <div class="card-actions">
                    <?php if ($loan['status'] === 'Pending'): ?>
                        <a href="?action=approve&id=<?php echo $loan['id']; ?>" class="btn-action btn-approve" onclick="return confirm('Approve this application?')">&#10004; Approve</a>
                        <a href="?action=reject&id=<?php echo $loan['id']; ?>" class="btn-action btn-reject" onclick="return confirm('Reject this application?')">&#10006; Reject</a>
                        <a href="?action=delete&id=<?php echo $loan['id']; ?>" class="btn-action btn-delete" onclick="return confirm('DELETE this application permanently? This cannot be undone!')">&#128465; Delete</a>
                    <?php elseif ($loan['status'] === 'Approved'): ?>
                        <span class="btn-done">&#10004; Approved</span>
                        <a href="?action=delete&id=<?php echo $loan['id']; ?>" class="btn-action btn-delete" onclick="return confirm('DELETE this application permanently? This cannot be undone!')">&#128465; Delete</a>
                    <?php else: ?>
                        <span class="btn-done">&#10006; Rejected</span>
                        <a href="?action=delete&id=<?php echo $loan['id']; ?>" class="btn-action btn-delete" onclick="return confirm('DELETE this application permanently? This cannot be undone!')">&#128465; Delete</a>
                    <?php endif; ?>
                </div>

                <p class="card-time">&#128338; Submitted: <?php echo htmlspecialchars($loan['created_at']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

</body>
</html>
