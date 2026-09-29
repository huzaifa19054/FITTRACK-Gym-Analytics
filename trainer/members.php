<?php require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_role('trainer');
$uid = (int)$_SESSION['user_id'];
// Run the database query.
$tr = $conn->query("SELECT id FROM trainers WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$tid = (int)($tr['id'] ?? 0);
$q = trim($_GET['q'] ?? '');
$safe = $conn->real_escape_string($q);
// Run the database query.
$sql = "SELECT m.*,u.full_name,u.email,u.phone FROM members m JOIN users u ON u.id=m.user_id WHERE m.trainer_id=$tid" . ($q ? " AND (u.full_name LIKE '%$safe%' OR u.email LIKE '%$safe%')" : "") . " ORDER BY m.id DESC";
$rows = $conn->query($sql); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — My Members</title>
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
            <div class="nav-label">MAIN</div><a class="nav " href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav active" href="members.php"><span class="ico">◎</span>My Members</a><a class="nav " href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav " href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav " href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav " href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav " href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">TRAINER / MEMBERS</div>
                    <h1>My Members</h1>
                    <p>Members currently assigned to you.</p>
                </div><a href="dashboard.php" class="logout-top">← Dashboard</a>
            </div>
    <!-- Main page section. -->
            <section class="panel">
                <div class="panel-head">
                    <div><span class="panel-kicker">MEMBER DIRECTORY</span>
                        <h2>Assigned members</h2>
                    </div>
    <!-- Form used to collect or submit data. -->
                    <form>
                        <?php echo csrf_field(); ?><input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search member..." style="padding:10px;border:1px solid var(--border);background:#071214;color:#fff;border-radius:7px"></form>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?><tr>
                                        <td><strong><?php echo htmlspecialchars($r['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($r['email']); ?></td>
                                        <td><?php echo htmlspecialchars($r['phone'] ?? '-'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($r['joined_date'])); ?></td>
                                        <td><span class="badge <?php echo $r['status'] === 'active' ? '' : 'muted'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="5" class="empty">No assigned members found.</td>
                                </tr><?php endif; ?></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
