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
$edit = null;

// Check if an action was requested
if (isset($_GET['delete'])) {
// Run the database query.
    $id = (int)$_GET['delete'];
    // Get data from the database
    $stmt = $conn->prepare('DELETE FROM classes WHERE id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Class deleted successfully.';
// Run the database query.
    else $error = 'Unable to delete class.';
    $stmt->close();
}

// Check if an action was requested
if (isset($_GET['booking_status'])) {
    $id = (int)$_GET['booking_status'];
    $status = $_GET['status'] ?? '';
    if (in_array($status, ['booked', 'attended', 'cancelled'], true)) {
        // Get data from the database
        $stmt = $conn->prepare('UPDATE class_bookings SET status=? WHERE id=?');
        // Add values to the prepared query
        $stmt->bind_param('si', $status, $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Booking status updated.';
// Run the database query.
        else $error = 'Unable to update booking.';
        $stmt->close();
    }
}

// Check if an action was requested
if (isset($_GET['remove_booking'])) {
    $id = (int)$_GET['remove_booking'];
    // Get data from the database
    $stmt = $conn->prepare('DELETE FROM class_bookings WHERE id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Booking removed.';
    else $error = 'Unable to remove booking.';
    $stmt->close();
}

// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare('SELECT id,trainer_id,name,description,class_date,start_time,end_time,capacity,status FROM classes WHERE id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Check which form action was selected
    if ($action === 'save_class') {
        $id = (int)($_POST['id'] ?? 0);
        $trainer_id = (int)($_POST['trainer_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $class_date = $_POST['class_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 20);
        $status = $_POST['status'] ?? 'scheduled';
        if ($trainer_id < 1 || $name === '' || !$class_date || !$start_time || !$end_time) $error = 'Trainer, class name, date and times are required.';
        elseif (strtotime($class_date . ' ' . $end_time) <= strtotime($class_date . ' ' . $start_time)) $error = 'End time must be later than start time.';
        elseif ($capacity < 1) $error = 'Capacity must be at least 1.';
        elseif (!in_array($status, ['scheduled', 'completed', 'cancelled'], true)) $error = 'Invalid class status.';
        elseif ($id) {
            // Get data from the database
            $stmt = $conn->prepare('UPDATE classes SET trainer_id=?,name=?,description=?,class_date=?,start_time=?,end_time=?,capacity=?,status=? WHERE id=?');
            // Add values to the prepared query
            $stmt->bind_param('isssssisi', $trainer_id, $name, $description, $class_date, $start_time, $end_time, $capacity, $status, $id);
            // Run the database query
            if ($stmt->execute()) $message = 'Class updated successfully.';
// Run the database query.
            else $error = 'Could not update class.';
            $stmt->close();
        } else {
            // Get data from the database
            $stmt = $conn->prepare('INSERT INTO classes(trainer_id,name,description,class_date,start_time,end_time,capacity,status) VALUES(?,?,?,?,?,?,?,?)');
            // Add values to the prepared query
            $stmt->bind_param('isssssis', $trainer_id, $name, $description, $class_date, $start_time, $end_time, $capacity, $status);
            // Run the database query
            if ($stmt->execute()) $message = 'Class scheduled successfully.';
            else $error = 'Could not create class.';
            $stmt->close();
        }
        if ($message) $edit = null;
    }

    // Check which form action was selected
    if ($action === 'book_member') {
        $class_id = (int)($_POST['class_id'] ?? 0);
        $member_id = (int)($_POST['member_id'] ?? 0);
// Run the database query.
        if ($class_id < 1 || $member_id < 1) $error = 'Select a class and member.';
        else {
            // Get data from the database
            $stmt = $conn->prepare("SELECT capacity,(SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='booked') booked,status FROM classes c WHERE c.id=?");
            // Add values to the prepared query
            $stmt->bind_param('i', $class_id);
            $stmt->execute();
            $class = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            // Get data from the database
            $already = $conn->prepare("SELECT id FROM class_bookings WHERE class_id=? AND member_id=? AND status<>'cancelled' LIMIT 1");
            $already->bind_param('ii', $class_id, $member_id);
            $already->execute();
            $exists = $already->get_result()->fetch_assoc();
            $already->close();
            if (!$class) $error = 'Class not found.';
            elseif ($class['status'] === 'cancelled') $error = 'Cancelled classes cannot receive bookings.';
            elseif ($exists) $error = 'This member is already booked for this class.';
            elseif ((int)$class['booked'] >= (int)$class['capacity']) $error = 'This class is already full.';
            else {
                // Get data from the database
                $stmt = $conn->prepare("INSERT INTO class_bookings(class_id,member_id,status) VALUES(?,?, 'booked')");
                // Add values to the prepared query
                $stmt->bind_param('ii', $class_id, $member_id);
                // Run the database query
                if ($stmt->execute()) $message = 'Member booked successfully.';
                else $error = 'Could not create booking.';
                $stmt->close();
            }
        }
    }
}

// Get data from the database
$trainers = $conn->query("SELECT t.id,u.full_name FROM trainers t JOIN users u ON u.id=t.user_id WHERE t.status='active' ORDER BY u.full_name");
// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id WHERE m.status='active' ORDER BY u.full_name");
// Get data from the database
$classes = $conn->query("SELECT c.*,u.full_name trainer_name,(SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='booked') booked_count,(SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='attended') attended_count FROM classes c JOIN trainers t ON t.id=c.trainer_id JOIN users u ON u.id=t.user_id ORDER BY c.class_date DESC,c.start_time DESC,c.id DESC");
$selected = (int)($_GET['class'] ?? 0);
$selectedClass = null;
$bookings = null;
if ($selected) {
    // Get data from the database
    $stmt = $conn->prepare('SELECT c.*,u.full_name trainer_name FROM classes c JOIN trainers t ON t.id=c.trainer_id JOIN users u ON u.id=t.user_id WHERE c.id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $selected);
    $stmt->execute();
    $selectedClass = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($selectedClass) {
        // Get data from the database
        $stmt = $conn->prepare('SELECT b.id,b.status,b.booked_at,u.full_name member_name,m.id member_id FROM class_bookings b JOIN members m ON m.id=b.member_id JOIN users u ON u.id=m.user_id WHERE b.class_id=? ORDER BY b.id DESC');
        // Add values to the prepared query
        $stmt->bind_param('i', $selected);
        $stmt->execute();
        $bookings = $stmt->get_result();
        $stmt->close();
    }
}

// Run the database query.
$totalClasses = (int)$conn->query('SELECT COUNT(*) c FROM classes')->fetch_assoc()['c'];
// Run the database query.
$scheduled = (int)$conn->query("SELECT COUNT(*) c FROM classes WHERE status='scheduled'")->fetch_assoc()['c'];
// Run the database query.
$today = (int)$conn->query("SELECT COUNT(*) c FROM classes WHERE class_date=CURDATE() AND status='scheduled'")->fetch_assoc()['c'];
// Run the database query.
$totalBookings = (int)$conn->query("SELECT COUNT(*) c FROM class_bookings WHERE status='booked'")->fetch_assoc()['c'];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Classes & Schedule — FitTrack</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .form-control {
            display: block;
            width: 100%;
            margin-top: 6px;
            padding: 11px;
            border-radius: 7px;
            border: 1px solid #1a3032;
            background: #0a1719;
            color: #fff;
            outline: none
        }

        .form-control:focus {
            border-color: #287f70
        }

        .class-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 13px
        }

        .class-card {
            background: #0b191b;
            border: 1px solid #182c2e;
            border-radius: 12px;
            padding: 18px;
            transition: .25s
        }

        .class-card:hover {
            transform: translateY(-3px);
            border-color: #24514a
        }

        .class-card h3 {
            font: 700 16px Manrope;
            margin: 0 0 7px
        }

        .class-card p {
            font-size: 10px;
            color: #728481;
            line-height: 1.55;
            min-height: 30px
        }

        .class-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin: 14px 0
        }

        .class-meta div {
            background: #0d1f21;
            border-radius: 7px;
            padding: 9px
        }

        .class-meta small {
            display: block;
            color: #617370;
            font-size: 8px
        }

        .class-meta strong {
            display: block;
            font-size: 10px;
            margin-top: 3px
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 18px
        }

        .status {
            font-size: 8px;
            padding: 5px 8px;
            border-radius: 20px
        }

        .scheduled {
            background: #12352f;
            color: #54d5bc
        }

        .completed {
            background: #263226;
            color: #9bd59b
        }

        .cancelled {
            background: #3a2222;
            color: #df9999
        }

        .booking-count {
            font-size: 9px;
            color: #738581
        }

        @media(max-width:900px) {

            .class-grid,
            .detail-grid {
                grid-template-columns: 1fr
            }
        }

        @media(max-width:650px) {
            .form-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="members.php"><span class="ico">◎</span>Members</a><a class="nav" href="trainers.php"><span class="ico">✦</span>Trainers</a><a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a><a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a><a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav active" href="classes.php"><span class="ico">▤</span>Classes</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Classes & Schedule</h1>
                    <p>Schedule gym classes, manage capacity and book members.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            </div>
            <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <div class="grid">
                <div class="stat"><small>Total Classes</small><strong><?php echo $totalClasses; ?></strong><i>▤</i></div>
                <div class="stat"><small>Scheduled</small><strong><?php echo $scheduled; ?></strong><i>◷</i></div>
                <div class="stat"><small>Today's Classes</small><strong><?php echo $today; ?></strong><i>◆</i></div>
                <div class="stat"><small>Active Bookings</small><strong><?php echo $totalBookings; ?></strong><i>◎</i></div>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Class' : 'Schedule New Class'; ?></h2><?php if ($edit): ?><a class="btn secondary" href="classes.php">Cancel</a><?php endif; ?>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
<!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save_class"><?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?><label>Trainer<select class="form-control" name="trainer_id" required>
<!-- // Run the database query. -->
                            <option value="">Select trainer</option><?php if ($trainers): while ($t = $trainers->fetch_assoc()): ?><option value="<?php echo $t['id']; ?>" <?php echo ((int)($edit['trainer_id'] ?? 0) == (int)$t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['full_name']); ?></option><?php endwhile;
                                                                                                                                                                                                                                                                                                    endif; ?>
<!-- // Run the database query. -->
                        </select></label><label>Class Name<input class="form-control" name="name" value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" placeholder="e.g. Morning Strength" required></label><label>Date<input class="form-control" type="date" name="class_date" value="<?php echo htmlspecialchars($edit['class_date'] ?? date('Y-m-d')); ?>" required></label><label>Capacity<input class="form-control" type="number" min="1" name="capacity" value="<?php echo htmlspecialchars($edit['capacity'] ?? 20); ?>" required></label><label>Start Time<input class="form-control" type="time" name="start_time" value="<?php echo htmlspecialchars($edit['start_time'] ?? '08:00'); ?>" required></label><label>End Time<input class="form-control" type="time" name="end_time" value="<?php echo htmlspecialchars($edit['end_time'] ?? '09:00'); ?>" required></label><label>Status<select class="form-control" name="status">
                            <option value="scheduled" <?php echo (($edit['status'] ?? 'scheduled') === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="completed" <?php echo (($edit['status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo (($edit['status'] ?? '') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
<!-- // Run the database query. -->     
                        </select></label><label>Description<input class="form-control" name="description" value="<?php echo htmlspecialchars($edit['description'] ?? ''); ?>" placeholder="Short class description"></label>
<!-- // Run the database query. -->
                    <div style="grid-column:1/-1"><button class="btn" type="submit"><?php echo $edit ? 'Update Class' : 'Schedule Class'; ?></button></div>
                </form>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2>Class Schedule</h2><span style="font-size:10px;color:#637572"><?php echo $totalClasses; ?> classes</span>
                </div>
                <div class="class-grid"><?php if ($classes && $classes->num_rows): while ($c = $classes->fetch_assoc()): ?><div class="class-card">
                                <div style="display:flex;justify-content:space-between;gap:8px;align-items:center"><span class="status <?php echo htmlspecialchars($c['status']); ?>"><?php echo ucfirst($c['status']); ?></span><span class="booking-count"><?php echo (int)$c['booked_count']; ?>/<?php echo (int)$c['capacity']; ?> booked</span></div>
                                <h3 style="margin-top:12px"><?php echo htmlspecialchars($c['name']); ?></h3>
                                <p><?php echo htmlspecialchars($c['description'] ?: 'No description added.'); ?></p>
                                <div class="class-meta">
                                    <div><small>Date</small><strong><?php echo date('d M Y', strtotime($c['class_date'])); ?></strong></div>
                                    <div><small>Time</small><strong><?php echo date('h:i A', strtotime($c['start_time'])); ?> – <?php echo date('h:i A', strtotime($c['end_time'])); ?></strong></div>
                                    <div><small>Trainer</small><strong><?php echo htmlspecialchars($c['trainer_name']); ?></strong></div>
                                    <div><small>Attended</small><strong><?php echo (int)$c['attended_count']; ?></strong></div>
                                </div>
<!-- // Run the database query. -->
                                <div class="actions"><a class="action" href="classes.php?class=<?php echo $c['id']; ?>">Bookings</a><a class="action" href="classes.php?edit=<?php echo $c['id']; ?>">Edit</a><a class="action" href="classes.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete this class and its bookings?')">Delete</a></div>
                            </div><?php endwhile;
                                        else: ?><div class="empty" style="grid-column:1/-1">No classes scheduled yet. Create the first class above.</div><?php endif; ?></div>
            </div>
            <?php if ($selectedClass): ?><div class="detail-grid">
                    <div class="section">
                        <div class="section-head">
                            <div>
                                <h2><?php echo htmlspecialchars($selectedClass['name']); ?> — Bookings</h2>
                                <p style="font-size:10px;color:#70817f;margin:5px 0 0"><?php echo date('d M Y', strtotime($selectedClass['class_date'])); ?> · <?php echo date('h:i A', strtotime($selectedClass['start_time'])); ?> – <?php echo date('h:i A', strtotime($selectedClass['end_time'])); ?></p>
                            </div>
                        </div>
                        <div class="table-wrap">
    <!-- Table used to display records. -->
                            <table>
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Booked At</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody><?php if ($bookings && $bookings->num_rows): while ($b = $bookings->fetch_assoc()): ?><tr>
                                                <td><strong><?php echo htmlspecialchars($b['member_name']); ?></strong></td>
                                                <td><?php echo date('d M Y, h:i A', strtotime($b['booked_at'])); ?></td>
                                                <td><span class="status <?php echo $b['status'] === 'attended' ? 'completed' : ($b['status'] === 'cancelled' ? 'cancelled' : 'scheduled'); ?>"><?php echo ucfirst($b['status']); ?></span></td>
                                                <td>
                                                    <div class="actions"><?php if ($b['status'] !== 'attended'): ?><a class="action" href="classes.php?class=<?php echo $selected; ?>&booking_status=<?php echo $b['id']; ?>&status=attended">Attended</a><?php endif; ?><?php if ($b['status'] !== 'cancelled'): ?><a class="action" href="classes.php?class=<?php echo $selected; ?>&booking_status=<?php echo $b['id']; ?>&status=cancelled">Cancel</a><?php endif; ?><?php if ($b['status'] !== 'booked'): ?><a class="action" href="classes.php?class=<?php echo $selected; ?>&booking_status=<?php echo $b['id']; ?>&status=booked">Book</a><?php endif; ?><a class="action" href="classes.php?class=<?php echo $selected; ?>&remove_booking=<?php echo $b['id']; ?>" onclick="return confirm('Remove this booking?')">Remove</a></div>
                                                </td>
                                            </tr><?php endwhile;
                                            else: ?><tr>
                                            <td colspan="4">
                                                <div class="empty">No bookings for this class yet.</div>
                                            </td>
                                        </tr><?php endif; ?></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-head">
                            <h2>Book a Member</h2>
                        </div>
    <!-- Form used to collect or submit data. -->
                        <form method="post" class="form-grid" style="grid-template-columns:1fr">
<!-- // Run the database query. -->
                            <?php echo csrf_field(); ?><input type="hidden" name="action" value="book_member"><input type="hidden" name="class_id" value="<?php echo $selected; ?>"><label>Member<select class="form-control" name="member_id" required>
<!-- // Run the database query. -->
                                    <option value="">Select member</option><?php $members2 = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id WHERE m.status='active' ORDER BY u.full_name");
                                                                            while ($m = $members2->fetch_assoc()): ?><option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option><?php endwhile; ?>
<!-- // Run the database query. -->
                                </select></label><button class="btn" type="submit">+ Book Member</button></form>
                    </div>
                </div><?php endif; ?>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
