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
if (isset($_GET['delete_exercise'])) {
    $id = (int)$_GET['delete_exercise'];
    // Get data from the database
    $stmt = $conn->prepare('DELETE FROM workout_exercises WHERE id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Exercise removed from the workout plan.';
    else $error = 'Unable to remove exercise.';
    $stmt->close();
}

// Check if an action was requested
if (isset($_GET['delete_plan'])) {
    $id = (int)$_GET['delete_plan'];
    // Get data from the database
    $check = $conn->prepare('SELECT COUNT(*) c FROM member_workouts WHERE workout_plan_id=?');
    $check->bind_param('i', $id);
    $check->execute();
    $used = (int)$check->get_result()->fetch_assoc()['c'];
    $check->close();
    if ($used > 0) {
        $error = 'This workout plan is assigned to members and cannot be deleted. Remove its assignments first.';
    } else {
        // Get data from the database
        $stmt = $conn->prepare('DELETE FROM workout_plans WHERE id=?');
        // Add values to the prepared query
        $stmt->bind_param('i', $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Workout plan deleted successfully.';
// Run the database query.
        else $error = 'Unable to delete workout plan.';
        $stmt->close();
    }
}

// Check if an action was requested
if (isset($_GET['delete_assignment'])) {
    $id = (int)$_GET['delete_assignment'];
    // Get data from the database
    $stmt = $conn->prepare('DELETE FROM member_workouts WHERE id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Workout assignment removed.';
    else $error = 'Unable to remove assignment.';
    $stmt->close();
}

// Check if an action was requested
if (isset($_GET['status_assignment'])) {
    $id = (int)$_GET['status_assignment'];
    $status = $_GET['status'] ?? '';
    if (in_array($status, ['assigned', 'completed', 'paused'], true)) {
        // Get data from the database
        $stmt = $conn->prepare('UPDATE member_workouts SET status=? WHERE id=?');
        // Add values to the prepared query
        $stmt->bind_param('si', $status, $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Assignment status updated.';
// Run the database query.
        else $error = 'Unable to update assignment.';
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
        $description = trim($_POST['description'] ?? '');
        if ($trainer_id < 1 || $name === '') $error = 'Trainer and workout plan name are required.';
        elseif ($id) {
            // Get data from the database
            $stmt = $conn->prepare('UPDATE workout_plans SET trainer_id=?,name=?,goal=?,description=? WHERE id=?');
            // Add values to the prepared query
            $stmt->bind_param('isssi', $trainer_id, $name, $goal, $description, $id);
            // Run the database query
            if ($stmt->execute()) $message = 'Workout plan updated successfully.';
// Run the database query.
            else $error = 'Could not update workout plan.';
            $stmt->close();
        } else {
            // Get data from the database
            $stmt = $conn->prepare('INSERT INTO workout_plans(trainer_id,name,goal,description) VALUES(?,?,?,?)');
            // Add values to the prepared query
            $stmt->bind_param('isss', $trainer_id, $name, $goal, $description);
            // Run the database query
            if ($stmt->execute()) $message = 'Workout plan created successfully.';
            else $error = 'Could not create workout plan.';
            $stmt->close();
        }
    }

    // Check which form action was selected
    if ($action === 'add_exercise') {
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        $exercise = trim($_POST['exercise_name'] ?? '');
        $sets = max(1, (int)($_POST['sets_count'] ?? 3));
        $reps = trim($_POST['reps'] ?? '10');
        $rest = max(0, (int)($_POST['rest_seconds'] ?? 60));
        $notes = trim($_POST['notes'] ?? '');
        if ($plan_id < 1 || $exercise === '') $error = 'Workout plan and exercise name are required.';
        else {
            // Get data from the database
            $stmt = $conn->prepare('INSERT INTO workout_exercises(workout_plan_id,exercise_name,sets_count,reps,rest_seconds,notes) VALUES(?,?,?,?,?,?)');
            // Add values to the prepared query
            $stmt->bind_param('isisss', $plan_id, $exercise, $sets, $reps, $rest, $notes);
            // Run the database query
            if ($stmt->execute()) $message = 'Exercise added to the plan.';
            else $error = 'Could not add exercise.';
            $stmt->close();
        }
    }

    // Check which form action was selected
    if ($action === 'assign_plan') {
        $member_id = (int)($_POST['member_id'] ?? 0);
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        $assigned_date = $_POST['assigned_date'] ?? date('Y-m-d');
        if ($member_id < 1 || $plan_id < 1 || !$assigned_date) $error = 'Member, workout plan and assigned date are required.';
        else {
            // Add the workout plan to the selected member
            // Get data from the database
            $stmt = $conn->prepare(
// Run the database query.
                'INSERT INTO member_workouts(member_id, workout_plan_id, assigned_date, status)
     VALUES(?, ?, ?, ?)'
            );

            // Default status is assigned
            $status = 'assigned';

            // i = integer, s = string
            // Add values to the prepared query
            $stmt->bind_param(
                'iiss',
                $member_id,
                $plan_id,
                $assigned_date,
                $status
            );
            // Run the database query
            if ($stmt->execute()) $message = 'Workout plan assigned to member.';
            else $error = 'Could not assign workout plan.';
            $stmt->close();
        }
    }
}

$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare('SELECT id,trainer_id,name,goal,description FROM workout_plans WHERE id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$selectedPlan = (int)($_GET['plan'] ?? ($edit['id'] ?? 0));
// Get data from the database
$trainers = $conn->query("SELECT t.id,u.full_name FROM trainers t JOIN users u ON u.id=t.user_id WHERE t.status='active' ORDER BY u.full_name");
// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id WHERE m.status='active' ORDER BY u.full_name");
// Get data from the database
$plans = $conn->query("SELECT p.id,p.name,p.goal,p.description,u.full_name AS trainer_name,(SELECT COUNT(*) FROM workout_exercises e WHERE e.workout_plan_id=p.id) exercise_count,(SELECT COUNT(*) FROM member_workouts mw WHERE mw.workout_plan_id=p.id) assigned_count FROM workout_plans p JOIN trainers t ON t.id=p.trainer_id JOIN users u ON u.id=t.user_id ORDER BY p.id DESC");
$selected = null;
$exercises = null;
if ($selectedPlan) {
    // Get data from the database
    $stmt = $conn->prepare('SELECT p.id,p.trainer_id,p.name,p.goal,p.description,u.full_name trainer_name FROM workout_plans p JOIN trainers t ON t.id=p.trainer_id JOIN users u ON u.id=t.user_id WHERE p.id=?');
    // Add values to the prepared query
    $stmt->bind_param('i', $selectedPlan);
    $stmt->execute();
    $selected = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($selected) {
        // Get data from the database
        $stmt = $conn->prepare('SELECT id,exercise_name,sets_count,reps,rest_seconds,notes FROM workout_exercises WHERE workout_plan_id=? ORDER BY id');
        // Add values to the prepared query
        $stmt->bind_param('i', $selectedPlan);
        $stmt->execute();
        $exercises = $stmt->get_result();
        $stmt->close();
    }
}
// Get data from the database
$assignments = $conn->query("SELECT mw.id,mw.assigned_date,mw.status,u.full_name member_name,p.name plan_name FROM member_workouts mw JOIN members m ON m.id=mw.member_id JOIN users u ON u.id=m.user_id JOIN workout_plans p ON p.id=mw.workout_plan_id ORDER BY mw.assigned_date DESC,mw.id DESC");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Workout Plans — FitTrack</title>
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

        .detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 18px
        }

        .exercise-row {
            display: grid;
            grid-template-columns: 1.5fr .5fr .6fr .6fr 1.3fr auto;
            gap: 8px;
            align-items: center;
            border-bottom: 1px solid #18292b;
            padding: 10px 0;
            font-size: 10px;
            color: #bdc9c7
        }

        .mini {
            font-size: 9px;
            color: #71817f
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

        @media(max-width:900px) {

            .plan-grid,
            .detail-grid {
                grid-template-columns: 1fr
            }

            .exercise-row {
                min-width: 700px
            }

            .table-wrap {
                overflow: auto
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
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav active" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Workout Plans</h1>
                    <p>Create training plans, add exercises and assign them to members.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            </div>
            <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Workout Plan' : 'Create Workout Plan'; ?></h2><?php if ($edit): ?><a class="btn secondary" href="workout-plans.php">Cancel</a><?php endif; ?>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
<!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save_plan"><?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?><label>Trainer<select class="form-control" name="trainer_id" required>
<!-- // Run the database query. -->
                            <option value="">Select trainer</option><?php if ($trainers): while ($t = $trainers->fetch_assoc()): ?><option value="<?php echo $t['id']; ?>" <?php echo ((int)($edit['trainer_id'] ?? 0) == (int)$t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['full_name']); ?></option><?php endwhile;
                                                                                                                                                                                                                                                                                                                endif; ?>
<!-- // Run the database query. -->
                        </select></label><label>Plan Name<input class="form-control" name="name" placeholder="e.g. Muscle Gain — Beginner" value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" required></label><label>Goal<input class="form-control" name="goal" placeholder="e.g. Muscle Gain, Weight Loss" value="<?php echo htmlspecialchars($edit['goal'] ?? ''); ?>"></label><label>Description<input class="form-control" name="description" placeholder="Short plan description" value="<?php echo htmlspecialchars($edit['description'] ?? ''); ?>"></label>
<!-- // Run the database query. -->
                    <div style="grid-column:1/-1"><button class="btn" type="submit"><?php echo $edit ? 'Update Plan' : 'Create Plan'; ?></button></div>
                </form>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2>Workout Plans</h2><span style="font-size:10px;color:#637572"><?php echo $plans ? $plans->num_rows : 0; ?> plans</span>
                </div>
                <div class="plan-grid"><?php if ($plans && $plans->num_rows): while ($p = $plans->fetch_assoc()): ?><div class="plan-card">
                                <h3><?php echo htmlspecialchars($p['name']); ?></h3><span class="tag"><?php echo htmlspecialchars($p['goal'] ?: 'General Fitness'); ?></span>
                                <p><?php echo htmlspecialchars($p['description'] ?: 'No description added.'); ?></p>
                                <div class="meta">Trainer: <?php echo htmlspecialchars($p['trainer_name']); ?><br><?php echo (int)$p['exercise_count']; ?> exercises · <?php echo (int)$p['assigned_count']; ?> assignments</div>
<!-- // Run the database query. -->
                                <div class="actions"><a class="action" href="workout-plans.php?plan=<?php echo $p['id']; ?>">Manage</a><a class="action" href="workout-plans.php?edit=<?php echo $p['id']; ?>">Edit</a><?php if ((int)$p['assigned_count'] === 0): ?><a class="action" href="workout-plans.php?delete_plan=<?php echo $p['id']; ?>" onclick="return confirm('Delete this workout plan?')">Delete</a><?php endif; ?></div>
                            </div><?php endwhile;
                                        else: ?><div class="empty" style="grid-column:1/-1">No workout plans yet. Create the first plan above.</div><?php endif; ?></div>
            </div>

            <?php if ($selected): ?><div class="detail-grid">
                    <div class="section">
                        <div class="section-head">
                            <div>
                                <h2><?php echo htmlspecialchars($selected['name']); ?></h2>
                                <p style="font-size:10px;color:#70817f;margin:5px 0 0">Trainer: <?php echo htmlspecialchars($selected['trainer_name']); ?></p>
                            </div>
                        </div>
                        <div class="table-wrap">
                            <div class="exercise-row" style="color:#607270;font-weight:700">
                                <div>Exercise</div>
                                <div>Sets</div>
                                <div>Reps</div>
                                <div>Rest</div>
                                <div>Notes</div>
                                <div></div>
                            </div><?php if ($exercises && $exercises->num_rows): while ($e = $exercises->fetch_assoc()): ?><div class="exercise-row">
                                        <div><?php echo htmlspecialchars($e['exercise_name']); ?></div>
                                        <div><?php echo (int)$e['sets_count']; ?></div>
                                        <div><?php echo htmlspecialchars($e['reps']); ?></div>
                                        <div><?php echo (int)$e['rest_seconds']; ?>s</div>
                                        <div class="mini"><?php echo htmlspecialchars($e['notes'] ?: '—'); ?></div>
                                        <div><a class="action" href="workout-plans.php?plan=<?php echo $selectedPlan; ?>&delete_exercise=<?php echo $e['id']; ?>" onclick="return confirm('Remove this exercise?')">Remove</a></div>
                                    </div><?php endwhile;
                                    else: ?><div class="empty">No exercises added yet.</div><?php endif; ?>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-head">
                            <h2>Add Exercise</h2>
                        </div>
    <!-- Form used to collect or submit data. -->
                        <form method="post" class="form-grid" style="grid-template-columns:1fr 1fr">
                            <?php echo csrf_field(); ?><input type="hidden" name="action" value="add_exercise"><input type="hidden" name="plan_id" value="<?php echo $selectedPlan; ?>"><label style="grid-column:1/-1">Exercise Name<input class="form-control" name="exercise_name" placeholder="e.g. Bench Press" required></label><label>Sets<input class="form-control" type="number" name="sets_count" min="1" value="3"></label><label>Reps<input class="form-control" name="reps" value="10"></label><label>Rest (seconds)<input class="form-control" type="number" name="rest_seconds" min="0" value="60"></label><label>Notes<input class="form-control" name="notes" placeholder="Optional notes"></label>
                            <div style="grid-column:1/-1"><button class="btn" type="submit">+ Add Exercise</button></div>
                        </form>
                    </div>
                </div><?php endif; ?>

            <div class="section">
                <div class="section-head">
                    <h2>Assign Workout to Member</h2>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
<!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="assign_plan"><label>Member<select class="form-control" name="member_id" required>
<!-- // Run the database query. -->
                            <option value="">Select member</option><?php if ($members): while ($m = $members->fetch_assoc()): ?><option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option><?php endwhile;
                                                                                                                                                                                                                            endif; ?>
<!-- // Run the database query. -->
                        </select></label><label>Workout Plan<select class="form-control" name="plan_id" required>
<!-- // Run the database query. -->
                            <option value="">Select plan</option><?php $planOpts = $conn->query('SELECT id,name FROM workout_plans ORDER BY name');
                                                                    if ($planOpts): while ($po = $planOpts->fetch_assoc()): ?><option value="<?php echo $po['id']; ?>" <?php echo $selectedPlan == (int)$po['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($po['name']); ?></option><?php endwhile;
                                                                                                                                                                                                                                                                                        endif; ?>
<!-- // Run the database query. -->
                        </select></label><label>Assigned Date<input class="form-control" type="date" name="assigned_date" value="<?php echo date('Y-m-d'); ?>" required></label>
                    <div style="display:flex;align-items:end"><button class="btn" type="submit">Assign Plan</button></div>
                </form>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2>Member Assignments</h2><span style="font-size:10px;color:#637572"><?php echo $assignments ? $assignments->num_rows : 0; ?> records</span>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Workout Plan</th>
                                <th>Assigned Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($assignments && $assignments->num_rows): while ($a = $assignments->fetch_assoc()): ?><tr>
                                        <td><strong><?php echo htmlspecialchars($a['member_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($a['plan_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($a['assigned_date'])); ?></td>
                                        <td><span class="badge <?php echo 'pill-' . htmlspecialchars($a['status']); ?>" style="background:transparent;padding:0"><?php echo htmlspecialchars(ucfirst($a['status'])); ?></span></td>
                                        <td>
                                            <div class="actions"><?php if ($a['status'] !== 'completed'): ?><a class="action" href="workout-plans.php?status_assignment=<?php echo $a['id']; ?>&status=completed">Complete</a><?php endif; ?><?php if ($a['status'] !== 'paused'): ?><a class="action" href="workout-plans.php?status_assignment=<?php echo $a['id']; ?>&status=paused">Pause</a><?php endif; ?><?php if ($a['status'] !== 'assigned'): ?><a class="action" href="workout-plans.php?status_assignment=<?php echo $a['id']; ?>&status=assigned">Assign</a><?php endif; ?><a class="action" href="workout-plans.php?delete_assignment=<?php echo $a['id']; ?>" onclick="return confirm('Remove this assignment?')">Remove</a></div>
                                        </td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="5">
                                        <div class="empty">No member workout assignments yet.</div>
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
