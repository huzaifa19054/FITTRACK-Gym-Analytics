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
if (isset($_GET['delete_assignment'])) {
    $id = (int)$_GET['delete_assignment'];
    // Get data from the database
    $stmt = $conn->prepare('DELETE FROM member_diets WHERE id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Diet assignment removed successfully.';
    else $error = 'Unable to remove assignment.';
    $stmt->close();
}

// Check if an action was requested
if (isset($_GET['status_assignment'])) {
    $id = (int)$_GET['status_assignment'];
    $status = $_GET['status'] ?? '';
    if (in_array($status, ['assigned', 'completed', 'paused'], true)) {
        // Get data from the database
        $stmt = $conn->prepare('UPDATE member_diets SET status=? WHERE id=?');
        // Add values to the prepared query
        $stmt->bind_param('si', $status, $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Assignment status updated.';
// Run the database query.
        else $error = 'Unable to update assignment.';
        $stmt->close();
    }
}

// Check if an action was requested
if (isset($_GET['delete_plan'])) {
    $id = (int)$_GET['delete_plan'];
    // Get data from the database
    $check = $conn->prepare('SELECT COUNT(*) c FROM member_diets WHERE diet_plan_id=?');
    $check->bind_param('i', $id);
    $check->execute();
    $used = (int)$check->get_result()->fetch_assoc()['c'];
    $check->close();
    if ($used > 0) {
        $error = 'This diet plan is assigned to members and cannot be deleted. Remove its assignments first.';
    } else {
        // Get data from the database
        $stmt = $conn->prepare('DELETE FROM diet_plans WHERE id=?');
        // Add values to the prepared query
        $stmt->bind_param('i', $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Diet plan deleted successfully.';
// Run the database query.
        else $error = 'Unable to delete diet plan.';
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Check which form action was selected
    if ($action === 'save_plan') {
        $id = (int)($_POST['id'] ?? 0);
        $trainer_id = (int)($_POST['trainer_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $goal = trim($_POST['goal'] ?? '');
        $calories = (int)($_POST['calories'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        if ($trainer_id < 1 || $name === '') $error = 'Trainer and diet plan name are required.';
        elseif ($calories < 0) $error = 'Calories cannot be negative.';
        elseif ($id) {
            // Get data from the database
            $stmt = $conn->prepare('UPDATE diet_plans SET trainer_id=?,name=?,goal=?,calories=?,description=? WHERE id=?');
            // Add values to the prepared query
            $stmt->bind_param('issisi', $trainer_id, $name, $goal, $calories, $description, $id);
            // Run the database query
            if ($stmt->execute()) $message = 'Diet plan updated successfully.';
// Run the database query.
            else $error = 'Could not update diet plan.';
            $stmt->close();
        } else {
            // Get data from the database
            $stmt = $conn->prepare('INSERT INTO diet_plans(trainer_id,name,goal,calories,description) VALUES(?,?,?,?,?)');
            // Add values to the prepared query
            $stmt->bind_param('issis', $trainer_id, $name, $goal, $calories, $description);
            // Run the database query
            if ($stmt->execute()) $message = 'Diet plan created successfully.';
            else $error = 'Could not create diet plan.';
            $stmt->close();
        }
    }

    // Check which form action was selected
    if ($action === 'assign_plan') {
        $member_id = (int)($_POST['member_id'] ?? 0);
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        $assigned_date = $_POST['assigned_date'] ?? date('Y-m-d');
        if ($member_id < 1 || $plan_id < 1 || !$assigned_date) $error = 'Member, diet plan and assigned date are required.';
        else {
            $status = 'assigned';
            // Get data from the database
            $stmt = $conn->prepare('INSERT INTO member_diets(member_id,diet_plan_id,assigned_date,status) VALUES(?,?,?,?)');
            // Add values to the prepared query
            $stmt->bind_param('iiss', $member_id, $plan_id, $assigned_date, $status);
            // Run the database query
            if ($stmt->execute()) $message = 'Diet plan assigned to member.';
            else $error = 'Could not assign diet plan.';
            $stmt->close();
        }
    }
}

$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare('SELECT id,trainer_id,name,goal,calories,description FROM diet_plans WHERE id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get data from the database
$trainers = $conn->query("SELECT t.id,u.full_name FROM trainers t JOIN users u ON u.id=t.user_id WHERE t.status='active' ORDER BY u.full_name");
// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id WHERE m.status='active' ORDER BY u.full_name");
// Get data from the database
$plans = $conn->query("SELECT p.id,p.name,p.goal,p.calories,p.description,u.full_name AS trainer_name,(SELECT COUNT(*) FROM member_diets md WHERE md.diet_plan_id=p.id) assigned_count FROM diet_plans p JOIN trainers t ON t.id=p.trainer_id JOIN users u ON u.id=t.user_id ORDER BY p.id DESC");
// Get data from the database
$assignments = $conn->query("SELECT md.id,md.assigned_date,md.status,u.full_name member_name,p.name plan_name,p.calories FROM member_diets md JOIN members m ON m.id=md.member_id JOIN users u ON u.id=m.user_id JOIN diet_plans p ON p.id=md.diet_plan_id ORDER BY md.assigned_date DESC,md.id DESC");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Diet Plans — FitTrack</title>
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

        .plan-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 13px
        }

        .plan-card {
            background: #0d1c1e;
            border: 1px solid #182c2e;
            border-radius: 12px;
            padding: 18px;
            transition: .25s
        }

        .plan-card:hover {
            transform: translateY(-3px);
            border-color: #24514a
        }

        .plan-card h3 {
            font: 700 16px Manrope;
            margin: 0 0 7px
        }

        .plan-card p {
            font-size: 10px;
            color: #728481;
            line-height: 1.6;
            min-height: 32px
        }

        .meta {
            font-size: 9px;
            color: #647673;
            margin: 8px 0 13px
        }

        .tag {
            display: inline-block;
            padding: 4px 7px;
            border-radius: 20px;
            background: #12352f;
            color: #54d5bc;
            font-size: 8px
        }

        .pill-assigned {
            color: #62d8bf
        }

        .pill-completed {
            color: #86d58a
        }

        .pill-paused {
            color: #d5b66c
        }

        .cal {
            font: 800 20px Manrope;
            margin: 9px 0;
            color: #fff
        }

        .cal small {
            font: 500 9px Inter;
            color: #728481
        }

        @media(max-width:900px) {
            .plan-grid {
                grid-template-columns: 1fr 1fr
            }
        }

        @media(max-width:600px) {
            .plan-grid {
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
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav active" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Diet Plans</h1>
                    <p>Create nutrition plans, set calorie targets and assign them to members.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?> · Administrator</div>
            </div>
            <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Diet Plan' : 'Create Diet Plan'; ?></h2><?php if ($edit): ?><a class="btn secondary" href="diet-plans.php">Cancel</a><?php endif; ?>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
<!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save_plan"><?php if ($edit): ?><input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>"><?php endif; ?><label>Plan Name<input class="form-control" name="name" value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" placeholder="e.g. Fat Loss Diet" required></label><label>Trainer<select class="form-control" name="trainer_id" required>
<!-- // Run the database query. -->
                            <option value="">Select trainer</option><?php if ($trainers): while ($t = $trainers->fetch_assoc()): ?><option value="<?php echo $t['id']; ?>" <?php echo isset($edit['trainer_id']) && (int)$edit['trainer_id'] === (int)$t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['full_name']); ?></option><?php endwhile;
                                                                                                                                                                                                                                                                                                                            endif; ?>
<!-- // Run the database query. -->
                        </select></label><label>Goal<input class="form-control" name="goal" value="<?php echo htmlspecialchars($edit['goal'] ?? ''); ?>" placeholder="e.g. Weight Loss / Muscle Gain"></label><label>Daily Calories<input class="form-control" type="number" name="calories" min="0" value="<?php echo htmlspecialchars($edit['calories'] ?? '2000'); ?>" placeholder="2000"></label><label style="grid-column:1/-1">Description<textarea class="form-control" name="description" rows="4" placeholder="Breakfast, lunch, dinner guidance and nutrition notes..."><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea></label>
<!-- // Run the database query. -->
                    <div style="grid-column:1/-1"><button class="btn" type="submit"><?php echo $edit ? 'Update Diet Plan' : 'Create Diet Plan'; ?></button></div>
                </form>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2>Diet Plan Library</h2><span style="font-size:10px;color:#637572"><?php echo $plans ? $plans->num_rows : 0; ?> plans</span>
                </div>
                <div class="plan-grid"><?php if ($plans && $plans->num_rows): while ($p = $plans->fetch_assoc()): ?><div class="plan-card"><span class="tag"><?php echo htmlspecialchars($p['goal'] ?: 'General Nutrition'); ?></span>
                                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                                <div class="cal"><?php echo number_format((int)$p['calories']); ?> <small>kcal / day</small></div>
                                <p><?php echo htmlspecialchars($p['description'] ?: 'No description added yet.'); ?></p>
                                <div class="meta">Trainer: <?php echo htmlspecialchars($p['trainer_name']); ?> · <?php echo (int)$p['assigned_count']; ?> assignment(s)</div>
<!-- // Run the database query. -->
                                <div class="actions"><a class="action" href="diet-plans.php?edit=<?php echo $p['id']; ?>">Edit</a><a class="action" href="diet-plans.php?delete_plan=<?php echo $p['id']; ?>" onclick="return confirm('Delete this diet plan?')">Delete</a></div>
                            </div><?php endwhile;
                                        else: ?><div class="empty" style="grid-column:1/-1">No diet plans created yet.</div><?php endif; ?></div>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2>Assign Diet to Member</h2>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
<!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="assign_plan"><label>Member<select class="form-control" name="member_id" required>
<!-- // Run the database query. -->
                            <option value="">Select member</option><?php if ($members): while ($m = $members->fetch_assoc()): ?><option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option><?php endwhile;
                                                                                                                                                                                                                        endif; ?>
<!-- // Run the database query. -->
                        </select></label><label>Diet Plan<select class="form-control" name="plan_id" required>
<!-- // Run the database query. -->
                            <option value="">Select diet plan</option><?php $opts = $conn->query('SELECT id,name FROM diet_plans ORDER BY name');
                                                                        if ($opts): while ($o = $opts->fetch_assoc()): ?><option value="<?php echo $o['id']; ?>"><?php echo htmlspecialchars($o['name']); ?></option><?php endwhile;
                                                                                                                                                                                                                                                                                    endif; ?>
<!-- // Run the database query. -->
                        </select></label><label>Assigned Date<input class="form-control" type="date" name="assigned_date" value="<?php echo date('Y-m-d'); ?>" required></label>
                    <div style="display:flex;align-items:end"><button class="btn" type="submit">Assign Diet</button></div>
                </form>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2>Member Diet Assignments</h2><span style="font-size:10px;color:#637572"><?php echo $assignments ? $assignments->num_rows : 0; ?> records</span>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Diet Plan</th>
                                <th>Calories</th>
                                <th>Assigned Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($assignments && $assignments->num_rows): while ($a = $assignments->fetch_assoc()): ?><tr>
                                        <td><strong><?php echo htmlspecialchars($a['member_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($a['plan_name']); ?></td>
                                        <td><?php echo number_format((int)$a['calories']); ?> kcal</td>
                                        <td><?php echo date('d M Y', strtotime($a['assigned_date'])); ?></td>
                                        <td><span class="badge pill-<?php echo htmlspecialchars($a['status']); ?>" style="background:transparent;padding:0"><?php echo htmlspecialchars(ucfirst($a['status'])); ?></span></td>
                                        <td>
                                            <div class="actions"><?php if ($a['status'] !== 'completed'): ?><a class="action" href="diet-plans.php?status_assignment=<?php echo $a['id']; ?>&status=completed">Complete</a><?php endif; ?><?php if ($a['status'] !== 'paused'): ?><a class="action" href="diet-plans.php?status_assignment=<?php echo $a['id']; ?>&status=paused">Pause</a><?php endif; ?><?php if ($a['status'] !== 'assigned'): ?><a class="action" href="diet-plans.php?status_assignment=<?php echo $a['id']; ?>&status=assigned">Assign</a><?php endif; ?><a class="action" href="diet-plans.php?delete_assignment=<?php echo $a['id']; ?>" onclick="return confirm('Remove this assignment?')">Remove</a></div>
                                        </td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="6">
                                        <div class="empty">No member diet assignments yet.</div>
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
