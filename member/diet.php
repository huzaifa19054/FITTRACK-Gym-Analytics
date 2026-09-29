<?php require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('member');
// Get the logged-in user ID
$uid = (int)$_SESSION['user_id'];
// Get data from the database
$m = $conn->query("SELECT id FROM members WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$mid = (int)($m['id'] ?? 0);
// Get data from the database
$rows = $conn->query("SELECT md.*,d.name,d.goal,d.calories,d.description FROM member_diets md JOIN diet_plans d ON d.id=md.diet_plan_id WHERE md.member_id=$mid ORDER BY md.id DESC"); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — My Diet</title>
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
            <div class="nav-label">MAIN</div><a class="nav " href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav " href="membership.php"><span class="ico">▱</span>My Membership</a><a class="nav " href="attendance.php"><span class="ico">◷</span>My Attendance</a><a class="nav " href="workout.php"><span class="ico">▣</span>My Workout</a><a class="nav active" href="diet.php"><span class="ico">◌</span>My Diet</a><a class="nav " href="payments.php"><span class="ico">₨</span>My Payments</a><a class="nav " href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav " href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">MEMBER / DIET</div>
                    <h1>My Diet Plan</h1>
                    <p>View nutrition plans assigned by your trainer.</p>
                </div>
            </div>
    <!-- Main page section. -->
            <section class="panel">
                <div class="panel-head">
                    <div><span class="panel-kicker">NUTRITION</span>
                        <h2>Assigned diet plans</h2>
                    </div>
                </div>
                <div class="member-list"><?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?><div class="member-row"><span class="member-avatar">◌</span>
                                <div><strong><?php echo htmlspecialchars($r['name']); ?></strong><small><?php echo htmlspecialchars($r['goal'] ?: 'Nutrition goal'); ?> · <?php echo $r['calories'] ? (int)$r['calories'] . ' kcal/day' : ''; ?> · Assigned <?php echo date('d M Y', strtotime($r['assigned_date'])); ?></small></div><span class="badge"><?php echo ucfirst($r['status']); ?></span>
                            </div>
                            <p style="margin:0 0 14px 39px;color:#718380;font-size:10px;line-height:1.7"><?php echo nl2br(htmlspecialchars($r['description'] ?: 'No diet description.')); ?></p><?php endwhile;
                                                                                                                                                                                        else: ?><div class="empty">No diet plan assigned yet.</div><?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
