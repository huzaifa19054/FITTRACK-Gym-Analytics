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
    $stmt = $conn->prepare("DELETE FROM members WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Member deleted successfully.';
    // Run the database query.
    else $error = 'Unable to delete member.';
    $stmt->close();
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $joined = $_POST['joined_date'] ?? date('Y-m-d');
    $weight = (float)($_POST['weight'] ?? 0);
    $height = (float)($_POST['height'] ?? 0);
    $fitness_goal = trim($_POST['fitness_goal'] ?? '');
    $trainer_id = (int)($_POST['trainer_id'] ?? 0);
    if ($trainer_id < 1) $trainer_id = null;
    $id = (int)($_POST['id'] ?? 0);
    $allowed_goals = ['weight_loss', 'muscle_gain', 'general_fitness'];
    if ($fitness_goal !== '' && !in_array($fitness_goal, $allowed_goals, true)) {
        $fitness_goal = '';
    }
    if ($name === '' || $email === '' || $username === '' || $fitness_goal === '') {
        $error = 'Name, email, username and fitness goal are required.';
    } elseif ($id) {
        // Get data from the database
        $stmt = $conn->prepare("UPDATE users u JOIN members m ON m.user_id=u.id SET u.full_name=?,u.email=?,u.username=?,u.phone=?,m.joined_date=?,m.height=?,m.weight=?,m.fitness_goal=?,m.trainer_id=? WHERE m.id=?");
        // Add values to the prepared query
        $stmt->bind_param("sssssddsii", $name, $email, $username, $phone, $joined, $height, $weight, $fitness_goal, $trainer_id, $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Member updated successfully.';
        // Run the database query.
        else $error = 'Could not update member. Email/username may already exist.';
        $stmt->close();
    } else {
        // Get data from the database
        $role = $conn->query("SELECT id FROM roles WHERE name='member' LIMIT 1")->fetch_assoc();
        $roleId = (int)$role['id'];
        $hash = password_hash('password', PASSWORD_DEFAULT);
        // Start a database transaction
        $conn->begin_transaction();
        try {
            // Get data from the database
            $stmt = $conn->prepare("INSERT INTO users(role_id,full_name,email,username,password,phone) VALUES(?,?,?,?,?,?)");
            // Add values to the prepared query
            $stmt->bind_param("isssss", $roleId, $name, $email, $username, $hash, $phone);
            $stmt->execute();
            $uid = $conn->insert_id;
            $stmt->close();
            // Get data from the database
            $stmt = $conn->prepare("INSERT INTO members(user_id,joined_date,height,weight,fitness_goal,trainer_id) VALUES(?,?,?,?,?,?)");
            // Add values to the prepared query
            $stmt->bind_param("isddsi", $uid, $joined, $height, $weight, $fitness_goal, $trainer_id);
            $stmt->execute();
            $stmt->close();
            // Save all database changes
            $conn->commit();
            $message = 'Member added successfully. Default password: password';
        } catch (Throwable $e) {
            // Undo the changes if something fails
            $conn->rollback();
            $error = 'Could not add member. Email/username may already exist.';
        }
    }
}
$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare("SELECT m.id,u.full_name,u.email,u.username,u.phone,m.joined_date,m.height,m.weight,m.fitness_goal,m.trainer_id FROM members m JOIN users u ON u.id=m.user_id WHERE m.id=?");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$search = trim($_GET['search'] ?? '');
$like = '%' . $search . '%';
if ($search !== '') {
    // Get data from the database
    $stmt = $conn->prepare("SELECT m.id,u.full_name,u.email,u.phone,m.joined_date,m.weight,m.fitness_goal,m.status FROM members m JOIN users u ON u.id=m.user_id WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.phone LIKE ? ORDER BY m.id DESC");
    // Add values to the prepared query
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $list = $stmt->get_result();
    // Run the database query.
} else $list = $conn->query("SELECT m.id,u.full_name,u.email,u.phone,m.joined_date,m.weight,m.fitness_goal,m.status FROM members m JOIN users u ON u.id=m.user_id ORDER BY m.id DESC");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Members — FitTrack</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="app">
        <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div>
            <a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav active" href="members.php"><span class="ico">◎</span>Members</a><a class="nav" href="trainers.php"><span class="ico">✦</span>Trainers</a><a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a><a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a><a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
        <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Members</h1>
                    <p>Add, edit and manage gym members.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            </div>
            <?php if ($message): ?><div style="background:#10362f;border:1px solid #1d6557;color:#66d7c0;padding:11px;border-radius:8px;font-size:10px;margin-bottom:14px"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:#351d1d;border:1px solid #653333;color:#e89b9b;padding:11px;border-radius:8px;font-size:10px;margin-bottom:14px"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Member' : 'Add New Member'; ?></h2><?php if ($edit): ?><a class="btn secondary" href="members.php">Cancel</a><?php endif; ?>
                </div>
                <!-- Form used to collect or submit data. -->
                <form method="post" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
                    <!-- // Run the database query. -->
                    <label style="font-size:10px;color:#829391">Trainer<select name="trainer_id" style="display:block;width:100%;margin-top:6px;padding:11px;border-radius:7px;border:1px solid #1a3032;background:#0a1719;color:#fff">
                            <!-- // Run the database query. -->
                            <option value="">Unassigned</option><?php $trainerOpts = $conn->query("SELECT t.id,u.full_name FROM trainers t JOIN users u ON u.id=t.user_id WHERE t.status='active' ORDER BY u.full_name");
                                                                if ($trainerOpts): while ($to = $trainerOpts->fetch_assoc()): ?><option value="<?php echo (int)$to['id']; ?>" <?php echo isset($edit['trainer_id']) && (int)$edit['trainer_id'] === (int)$to['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($to['full_name']); ?></option><?php endwhile;
                                                                                                                                                                                                                                                                                                                                            endif; ?>
                        </select></label>
                    <label style="font-size:10px;color:#829391">Fitness Goal<select name="fitness_goal" style="display:block;width:100%;margin-top:6px;padding:11px;border-radius:7px;border:1px solid #1a3032;background:#0a1719;color:#fff" required>
                            <option value="">Select Goal</option>
                            <option value="weight_loss" <?php echo (($edit['fitness_goal'] ?? '') === 'weight_loss') ? 'selected' : ''; ?>>Weight Loss</option>
                            <option value="muscle_gain" <?php echo (($edit['fitness_goal'] ?? '') === 'muscle_gain') ? 'selected' : ''; ?>>Muscle Gain</option>
                            <option value="general_fitness" <?php echo (($edit['fitness_goal'] ?? '') === 'general_fitness') ? 'selected' : ''; ?>>General Fitness</option>
                        </select></label>
                    <?php $fields = [['full_name', 'Full Name', 'text'], ['email', 'Email', 'email'], ['username', 'Username', 'text'], ['phone', 'Phone', 'text'], ['joined_date', 'Joined Date', 'date'], ['height', 'Height (cm)', 'number'], ['weight', 'Weight (kg)', 'number']];
                    foreach ($fields as $f): ?>
                        <label style="font-size:10px;color:#829391"><?php echo $f[1]; ?><input name="<?php echo $f[0]; ?>" type="<?php echo $f[2]; ?>" value="<?php echo htmlspecialchars($edit[$f[0]] ?? ($f[0] === 'joined_date' ? date('Y-m-d') : '')); ?>" <?php echo in_array($f[0], ['height', 'weight']) ? 'step="0.01"' : ''; ?> style="display:block;width:100%;margin-top:6px;padding:11px;border-radius:7px;border:1px solid #1a3032;background:#0a1719;color:#fff" required></label>
                    <?php endforeach; ?>
                    <!-- // Run the database query. -->
                    <div style="grid-column:1/-1"><button class="btn" type="submit"><?php echo $edit ? 'Update Member' : 'Add Member'; ?></button></div>
                </form>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2>All Members</h2><span style="font-size:10px;color:#637572"><?php echo $list->num_rows; ?> records</span>
                </div>
                <!-- Form used to collect or submit data. -->
                <form method="get" class="filter-bar">
                    <?php echo csrf_field(); ?><input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, username or phone..."><button class="btn" type="submit">Search</button><?php if ($search !== ''): ?><a class="btn secondary" href="members.php">Clear</a><?php endif; ?></form>
                <div class="table-wrap">
                    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Weight</th>
                                <th>Fitness Goal</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list->num_rows): while ($row = $list->fetch_assoc()): ?><tr>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($row['joined_date']); ?></td>
                                        <td><?php echo $row['weight'] ? htmlspecialchars($row['weight']) . ' kg' : '—'; ?></td>
                                        <td><?php
                                            $goal_labels = [
                                                'weight_loss' => 'Weight Loss',
                                                'muscle_gain' => 'Muscle Gain',
                                                'general_fitness' => 'General Fitness'
                                            ];
                                            echo htmlspecialchars($goal_labels[$row['fitness_goal'] ?? ''] ?? '—');
                                            ?></td>
                                        <td><span class="badge"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td>
                                            <!-- // Run the database query. -->
                                            <div class="actions"><a class="action" href="members.php?edit=<?php echo $row['id']; ?>">Edit</a><a class="action" href="members.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this member?')">Delete</a></div>
                                        </td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="8">
                                        <div class="empty">No members yet. Add the first member above.</div>
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