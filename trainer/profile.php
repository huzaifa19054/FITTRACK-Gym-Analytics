<?php require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_role('trainer');
$uid = (int)$_SESSION['user_id'];
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $spec = trim($_POST['specialization']);
    $exp = (int)$_POST['experience_years'];
// Run the database query.
    $q = $conn->prepare('UPDATE users SET full_name=?,phone=? WHERE id=?');
    $q->bind_param('ssi', $name, $phone, $uid);
    $q->execute();
// Run the database query.
    $q = $conn->prepare('UPDATE trainers SET specialization=?,experience_years=? WHERE user_id=?');
    $q->bind_param('sii', $spec, $exp, $uid);
    $q->execute();
    $_SESSION['full_name'] = $name;
    $msg = 'Profile updated.';
}
// Run the database query.
$r = $conn->query("SELECT u.full_name,u.email,u.phone,t.specialization,t.experience_years,t.joining_date FROM users u JOIN trainers t ON t.user_id=u.id WHERE u.id=$uid LIMIT 1")->fetch_assoc(); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Trainer Profile</title>
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
            <div class="nav-label">MAIN</div><a class="nav " href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav " href="members.php"><span class="ico">◎</span>My Members</a><a class="nav " href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav " href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav " href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav " href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav " href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">TRAINER / ACCOUNT</div>
                    <h1>Profile</h1>
                    <p>Keep your trainer information up to date.</p>
                </div>
            </div><?php if ($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?><section class="panel" style="max-width:760px">
                <div class="panel-head">
                    <div><span class="panel-kicker">ACCOUNT DETAILS</span>
                        <h2>Edit profile</h2>
                    </div>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
                    <?php echo csrf_field(); ?><label>Full Name<input name="full_name" value="<?php echo htmlspecialchars($r['full_name']); ?>" required></label><label>Email<input value="<?php echo htmlspecialchars($r['email']); ?>" disabled></label><label>Phone<input name="phone" value="<?php echo htmlspecialchars($r['phone'] ?? ''); ?>"></label><label>Specialization<input name="specialization" value="<?php echo htmlspecialchars($r['specialization'] ?? ''); ?>"></label><label>Experience Years<input type="number" name="experience_years" value="<?php echo (int)$r['experience_years']; ?>" min="0"></label><label>Joining Date<input value="<?php echo htmlspecialchars($r['joining_date'] ?? ''); ?>" disabled></label><button class="btn" type="submit">Save Changes</button></form>
            </section>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
