<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('admin');
date_default_timezone_set('Asia/Karachi');

$message = '';
$error = '';

// Check if an action was requested
if (isset($_GET['checkout'])) {
    $id = (int)$_GET['checkout'];
    // Get data from the database
    $stmt = $conn->prepare("UPDATE attendance SET check_out=? WHERE id=? AND check_out IS NULL");
    $now = date('Y-m-d H:i:s');
    // Add values to the prepared query
    $stmt->bind_param('si', $now, $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) $message = 'Member checked out successfully.';
    else $error = 'This attendance record is already closed or does not exist.';
    $stmt->close();
}

// Check if an action was requested
if (isset($_GET['delete'])) {
// Run the database query.
    $id = (int)$_GET['delete'];
    // Get data from the database
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Attendance record deleted successfully.';
// Run the database query.
    else $error = 'Unable to delete attendance record.';
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $member = (int)($_POST['member_id'] ?? 0);
    $check_in = trim($_POST['check_in'] ?? '');
    $check_out = trim($_POST['check_out'] ?? '');
    $status = $_POST['status'] ?? 'present';

    if (!$member || !$check_in || !in_array($status, ['present', 'late'], true)) {
        $error = 'Member, check-in time and valid status are required.';
    } elseif ($check_out && strtotime($check_out) < strtotime($check_in)) {
        $error = 'Check-out time cannot be before check-in time.';
    } else {
        // Only one open attendance record per member.
        if (!$error) {
            // A member may have many visits per day, but only one OPEN visit at a time.
            // Get data from the database
            $stmt = $conn->prepare("SELECT id FROM attendance WHERE member_id=? AND check_out IS NULL AND id<>? LIMIT 1");
            // Add values to the prepared query
            $stmt->bind_param('ii', $member, $id);
            $stmt->execute();
            $open = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($open && !$check_out) $error = 'This member already has an active check-in. Check them out first.';
        }

        if (!$error && $id) {
            // Get data from the database
            $stmt = $conn->prepare("UPDATE attendance SET member_id=?,check_in=?,check_out=?,status=? WHERE id=?");
            $out = $check_out !== '' ? $check_out : null;
            // Add values to the prepared query
            $stmt->bind_param('isssi', $member, $check_in, $out, $status, $id);
            // Run the database query
            if ($stmt->execute()) $message = 'Attendance record updated successfully.';
// Run the database query.
            else $error = 'Could not update attendance record.';
            $stmt->close();
        } elseif (!$error) {
            // Get data from the database
            $stmt = $conn->prepare("INSERT INTO attendance(member_id,check_in,check_out,status) VALUES(?,?,?,?)");
            $out = $check_out !== '' ? $check_out : null;
            // Add values to the prepared query
            $stmt->bind_param('isss', $member, $check_in, $out, $status);
            // Run the database query
            if ($stmt->execute()) $message = 'Attendance recorded successfully.';
            else $error = 'Could not record attendance.';
            $stmt->close();
        }
    }
}

// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id ORDER BY u.full_name");
$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$today = date('Y-m-d');
$today_present = 0;
$inside = 0;
$today_late = 0;
// Get data from the database
$r = $conn->query("SELECT COUNT(*) c FROM attendance WHERE DATE(check_in)='$today'");
if ($r) $today_present = (int)$r->fetch_assoc()['c'];
// Get data from the database
$r = $conn->query("SELECT COUNT(*) c FROM attendance WHERE check_out IS NULL");
if ($r) $inside = (int)$r->fetch_assoc()['c'];
// Get data from the database
$r = $conn->query("SELECT COUNT(*) c FROM attendance WHERE DATE(check_in)='$today' AND status='late'");
if ($r) $today_late = (int)$r->fetch_assoc()['c'];

$search = trim($_GET['search'] ?? '');
$date_filter = trim($_GET['date'] ?? '');
$where = [];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = 'u.full_name LIKE ?';
    $params[] = '%' . $search . '%';
    $types .= 's';
}
if ($date_filter !== '') {
    $where[] = 'DATE(a.check_in)=?';
    $params[] = $date_filter;
    $types .= 's';
}
// Run the database query.
$sql = "SELECT a.*,u.full_name FROM attendance a JOIN members m ON m.id=a.member_id JOIN users u ON u.id=m.user_id" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY a.check_in DESC,a.id DESC";
if ($params) {
    // Get data from the database
    $stmt = $conn->prepare($sql);
    // Add values to the prepared query
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $list = $stmt->get_result();
} else $list = $conn->query($sql);

function dt_input($value)
{
    return $value ? date('Y-m-d\\TH:i', strtotime($value)) : '';
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Attendance — FitTrack</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div>
            <a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a>
            <a class="nav" href="members.php"><span class="ico">◎</span>Members</a>
            <a class="nav" href="trainers.php"><span class="ico">✦</span>Trainers</a>
            <a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a>
            <a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a>
            <a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <div class="nav-label">MANAGEMENT</div>
            <a class="nav active" href="attendance.php"><span class="ico">◷</span>Attendance</a>
            <a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a>
            <a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a>
            <a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>

    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Attendance</h1>
                    <p>Track member check-ins, check-outs and attendance history.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?> · Administrator</div>
            </div>
            <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="grid">
                <div class="stat"><small>Today's Check-ins</small><strong><?php echo $today_present; ?></strong><i>✓</i></div>
                <div class="stat"><small>Currently Inside</small><strong><?php echo $inside; ?></strong><i>↗</i></div>
                <div class="stat"><small>Late Today</small><strong><?php echo $today_late; ?></strong><i>◷</i></div>
                <div class="stat"><small>Total Records</small><strong><?php echo $list->num_rows; ?></strong><i>▤</i></div>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Attendance' : 'Record Check-in'; ?></h2><?php if ($edit): ?><a class="btn secondary" href="attendance.php">Cancel</a><?php endif; ?>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)($edit['id'] ?? 0); ?>">
<!-- // Run the database query. -->
                    <label>Member<select name="member_id" required>
<!-- // Run the database query. -->
                            <option value="">Select member</option><?php $members->data_seek(0);
                                                                    while ($m = $members->fetch_assoc()): ?><option value="<?php echo $m['id']; ?>" <?php echo (($edit['member_id'] ?? '') == $m['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['full_name']); ?></option><?php endwhile; ?>
                        </select></label>
<!-- // Run the database query. -->
                    <label>Status<select name="status">
                            <option value="present" <?php echo (($edit['status'] ?? 'present') === 'present') ? 'selected' : ''; ?>>Present</option>
                            <option value="late" <?php echo (($edit['status'] ?? '') === 'late') ? 'selected' : ''; ?>>Late</option>
                        </select></label>
                    <label>Check-in<input type="datetime-local" name="check_in" value="<?php echo htmlspecialchars(dt_input($edit['check_in'] ?? date('Y-m-d H:i:s'))); ?>" required></label>
                    <label>Check-out (optional)<input type="datetime-local" name="check_out" value="<?php echo htmlspecialchars(dt_input($edit['check_out'] ?? '')); ?>"></label>
<!-- // Run the database query. -->
                    <div style="display:flex;align-items:end"><button class="btn" type="submit"><?php echo $edit ? 'Update Attendance' : 'Save Check-in'; ?></button></div>
                </form>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2>Attendance History</h2><span style="font-size:10px;color:#637572"><?php echo $list->num_rows; ?> records</span>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="get" class="filter-bar">
                    <?php echo csrf_field(); ?><input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search member..."><input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"><button class="btn" type="submit">Filter</button><?php if ($search !== '' || $date_filter !== ''): ?><a class="btn secondary" href="attendance.php">Clear</a><?php endif; ?></form>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list->num_rows): while ($row = $list->fetch_assoc()):
                                    $duration = '—';
                                    if ($row['check_out']) {
                                        $mins = max(0, (int)round((strtotime($row['check_out']) - strtotime($row['check_in'])) / 60));
                                        $hours = intdiv($mins, 60);
                                        $rem = $mins % 60;
                                        $duration = ($hours ? $hours . 'h ' : '') . $rem . 'm';
                                    }
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($row['check_in']))); ?></td>
                                        <td><?php echo $row['check_out'] ? htmlspecialchars(date('d M Y, h:i A', strtotime($row['check_out']))) : '<span style="color:#16c7a4">Currently inside</span>'; ?></td>
                                        <td><span class="badge"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                                        <td><?php echo $duration; ?></td>
                                        <td>
<!-- // Run the database query. -->
                                            <div class="actions"><?php if (!$row['check_out']): ?><a class="action" href="attendance.php?checkout=<?php echo $row['id']; ?>" onclick="return confirm('Check out this member now?')">Check-out</a><?php endif; ?><a class="action" href="attendance.php?edit=<?php echo $row['id']; ?>">Edit</a><a class="action" href="attendance.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this attendance record?')">Delete</a></div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?><tr>
                                    <td colspan="6">
                                        <div class="empty">No attendance records yet.</div>
                                    </td>
                                </tr><?php endif; ?>
                        </tbody>
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
