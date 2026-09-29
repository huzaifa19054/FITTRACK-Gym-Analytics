<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('admin');
$message = '';
$error = '';
// Check if an action was requested
if (isset($_GET['delete'])) {
// Run the database query.
    $id = (int)$_GET['delete'];
    // Get data from the database
    $s = $conn->prepare('DELETE FROM progress_records WHERE id=?');
    $s->bind_param('i', $id);
    $s->execute();
    $message = $s->affected_rows ? 'Progress record deleted.' : 'Record not found.';
    $s->close();
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Check which form action was selected
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $member = (int)($_POST['member_id'] ?? 0);
        $date = $_POST['record_date'] ?? date('Y-m-d');
        $weight = (float)($_POST['weight'] ?? 0);
        $height = (float)($_POST['height'] ?? 0);
        $body = (float)($_POST['body_fat'] ?? 0);
        $chest = (float)($_POST['chest'] ?? 0);
        $waist = (float)($_POST['waist'] ?? 0);
        $arms = (float)($_POST['arms'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($member < 1 || !$date) $error = 'Member and date are required.';
        elseif ($weight <= 0) $error = 'Weight must be greater than 0.';
        elseif ($id) {
            // Get data from the database
            $s = $conn->prepare('UPDATE progress_records SET member_id=?,record_date=?,weight=?,height=?,body_fat=?,chest=?,waist=?,arms=?,notes=? WHERE id=?');
            $s->bind_param('isddddddsi', $member, $date, $weight, $height, $body, $chest, $waist, $arms, $notes, $id);
            $s->execute();
// Run the database query.
            $message = $s->affected_rows >= 0 ? 'Progress record updated successfully.' : 'Could not update record.';
            $s->close();
        } else {
            // Get data from the database
            $s = $conn->prepare('INSERT INTO progress_records(member_id,record_date,weight,height,body_fat,chest,waist,arms,notes) VALUES(?,?,?,?,?,?,?,?,?)');
            $s->bind_param('isdddddds', $member, $date, $weight, $height, $body, $chest, $waist, $arms, $notes);
            if ($s->execute()) $message = 'Progress record added successfully.';
            else $error = 'Could not add progress record.';
            $s->close();
        }
    }
}
$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $s = $conn->prepare('SELECT * FROM progress_records WHERE id=?');
    $s->bind_param('i', $id);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
    $s->close();
}
// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id ORDER BY u.full_name");
// Run the database query.
$total = (int)$conn->query('SELECT COUNT(*) c FROM progress_records')->fetch_assoc()['c'];
// Get data from the database
$latestDate = $conn->query("SELECT record_date FROM progress_records ORDER BY record_date DESC,id DESC LIMIT 1");
$latestDateRow = $latestDate ? $latestDate->fetch_assoc() : null;
// Get data from the database
$latest = $conn->query("SELECT p.*,u.full_name FROM progress_records p JOIN members m ON m.id=p.member_id JOIN users u ON u.id=m.user_id ORDER BY p.record_date DESC,p.id DESC LIMIT 100");
// Run the database query.
$tracked = (int)$conn->query('SELECT COUNT(DISTINCT member_id) c FROM progress_records')->fetch_assoc()['c'];
// Run the database query.
$avg = (float)$conn->query('SELECT COALESCE(AVG(weight),0) a FROM progress_records')->fetch_assoc()['a'];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Progress Tracking · FitTrack</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="members.php"><span class="ico">◎</span>Members</a><a class="nav" href="trainers.php"><span class="ico">✦</span>Trainers</a><a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a><a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a><a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav active" href="progress.php"><span class="ico">↗</span>Progress</a><a class="nav" href="reports.php"><span class="ico">▥</span>Reports</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="users.php"><span class="ico">♙</span>User Management</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Progress Tracking</h1>
                    <p>Track member body measurements and fitness progress over time.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?> · Administrator</div>
            </div><?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><div class="grid">
                <div class="stat"><small>Total Records</small><strong><?php echo $total; ?></strong><i>▥</i></div>
                <div class="stat"><small>Members Tracked</small><strong><?php echo $tracked; ?></strong><i>◎</i></div>
                <div class="stat"><small>Average Weight</small><strong><?php echo number_format($avg, 1); ?> kg</strong><i>↕</i></div>
<!-- // Run the database query. -->
                <div class="stat"><small>Latest Update</small><strong><?php echo $latestDateRow ? date('d M', strtotime($latestDateRow['record_date'])) : '—'; ?></strong><i>◷</i></div>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Progress Record' : 'Add Progress Record'; ?></h2><?php if ($edit): ?><a class="btn secondary" href="progress.php">Cancel</a><?php endif; ?>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
<!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo (int)($edit['id'] ?? 0); ?>"><label>Member<select class="form-control" name="member_id" required>
<!-- // Run the database query. -->
                            <option value="">Select member</option><?php if ($members): while ($m = $members->fetch_assoc()): ?><option value="<?php echo $m['id']; ?>" <?php echo ((int)($edit['member_id'] ?? 0) == $m['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['full_name']); ?></option><?php endwhile;
                                                                                                                                                                                                                                                                                        endif; ?>
<!-- // Run the database query. -->
                        </select></label><label>Record Date<input class="form-control" type="date" name="record_date" value="<?php echo htmlspecialchars($edit['record_date'] ?? date('Y-m-d')); ?>" required></label><label>Weight (kg)<input class="form-control" type="number" step="0.1" name="weight" value="<?php echo htmlspecialchars($edit['weight'] ?? ''); ?>" required></label><label>Height (cm)<input class="form-control" type="number" step="0.1" name="height" value="<?php echo htmlspecialchars($edit['height'] ?? ''); ?>"></label><label>Body Fat (%)<input class="form-control" type="number" step="0.1" name="body_fat" value="<?php echo htmlspecialchars($edit['body_fat'] ?? ''); ?>"></label><label>Chest (cm)<input class="form-control" type="number" step="0.1" name="chest" value="<?php echo htmlspecialchars($edit['chest'] ?? ''); ?>"></label><label>Waist (cm)<input class="form-control" type="number" step="0.1" name="waist" value="<?php echo htmlspecialchars($edit['waist'] ?? ''); ?>"></label><label>Arms (cm)<input class="form-control" type="number" step="0.1" name="arms" value="<?php echo htmlspecialchars($edit['arms'] ?? ''); ?>"></label><label style="grid-column:1/-1">Notes<input class="form-control" name="notes" placeholder="Progress notes, observations, goals..." value="<?php echo htmlspecialchars($edit['notes'] ?? ''); ?>"></label>
<!-- // Run the database query. -->
                    <div style="grid-column:1/-1"><button class="btn" type="submit"><?php echo $edit ? 'Update Record' : 'Save Progress'; ?></button></div>
                </form>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2>Progress History</h2><span style="font-size:10px;color:#637572"><?php echo $total; ?> records</span>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Date</th>
                                <th>Weight</th>
                                <th>Height</th>
                                <th>Body Fat</th>
                                <th>Measurements</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($latest && $latest->num_rows): while ($p = $latest->fetch_assoc()): ?><tr>
                                        <td><strong><?php echo htmlspecialchars($p['full_name']); ?></strong></td>
                                        <td><?php echo date('d M Y', strtotime($p['record_date'])); ?></td>
                                        <td><?php echo number_format($p['weight'], 1); ?> kg</td>
                                        <td><?php echo $p['height'] ? number_format($p['height'], 1) . ' cm' : '—'; ?></td>
                                        <td><?php echo $p['body_fat'] ? number_format($p['body_fat'], 1) . ' %' : '—'; ?></td>
                                        <td><?php echo $p['chest'] ? number_format($p['chest'], 1) . ' / ' : ''; ?><?php echo $p['waist'] ? number_format($p['waist'], 1) . ' / ' : ''; ?><?php echo $p['arms'] ? number_format($p['arms'], 1) : '—'; ?> cm</td>
                                        <td class="mini"><?php echo htmlspecialchars($p['notes'] ?: '—'); ?></td>
                                        <td>
<!-- // Run the database query. -->
                                            <div class="actions"><a class="action" href="progress.php?edit=<?php echo $p['id']; ?>">Edit</a><a class="action" href="progress.php?delete=<?php echo $p['id']; ?>" onclick="return confirm('Delete this progress record?')">Delete</a></div>
                                        </td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="8">
                                        <div class="empty">No progress records yet.</div>
                                    </td>
                                </tr><?php endif; ?></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
