<?php require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('trainer');
// Get the logged-in user ID
$uid = (int) $_SESSION['user_id'];
// Get data from the database
$tr = $conn->query("SELECT id FROM trainers WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$tid = (int) ($tr['id'] ?? 0);
$msg = '';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid = (int) $_POST['member_id'];
    $date = $_POST['record_date'];
    $weight = (float) $_POST['weight'];
    $height = $_POST['height'] !== '' ? (float) $_POST['height'] : null;
    $bf = $_POST['body_fat'] !== '' ? (float) $_POST['body_fat'] : null;
    $ch = $_POST['chest'] !== '' ? (float) $_POST['chest'] : null;
    $wa = $_POST['waist'] !== '' ? (float) $_POST['waist'] : null;
    $ar = $_POST['arms'] !== '' ? (float) $_POST['arms'] : null;
    $notes = trim($_POST['notes']);
    // Get data from the database
    $own = $conn->query("SELECT id FROM members WHERE id=$mid AND trainer_id=$tid")->fetch_assoc();
    if ($own) {
        // Get data from the database
        $q = $conn->prepare('INSERT INTO progress_records(member_id,record_date,weight,height,body_fat,chest,waist,arms,notes) VALUES(?,?,?,?,?,?,?,?,?)');
        // Add values to the prepared query
        $q->bind_param('isdddddds', $mid, $date, $weight, $height, $bf, $ch, $wa, $ar, $notes);
        $q->execute();
        $msg = 'Progress record saved.';
    }
}
// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id WHERE m.trainer_id=$tid ORDER BY u.full_name");
// Get data from the database
$rows = $conn->query("SELECT p.*,u.full_name FROM progress_records p JOIN members m ON m.id=p.member_id JOIN users u ON u.id=m.user_id WHERE m.trainer_id=$tid ORDER BY p.record_date DESC,p.id DESC LIMIT 30"); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Progress</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div><a class="nav " href="dashboard.php"><span
                    class="ico">⌂</span>Dashboard</a><a class="nav " href="members.php"><span class="ico">◎</span>My
                Members</a><a class="nav " href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a
                class="nav " href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav "
                href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav " href="classes.php"><span
                    class="ico">▤</span>Classes</a><a class="nav active" href="progress.php"><span
                    class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span
                    class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span
                    class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span
                    class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span
                    class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">TRAINER / PROGRESS</div>
                    <h1>Progress Tracking</h1>
                    <p>Record measurements and monitor member progress.</p>
                </div>
            </div><?php if ($msg): ?>
                <div class="alert success"><?php echo $msg; ?></div><?php endif; ?>
    <!-- Main page section. -->
            <section class="panel">
                <div class="panel-head">
                    <div><span class="panel-kicker">NEW RECORD</span>
                        <h2>Member progress</h2>
                    </div>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
<!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><label>Member<select name="member_id" required>
<!-- // Run the database query. -->
                            <option value="">Select member</option><?php while ($m = $members->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?>
                                </option><?php endwhile; ?>
<!-- // Run the database query. -->
                        </select></label><label>Date<input type="date" name="record_date"
                            value="<?php echo date('Y-m-d'); ?>" required></label><label>Weight<input type="number"
                            step="0.01" name="weight" required></label><label>Height<input type="number" step="0.01"
                            name="height"></label><label>Body Fat %<input type="number" step="0.01"
                            name="body_fat"></label><label>Chest<input type="number" step="0.01"
                            name="chest"></label><label>Waist<input type="number" step="0.01"
                            name="waist"></label><label>Arms<input type="number" step="0.01" name="arms"></label><label
                        style="grid-column:1/-1">Notes<textarea name="notes" rows="3"></textarea></label><button
                        class="btn" type="submit">Save Progress</button>
                </form>
            </section>
    <!-- Main page section. -->
            <section class="panel" style="margin-top:13px">
                <div class="panel-head">
                    <div><span class="panel-kicker">HISTORY</span>
                        <h2>Recent records</h2>
                    </div>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Date</th>
                                <th>Weight</th>
                                <th>Body Fat</th>
                                <th>Measurements</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($rows && $rows->num_rows):
                                    while ($r = $rows->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($r['record_date'])); ?></td>
                                        <td><?php echo $r['weight']; ?> kg</td>
                                        <td><?php echo $r['body_fat'] !== null ? $r['body_fat'] . '%' : '—'; ?></td>
                                        <td>Chest <?php echo $r['chest'] ?? '—'; ?> · Waist <?php echo $r['waist'] ?? '—'; ?> ·
                                            Arms <?php echo $r['arms'] ?? '—'; ?></td>
                                    </tr><?php endwhile;
                                    else: ?>
                                <tr>
                                    <td colspan="5" class="empty">No progress records yet.</td>
                                </tr><?php endif; ?>
                        </tbody>
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
