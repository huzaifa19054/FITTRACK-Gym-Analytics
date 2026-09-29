<?php
// Load the file needed by this page
require_once __DIR__ . '/config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}
// Get the logged-in user ID
$uid = (int)$_SESSION['user_id'];
$msg = '';
$err = '';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    // Get data from the database
    $q = $conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
    // Add values to the prepared query
    $q->bind_param('i', $uid);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    if (!$row || !password_verify($current, $row['password'])) $err = 'Current password is incorrect.';
    elseif (strlen($new) < 6) $err = 'New password must be at least 6 characters.';
    elseif ($new !== $confirm) $err = 'New passwords do not match.';
    elseif (password_verify($new, $row['password'])) $err = 'New password must be different from the current password.';
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        // Get data from the database
        $u = $conn->prepare('UPDATE users SET password=? WHERE id=?');
        $u->bind_param('si', $hash, $uid);
        $u->execute();
        $msg = 'Password changed successfully.';
    }
}
$role = $_SESSION['role'];
$back = $role === 'admin' ? 'admin/dashboard.php' : ($role === 'trainer' ? 'trainer/profile.php' : 'member/profile.php');
$name = htmlspecialchars($_SESSION['full_name'] ?? 'User');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Change Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="<?php echo $back; ?>"><span class="ico">←</span>Back to Portal</a><a class="nav active" href="change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="index.php"><span class="ico">↗</span>Website</a><a class="nav" href="logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">FITTRACK / SECURITY</div>
                    <h1>Change Password</h1>
                    <p>Update the password for <?php echo $name; ?>.</p>
                </div>
            </div><?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?><?php if ($err): ?><div class="alert error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?><section class="panel" style="max-width:720px">
                <div class="panel-head">
                    <div><span class="panel-kicker">ACCOUNT SECURITY</span>
                        <h2>Set a new password</h2>
                    </div>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
                    <?php echo csrf_field(); ?><label>Current Password<input type="password" name="current_password" autocomplete="current-password" required></label><label>New Password<input type="password" name="new_password" minlength="6" autocomplete="new-password" required></label><label>Confirm New Password<input type="password" name="confirm_password" minlength="6" autocomplete="new-password" required></label>
// Run the database query.
                    <div><button class="btn" type="submit">Update Password</button> <a class="btn secondary" href="<?php echo $back; ?>">Cancel</a></div>
                </form>
            </section>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="assets/js/admin.js"></script>
</body>

</html>
