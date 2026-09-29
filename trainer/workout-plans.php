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
$error = '';
$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
  $id = (int)$_GET['edit'];
  // Get data from the database
  $edit = $conn->query("SELECT * FROM workout_plans WHERE id=$id AND trainer_id=$tid LIMIT 1")->fetch_assoc();
}
// Check if an action was requested
if (isset($_GET['delete'])) {
// Run the database query.
  $id = (int)$_GET['delete'];
  // Get data from the database
  $own = $conn->query("SELECT id FROM workout_plans WHERE id=$id AND trainer_id=$tid LIMIT 1")->fetch_assoc();
  if (!$own) {
    $error = 'Workout plan not found.';
  } else {
// Run the database query.
    $used = (int)($conn->query("SELECT COUNT(*) v FROM member_workouts WHERE workout_plan_id=$id")->fetch_assoc()['v'] ?? 0);
    if ($used) {
      $error = 'This plan is already assigned and cannot be deleted.';
    } else {
      // Get data from the database
      $q = $conn->prepare('DELETE FROM workout_plans WHERE id=? AND trainer_id=?');
      // Add values to the prepared query
      $q->bind_param('ii', $id, $tid);
      $q->execute();
      $msg = 'Workout plan deleted.';
    }
  }
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $goal = trim($_POST['goal'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  if (!$name) {
    $error = 'Plan name is required.';
  } elseif ($id) {
    // Get data from the database
    $q = $conn->prepare('UPDATE workout_plans SET name=?,goal=?,description=? WHERE id=? AND trainer_id=?');
    // Add values to the prepared query
    $q->bind_param('sssii', $name, $goal, $desc, $id, $tid);
    $q->execute();
    $msg = 'Workout plan updated successfully.';
    $edit = null;
  } else {
    // Get data from the database
    $q = $conn->prepare('INSERT INTO workout_plans(trainer_id,name,goal,description) VALUES(?,?,?,?)');
    // Add values to the prepared query
    $q->bind_param('isss', $tid, $name, $goal, $desc);
    $q->execute();
    $msg = 'Workout plan created successfully.';
  }
}
// Get data from the database
$rows = $conn->query("SELECT w.*, (SELECT COUNT(*) FROM member_workouts mw WHERE mw.workout_plan_id=w.id) assigned_count FROM workout_plans w WHERE w.trainer_id=$tid ORDER BY w.id DESC");
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>FitTrack — Workout Plans</title>
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
      <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="members.php"><span class="ico">◎</span>My Members</a><a class="nav active" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav" href="progress.php"><span class="ico">↗</span>Progress</a>
      <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
    </aside>
    <!-- Main page content. -->
    <main class="main dashboard-main">
    <!-- Main page content. -->
      <main class="main dashboard-main">
        <div class="top dashboard-top">
          <div>
            <div class="eyebrow">TRAINER / WORKOUTS</div>
            <h1>Workout Plans</h1>
            <p>Create, update and manage plans for your members.</p>
          </div>
        </div><?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?><?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><div class="dashboard-grid two-col">
    <!-- Main page section. -->
          <section class="panel">
            <div class="panel-head">
              <div><span class="panel-kicker"><?php echo $edit ? 'EDIT' : 'CREATE'; ?></span>
<!-- // Run the database query. -->
                <h2><?php echo $edit ? 'Update workout plan' : 'New workout plan'; ?></h2>
              </div><?php if ($edit): ?><a class="panel-link" href="workout-plans.php">Cancel</a><?php endif; ?>
            </div>
    <!-- Form used to collect or submit data. -->
            <form method="post" class="form-grid">
              <?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)($edit['id'] ?? 0); ?>"><label>Name<input name="name" value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" required></label><label>Goal<input name="goal" value="<?php echo htmlspecialchars($edit['goal'] ?? ''); ?>"></label><label style="grid-column:1/-1">Description<textarea name="description" rows="5"><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea></label><button class="btn" type="submit"><?php echo $edit ? 'Save Changes' : 'Create Plan'; ?></button></form>
          </section>
    <!-- Main page section. -->
          <section class="panel">
            <div class="panel-head">
              <div><span class="panel-kicker">YOUR PLANS</span>
                <h2>Workout library</h2>
              </div><span class="panel-badge"><?php echo $rows ? $rows->num_rows : 0; ?> plans</span>
            </div>
            <div class="table-wrap">
    <!-- Table used to display records. -->
              <table>
                <thead>
                  <tr>
                    <th>Plan</th>
                    <th>Goal</th>
                    <th>Assigned</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody><?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?><tr>
                        <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($r['goal'] ?: 'General fitness'); ?></td>
                        <td><?php echo (int)$r['assigned_count']; ?></td>
<!-- // Run the database query. -->
                        <td class="actions"><a class="action" href="?edit=<?php echo (int)$r['id']; ?>">Edit</a><?php if (!(int)$r['assigned_count']): ?><a class="action" href="?delete=<?php echo (int)$r['id']; ?>" onclick="return confirm('Delete this workout plan?')">Delete</a><?php endif; ?></td>
                      </tr><?php endwhile;
                        else: ?><tr>
                      <td colspan="4" class="empty">No workout plans yet.</td>
                    </tr><?php endif; ?></tbody>
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
