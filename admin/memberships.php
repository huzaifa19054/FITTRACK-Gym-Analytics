<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('admin');

$message = '';
$error = '';

function valid_date($d)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

// Check if an action was requested
if (isset($_GET['cancel'])) {
    $id = (int)$_GET['cancel'];
    // Get data from the database
    $stmt = $conn->prepare("UPDATE memberships SET status='cancelled' WHERE id=? AND status='active'");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Membership cancelled successfully.';
    else $error = 'Unable to cancel membership.';
    $stmt->close();
}

// Check if an action was requested
if (isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    // Get data from the database
    $stmt = $conn->prepare("UPDATE memberships SET status='active' WHERE id=? AND end_date>=CURDATE()");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Membership activated successfully.';
    else $error = 'Unable to activate membership.';
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = (int)($_POST['member_id'] ?? 0);
    $planId = (int)($_POST['plan_id'] ?? 0);
    $start = $_POST['start_date'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if (!$memberId || !$planId || !valid_date($start)) {
        $error = 'Member, membership plan and a valid start date are required.';
    } else {
        // Get data from the database
        $stmt = $conn->prepare("SELECT duration_months FROM membership_plans WHERE id=? AND status='active'");
        // Add values to the prepared query
        $stmt->bind_param("i", $planId);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$plan) {
// Run the database query.
            $error = 'Please select an active membership plan.';
        } else {
            $startObj = new DateTime($start);
            $endObj = (clone $startObj)->modify('+' . ((int)$plan['duration_months']) . ' months')->modify('-1 day');
            $end = $endObj->format('Y-m-d');

            if ($id) {
                // Get data from the database
                $stmt = $conn->prepare("UPDATE memberships SET member_id=?,plan_id=?,start_date=?,end_date=?,status=IF(end_date<CURDATE(),'expired',status) WHERE id=?");
                // Add values to the prepared query
                $stmt->bind_param("iissi", $memberId, $planId, $start, $end, $id);
                // Run the database query
                if ($stmt->execute()) $message = 'Membership updated successfully.';
// Run the database query.
                else $error = 'Could not update membership.';
                $stmt->close();
            } else {
                // Get data from the database
                $stmt = $conn->prepare("INSERT INTO memberships(member_id,plan_id,start_date,end_date,status) VALUES(?,?,?,?,IF(?<CURDATE(),'expired','active'))");
                // Add values to the prepared query
                $stmt->bind_param("iisss", $memberId, $planId, $start, $end, $end);
                // Run the database query
                if ($stmt->execute()) $message = 'Membership assigned successfully.';
                else $error = 'Could not assign membership.';
                $stmt->close();
            }
        }
    }
}

$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare("SELECT id,member_id,plan_id,start_date,end_date,status FROM memberships WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* Automatically mark old memberships as expired. */
// Run the database query.
$conn->query("UPDATE memberships SET status='expired' WHERE status='active' AND end_date<CURDATE()");

// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id WHERE m.status='active' ORDER BY u.full_name");
// Get data from the database
$plans = $conn->query("SELECT id,name,duration_months,price FROM membership_plans WHERE status='active' ORDER BY price ASC");
// Get data from the database
$list = $conn->query("SELECT ms.id,ms.member_id,ms.plan_id,ms.start_date,ms.end_date,ms.status,
 u.full_name,p.name plan_name,p.price,p.duration_months
 FROM memberships ms
 JOIN members m ON m.id=ms.member_id
 JOIN users u ON u.id=m.user_id
 JOIN membership_plans p ON p.id=ms.plan_id
 ORDER BY ms.id DESC");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Memberships — FitTrack</title>
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

        .info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 18px
        }

        .info-card {
            background: #0d1c1e;
            border: 1px solid #182c2e;
            border-radius: 12px;
            padding: 17px
        }

        .info-card small {
            color: #718381;
            font-size: 9px
        }

        .info-card strong {
            display: block;
            font: 700 21px Manrope;
            margin-top: 7px
        }

        @media(max-width:750px) {

            .form-grid,
            .info {
                grid-template-columns: 1fr !important
            }
        }
    </style>
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
            <a class="nav active" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a>
            <div class="nav-label">MANAGEMENT</div>
            <a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a>
            <a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a>
            <a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a>
            <a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a>
            <div class="nav-label">ACCOUNT</div>
            <a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a>
            <a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Memberships</h1>
                    <p>Assign plans to members and track their membership periods.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            </div>

            <?php if ($message): ?><div style="background:#10362f;border:1px solid #1d6557;color:#66d7c0;padding:11px;border-radius:8px;font-size:10px;margin-bottom:14px"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:#351d1d;border:1px solid #653333;color:#e89b9b;padding:11px;border-radius:8px;font-size:10px;margin-bottom:14px"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <?php
// Run the database query.
            $activeCount = (int)$conn->query("SELECT COUNT(*) c FROM memberships WHERE status='active'")->fetch_assoc()['c'];
// Run the database query.
            $expiredCount = (int)$conn->query("SELECT COUNT(*) c FROM memberships WHERE status='expired'")->fetch_assoc()['c'];
// Run the database query.
            $cancelledCount = (int)$conn->query("SELECT COUNT(*) c FROM memberships WHERE status='cancelled'")->fetch_assoc()['c'];
            ?>
            <div class="info">
                <div class="info-card"><small>Active Memberships</small><strong><?php echo $activeCount; ?></strong></div>
                <div class="info-card"><small>Expired</small><strong><?php echo $expiredCount; ?></strong></div>
                <div class="info-card"><small>Cancelled</small><strong><?php echo $cancelledCount; ?></strong></div>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Membership' : 'Assign Membership'; ?></h2>
                    <?php if ($edit): ?><a class="btn secondary" href="memberships.php">Cancel</a><?php endif; ?>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
                    <label style="font-size:10px;color:#829391">Member
<!-- // Run the database query. -->
                        <select class="form-control" name="member_id" required>
<!-- // Run the database query. -->
                            <option value="">Select member</option>
                            <?php while ($m = $members->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ((int)($edit['member_id'] ?? 0) === (int)$m['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['full_name']); ?></option>
                            <?php endwhile; ?>
                        </select></label>
                    <label style="font-size:10px;color:#829391">Membership Plan
<!-- // Run the database query. -->
                        <select class="form-control" name="plan_id" required>
<!-- // Run the database query. -->
                            <option value="">Select plan</option>
                            <?php while ($p = $plans->fetch_assoc()): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ((int)($edit['plan_id'] ?? 0) === (int)$p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name']); ?> — PKR <?php echo number_format($p['price']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select></label>
                    <label style="font-size:10px;color:#829391">Start Date
                        <input class="form-control" name="start_date" type="date" value="<?php echo htmlspecialchars($edit['start_date'] ?? date('Y-m-d')); ?>" required></label>
<!-- // Run the database query. -->
                    <div style="display:flex;align-items:end;padding-bottom:1px"><button class="btn" type="submit"><?php echo $edit ? 'Update Membership' : 'Assign Membership'; ?></button></div>
                </form>
                <p style="font-size:9px;color:#607270;margin:13px 0 0">End date is automatically calculated from the selected plan duration.</p>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2>Membership Records</h2><span style="font-size:10px;color:#637572"><?php echo $list->num_rows; ?> records</span>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Plan</th>
                                <th>Price</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list->num_rows): while ($row = $list->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                                        <td>PKR <?php echo number_format($row['price']); ?></td>
                                        <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                                        <td><span class="badge" style="<?php echo $row['status'] === 'expired' ? 'background:#2c2820;color:#d4b46a' : ($row['status'] === 'cancelled' ? 'background:#351d1d;color:#e89b9b' : ''); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td>
                                            <div class="actions">
                                                <a class="action" href="memberships.php?edit=<?php echo $row['id']; ?>">Edit</a>
                                                <?php if ($row['status'] === 'active'): ?><a class="action" href="memberships.php?cancel=<?php echo $row['id']; ?>" onclick="return confirm('Cancel this membership?')">Cancel</a><?php elseif ($row['status'] === 'cancelled'): ?><a class="action" href="memberships.php?activate=<?php echo $row['id']; ?>">Activate</a><?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty">No memberships yet. Assign a plan to a member above.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
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
