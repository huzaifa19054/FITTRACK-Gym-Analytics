<?php require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_role('trainer');
$uid = (int)$_SESSION['user_id'];
// Run the database query.
$tr = $conn->query("SELECT id FROM trainers WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$tid = (int)($tr['id'] ?? 0);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid = (int)$_POST['member_id'];
    $in = $_POST['check_in'];
    $out = $_POST['check_out'] ?: null;
    $st = $_POST['status'];
// Run the database query.
    $own = $conn->query("SELECT id FROM members WHERE id=$mid AND trainer_id=$tid")->fetch_assoc();
    if ($own) {
// Run the database query.
        $q = $conn->prepare('INSERT INTO attendance(member_id,check_in,check_out,status) VALUES(?,?,?,?)');
        $q->bind_param('isss', $mid, $in, $out, $st);
        $q->execute();
        $msg = 'Attendance recorded.';
    }
}
// Run the database query.
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id WHERE m.trainer_id=$tid AND m.status='active' ORDER BY u.full_name");
// Run the database query.
$rows = $conn->query("SELECT a.*,u.full_name FROM attendance a JOIN members m ON m.id=a.member_id JOIN users u ON u.id=m.user_id WHERE m.trainer_id=$tid ORDER BY a.id DESC LIMIT 30"); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Attendance</title>
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
            <div class="nav-label">MAIN</div><a class="nav " href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav " href="members.php"><span class="ico">◎</span>My Members</a><a class="nav " href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav " href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav active" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav " href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav " href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">TRAINER / ATTENDANCE</div>
                    <h1>Attendance</h1>
                    <p>Record visits for your assigned members.</p>
                </div>
            </div><?php if ($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?><div class="dashboard-grid two-col">
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">CHECK-IN</span>
                            <h2>Record visit</h2>
                        </div>
                    </div>
    <!-- Form used to collect or submit data. -->
                    <form method="post" class="form-grid">
<!-- // Run the database query. -->
                        <?php echo csrf_field(); ?><label>Member<select name="member_id" required>
<!-- // Run the database query. -->
                                <option value="">Select member</option><?php while ($m = $members->fetch_assoc()): ?><option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option><?php endwhile; ?>
<!-- // Run the database query. -->
                            </select></label><label>Status<select name="status">
                                <option value="present">Present</option>
                                <option value="late">Late</option>
<!-- // Run the database query. -->
                            </select></label><label>Check in<input type="datetime-local" name="check_in" value="<?php echo date('Y-m-d\TH:i'); ?>" required></label><label>Check out<input type="datetime-local" name="check_out"></label><button class="btn" type="submit">Save Attendance</button></form>
                </section>
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">RECENT</span>
                            <h2>Visit history</h2>
                        </div>
                    </div>
                    <div class="table-wrap">
    <!-- Table used to display records. -->
                        <table>
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Check in</th>
                                    <th>Check out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody><?php while ($r = $rows->fetch_assoc()): ?><tr>
                                        <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                                        <td><?php echo date('d M Y h:i A', strtotime($r['check_in'])); ?></td>
                                        <td><?php echo $r['check_out'] ? date('h:i A', strtotime($r['check_out'])) : '—'; ?></td>
                                        <td><?php echo ucfirst($r['status']); ?></td>
                                    </tr><?php endwhile; ?></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
