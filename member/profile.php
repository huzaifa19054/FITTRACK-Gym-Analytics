<?php require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('member');
// Get the logged-in user ID
$uid = (int)$_SESSION['user_id'];
$msg = '';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $ec = trim($_POST['emergency_contact']);
    $ep = trim($_POST['emergency_phone']);
    // Get data from the database
    $q = $conn->prepare('UPDATE users SET full_name=?,phone=? WHERE id=?');
    // Add values to the prepared query
    $q->bind_param('ssi', $name, $phone, $uid);
    $q->execute();
    // Get data from the database
    $q = $conn->prepare('UPDATE members SET address=?,emergency_contact=?,emergency_phone=? WHERE user_id=?');
    // Add values to the prepared query
    $q->bind_param('sssi', $address, $ec, $ep, $uid);
    $q->execute();
    $_SESSION['full_name'] = $name;
    $msg = 'Profile updated.';
}
// Get data from the database
$r = $conn->query("SELECT u.full_name,u.email,u.phone,m.address,m.emergency_contact,m.emergency_phone,m.joined_date,m.height,m.weight FROM users u JOIN members m ON m.user_id=u.id WHERE u.id=$uid LIMIT 1")->fetch_assoc(); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Member Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div><a class="nav " href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav " href="membership.php"><span class="ico">▱</span>My Membership</a><a class="nav " href="attendance.php"><span class="ico">◷</span>My Attendance</a><a class="nav " href="workout.php"><span class="ico">▣</span>My Workout</a><a class="nav " href="diet.php"><span class="ico">◌</span>My Diet</a><a class="nav " href="payments.php"><span class="ico">₨</span>My Payments</a><a class="nav " href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav " href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">MEMBER / ACCOUNT</div>
                    <h1>Profile</h1>
                    <p>Manage your personal information.</p>
                </div>
            </div><?php if ($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?><section class="panel" style="max-width:760px">
                <div class="panel-head">
                    <div><span class="panel-kicker">PERSONAL DETAILS</span>
                        <h2>Edit profile</h2>
                    </div>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
                    <?php echo csrf_field(); ?><label>Full Name<input name="full_name" value="<?php echo htmlspecialchars($r['full_name']); ?>" required></label><label>Email<input value="<?php echo htmlspecialchars($r['email']); ?>" disabled></label><label>Phone<input name="phone" value="<?php echo htmlspecialchars($r['phone'] ?? ''); ?>"></label><label>Address<input name="address" value="<?php echo htmlspecialchars($r['address'] ?? ''); ?>"></label><label>Emergency Contact<input name="emergency_contact" value="<?php echo htmlspecialchars($r['emergency_contact'] ?? ''); ?>"></label><label>Emergency Phone<input name="emergency_phone" value="<?php echo htmlspecialchars($r['emergency_phone'] ?? ''); ?>"></label><label>Joined Date<input value="<?php echo htmlspecialchars($r['joined_date'] ?? ''); ?>" disabled></label><label>Current Weight<input value="<?php echo htmlspecialchars($r['weight'] ?? ''); ?>" disabled></label><button class="btn" type="submit">Save Changes</button></form>
            </section>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
