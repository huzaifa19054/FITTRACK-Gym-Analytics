<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('member');
// Get the logged-in user ID
$uid = (int)$_SESSION['user_id'];
// Get data from the database
$m = $conn->query("SELECT id FROM members WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$mid = (int)($m['id'] ?? 0);
$msg = '';
$error = '';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cid = (int)($_POST['class_id'] ?? 0);
    if (!$mid) {
        $error = 'Member profile not found.';
    } elseif ($action === 'book') {
        // Get data from the database
        $c = $conn->query("SELECT * FROM classes WHERE id=$cid AND class_date>=CURDATE() AND status='scheduled' LIMIT 1")->fetch_assoc();
        if (!$c) {
            $error = 'Class is no longer available.';
        } else {
// Run the database query.
            $count = (int)(($conn->query("SELECT COUNT(*) v FROM class_bookings WHERE class_id=$cid AND status='booked'")->fetch_assoc()['v'] ?? 0));
            // Get data from the database
            $exists = $conn->query("SELECT id,status FROM class_bookings WHERE class_id=$cid AND member_id=$mid LIMIT 1")->fetch_assoc();
            if ($exists && $exists['status'] === 'booked') {
                $error = 'You are already booked for this class.';
            } elseif ($count >= (int)$c['capacity']) {
                $error = 'This class is full.';
            } elseif ($exists) {
                // Get data from the database
                $q = $conn->prepare("UPDATE class_bookings SET status='booked',booked_at=CURRENT_TIMESTAMP WHERE id=?");
                // Add values to the prepared query
                $q->bind_param('i', $exists['id']);
                $q->execute();
                $msg = 'Class booked successfully.';
            } else {
                // Get data from the database
                $q = $conn->prepare("INSERT INTO class_bookings(class_id,member_id,status) VALUES(?,?,'booked')");
                // Add values to the prepared query
                $q->bind_param('ii', $cid, $mid);
                $q->execute();
                $msg = 'Class booked successfully.';
            }
        }
    } elseif ($action === 'cancel') {
        // Get data from the database
        $q = $conn->prepare("UPDATE class_bookings SET status='cancelled' WHERE class_id=? AND member_id=? AND status='booked'");
        // Add values to the prepared query
        $q->bind_param('ii', $cid, $mid);
        $q->execute();
        $msg = $q->affected_rows ? 'Class booking cancelled.' : 'Booking was already cancelled.';
    }
}
// Get data from the database
$rows = $conn->query("SELECT c.*,u.full_name trainer_name,(SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='booked') booked,(SELECT COUNT(*) FROM class_bookings b2 WHERE b2.class_id=c.id AND b2.member_id=$mid AND b2.status='booked') mine FROM classes c JOIN trainers t ON t.id=c.trainer_id JOIN users u ON u.id=t.user_id WHERE c.class_date>=CURDATE() AND c.status='scheduled' ORDER BY c.class_date,c.start_time");
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
            <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="membership.php"><span class="ico">▱</span>My Membership</a><a class="nav" href="attendance.php"><span class="ico">◷</span>My Attendance</a><a class="nav" href="workout.php"><span class="ico">▣</span>My Workout</a><a class="nav" href="diet.php"><span class="ico">◌</span>My Diet</a><a class="nav" href="payments.php"><span class="ico">₨</span>My Payments</a><a class="nav active" href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav" href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">MEMBER / CLASSES</div>
                    <h1>Classes</h1>
                    <p>Browse upcoming classes and manage your bookings.</p>
                </div>
            </div><?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?><?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><section class="panel">
                <div class="panel-head">
                    <div><span class="panel-kicker">UPCOMING</span>
                        <h2>Available classes</h2>
                    </div>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Trainer</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Seats</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?><tr>
                                        <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($r['trainer_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($r['class_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($r['start_time'])); ?></td>
                                        <td><?php echo (int)$r['booked'] . ' / ' . (int)$r['capacity']; ?></td>
                                        <td><?php if ($r['mine']): ?><form method="post" style="display:inline">
                                                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="class_id" value="<?php echo (int)$r['id']; ?>"><button class="action" type="submit" onclick="return confirm('Cancel this booking?')">Cancel</button></form><?php elseif ($r['booked'] >= $r['capacity']): ?><span class="badge muted">Full</span><?php else: ?><form method="post" style="display:inline">
                                                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="book"><input type="hidden" name="class_id" value="<?php echo (int)$r['id']; ?>"><button class="action" type="submit">Book</button></form><?php endif; ?></td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="6" class="empty">No upcoming classes.</td>
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
