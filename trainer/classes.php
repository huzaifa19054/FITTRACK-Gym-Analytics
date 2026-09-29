<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('trainer');

// Get the logged-in user ID
$uid = (int)$_SESSION['user_id'];
// Get data from the database
$tr = $conn->query("SELECT id FROM trainers WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$tid = (int)($tr['id'] ?? 0);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = $_POST['class_date'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        $cap = max(1, (int)($_POST['capacity'] ?? 20));
        $status = $_POST['status'] ?? 'scheduled';
        if ($name === '' || $date === '' || $start === '' || $end === '') $err = 'Please fill all required class fields.';
        elseif ($end <= $start) $err = 'End time must be later than start time.';
        elseif ($id > 0) {
            // Get data from the database
            $q = $conn->prepare('UPDATE classes SET name=?,description=?,class_date=?,start_time=?,end_time=?,capacity=?,status=? WHERE id=? AND trainer_id=?');
            // Add values to the prepared query
            $q->bind_param('sssssisii', $name, $desc, $date, $start, $end, $cap, $status, $id, $tid);
            if ($q->execute()) $msg = 'Class updated successfully.';
// Run the database query.
            else $err = 'Unable to update class.';
        } else {
            // Get data from the database
            $q = $conn->prepare('INSERT INTO classes(trainer_id,name,description,class_date,start_time,end_time,capacity,status) VALUES(?,?,?,?,?,?,?,?)');
            // Add values to the prepared query
            $q->bind_param('isssssis', $tid, $name, $desc, $date, $start, $end, $cap, $status);
            if ($q->execute()) $msg = 'Class created successfully.';
            else $err = 'Unable to create class.';
        }
// Run the database query.
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Get data from the database
        $q = $conn->prepare('DELETE FROM classes WHERE id=? AND trainer_id=?');
        $q->bind_param('ii', $id, $tid);
        if ($q->execute()) $msg = 'Class deleted successfully.';
// Run the database query.
        else $err = 'Unable to delete class. Remove bookings first if needed.';
    } elseif ($action === 'booking_status') {
        $bid = (int)($_POST['booking_id'] ?? 0);
        $status = $_POST['status'] ?? 'booked';
        // Get data from the database
        $q = $conn->prepare("UPDATE class_bookings b JOIN classes c ON c.id=b.class_id SET b.status=? WHERE b.id=? AND c.trainer_id=?");
        // Add values to the prepared query
        $q->bind_param('sii', $status, $bid, $tid);
        $q->execute();
        $msg = 'Booking status updated.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    // Get data from the database
    $edit = $conn->query("SELECT * FROM classes WHERE id=$eid AND trainer_id=$tid LIMIT 1")->fetch_assoc();
}
$selected = (int)($_GET['manage'] ?? 0);
// Run the database query.
$managedClass = $selected ? $conn->query("SELECT * FROM classes WHERE id=$selected AND trainer_id=$tid LIMIT 1")->fetch_assoc() : null;
$bookings = null;
if ($managedClass) {
    // Get data from the database
    $bookings = $conn->query("SELECT b.id,b.status,b.booked_at,u.full_name,u.email FROM class_bookings b JOIN members m ON m.id=b.member_id JOIN users u ON u.id=m.user_id WHERE b.class_id=$selected ORDER BY b.booked_at DESC");
}
// Get data from the database
$rows = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='booked') booked FROM classes c WHERE trainer_id=$tid ORDER BY class_date DESC,start_time DESC");
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Classes</title>
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
            <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="members.php"><span class="ico">◎</span>My Members</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav active" href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav" href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">TRAINER / CLASSES</div>
                    <h1>Classes</h1>
                    <p>Create sessions, manage bookings and keep your schedule organized.</p>
                </div>
                <div class="dashboard-user-actions">
                    <div class="user-pill"><span class="online-dot"></span><?php echo htmlspecialchars($_SESSION['full_name']); ?><b>Trainer</b></div><a href="../logout.php" class="logout-top">Logout <span>⇥</span></a>
                </div>
            </div>
            <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?><?php if ($err): ?><div class="alert"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
            <div class="dashboard-grid two-col">
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">SCHEDULE</span>
                            <h2><?php echo $edit ? 'Edit class' : 'New class'; ?></h2>
                        </div><?php if ($edit): ?><a class="panel-link" href="classes.php">Cancel →</a><?php endif; ?>
                    </div>
    <!-- Form used to collect or submit data. -->
                    <form method="post" class="form-grid">
<!-- // Run the database query. -->
                        <?php echo csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo (int)($edit['id'] ?? 0); ?>"><label>Name<input name="name" required value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>"></label><label>Capacity<input type="number" name="capacity" value="<?php echo (int)($edit['capacity'] ?? 20); ?>" min="1" required></label><label>Date<input type="date" name="class_date" required value="<?php echo htmlspecialchars($edit['class_date'] ?? ''); ?>"></label><label>Status<select name="status">
                                <option value="scheduled" <?php echo (($edit['status'] ?? 'scheduled') === 'scheduled' ? 'selected' : ''); ?>>Scheduled</option>
                                <option value="completed" <?php echo (($edit['status'] ?? '') === 'completed' ? 'selected' : ''); ?>>Completed</option>
                                <option value="cancelled" <?php echo (($edit['status'] ?? '') === 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
<!-- // Run the database query. -->
                            </select></label><label>Start<input type="time" name="start_time" required value="<?php echo htmlspecialchars($edit['start_time'] ?? ''); ?>"></label><label>End<input type="time" name="end_time" required value="<?php echo htmlspecialchars($edit['end_time'] ?? ''); ?>"></label><label style="grid-column:1/-1">Description<textarea name="description" rows="3"><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea></label><button class="btn" type="submit"><?php echo $edit ? 'Update Class' : 'Create Class'; ?></button></form>
                </section>
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">YOUR SCHEDULE</span>
                            <h2>Classes</h2>
                        </div>
                    </div>
                    <div class="table-wrap">
    <!-- Table used to display records. -->
                        <table>
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Bookings</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody><?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?><tr>
                                            <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                                            <td><?php echo date('d M Y', strtotime($r['class_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($r['start_time'])); ?></td>
                                            <td><?php echo (int)$r['booked'] . ' / ' . (int)$r['capacity']; ?></td>
                                            <td><?php echo ucfirst($r['status']); ?></td>
                                            <td><a class="action" href="classes.php?manage=<?php echo (int)$r['id']; ?>">Bookings</a> <a class="action" href="classes.php?edit=<?php echo (int)$r['id']; ?>">Edit</a>
<!-- // Run the database query. -->
    <!-- Form used to collect or submit data. -->
                                                <form method="post" style="display:inline" onsubmit="return confirm('Delete this class?')">
<!-- // Run the database query. -->
                                                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><button class="action" type="submit">Delete</button></form>
                                            </td>
                                        </tr><?php endwhile;
                                        else: ?><tr>
                                        <td colspan="6" class="empty">No classes scheduled.</td>
                                    </tr><?php endif; ?></tbody>
                        </table>
                    </div>
                </section>
            </div>
            <?php if ($managedClass): ?><section class="panel" style="margin-top:16px">
                    <div class="panel-head">
                        <div><span class="panel-kicker">BOOKING MANAGEMENT</span>
                            <h2><?php echo htmlspecialchars($managedClass['name']); ?></h2>
                        </div><a class="panel-link" href="classes.php">Close →</a>
                    </div>
                    <div class="table-wrap">
    <!-- Table used to display records. -->
                        <table>
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Booked At</th>
                                    <th>Status</th>
                                    <th>Update</th>
                                </tr>
                            </thead>
                            <tbody><?php if ($bookings && $bookings->num_rows): while ($b = $bookings->fetch_assoc()): ?><tr>
                                            <td><strong><?php echo htmlspecialchars($b['full_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($b['email']); ?></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($b['booked_at'])); ?></td>
                                            <td><?php echo ucfirst($b['status']); ?></td>
                                            <td>
    <!-- Form used to collect or submit data. -->
                                                <form method="post" style="display:flex;gap:6px">
<!-- // Run the database query. -->
                                                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="booking_status"><input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>"><select name="status">
                                                        <option value="booked" <?php echo $b['status'] === 'booked' ? 'selected' : ''; ?>>Booked</option>
                                                        <option value="attended" <?php echo $b['status'] === 'attended' ? 'selected' : ''; ?>>Attended</option>
                                                        <option value="cancelled" <?php echo $b['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
<!-- // Run the database query. -->
                                                    </select><button class="action" type="submit">Save</button></form>
                                            </td>
                                        </tr><?php endwhile;
                                        else: ?><tr>
                                        <td colspan="5" class="empty">No bookings for this class.</td>
                                    </tr><?php endif; ?></tbody>
                        </table>
                    </div>
                </section><?php endif; ?>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
