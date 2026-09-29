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
    $stmt = $conn->prepare("DELETE FROM trainers WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Trainer deleted successfully.';
// Run the database query.
    else $error = 'Unable to delete trainer.';
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $experience = (int)($_POST['experience_years'] ?? 0);
    $joining = $_POST['joining_date'] ?? date('Y-m-d');
    $id = (int)($_POST['id'] ?? 0);

    if ($name === '' || $email === '' || $username === '') {
        $error = 'Name, email and username are required.';
    } elseif ($id) {
        // Get data from the database
        $stmt = $conn->prepare("UPDATE users u JOIN trainers t ON t.user_id=u.id
            SET u.full_name=?,u.email=?,u.username=?,u.phone=?,
                t.specialization=?,t.experience_years=?,t.joining_date=?
            WHERE t.id=?");
        // Add values to the prepared query
        $stmt->bind_param("sssssisi", $name, $email, $username, $phone, $specialization, $experience, $joining, $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Trainer updated successfully.';
// Run the database query.
        else $error = 'Could not update trainer. Email/username may already exist.';
        $stmt->close();
    } else {
        // Get data from the database
        $role = $conn->query("SELECT id FROM roles WHERE name='trainer' LIMIT 1")->fetch_assoc();
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
            $stmt = $conn->prepare("INSERT INTO trainers(user_id,specialization,experience_years,joining_date) VALUES(?,?,?,?)");
            // Add values to the prepared query
            $stmt->bind_param("isis", $uid, $specialization, $experience, $joining);
            $stmt->execute();
            $stmt->close();

            // Save all database changes
            $conn->commit();
            $message = 'Trainer added successfully. Default password: password';
        } catch (Throwable $e) {
            // Undo the changes if something fails
            $conn->rollback();
            $error = 'Could not add trainer. Email/username may already exist.';
        }
    }
}

$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare("SELECT t.id,u.full_name,u.email,u.username,u.phone,t.specialization,t.experience_years,t.joining_date,t.status
        FROM trainers t JOIN users u ON u.id=t.user_id WHERE t.id=?");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get data from the database
$list = $conn->query("SELECT t.id,u.full_name,u.email,u.phone,t.specialization,t.experience_years,t.joining_date,t.status
    FROM trainers t JOIN users u ON u.id=t.user_id ORDER BY t.id DESC");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Trainers — FitTrack</title>
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
            <a class="nav active" href="trainers.php"><span class="ico">✦</span>Trainers</a>
            <a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a>
            <a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a><a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
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
                    <h1>Trainers</h1>
                    <p>Add, edit and manage gym trainers.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            </div>

            <?php if ($message): ?>
                <div style="background:#10362f;border:1px solid #1d6557;color:#66d7c0;padding:11px;border-radius:8px;font-size:10px;margin-bottom:14px">
                    <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background:#351d1d;border:1px solid #653333;color:#e89b9b;padding:11px;border-radius:8px;font-size:10px;margin-bottom:14px">
                    <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Trainer' : 'Add New Trainer'; ?></h2>
                    <?php if ($edit): ?><a class="btn secondary" href="trainers.php">Cancel</a><?php endif; ?>
                </div>

    <!-- Form used to collect or submit data. -->
                <form method="post" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>

                    <?php
                    $fields = [
                        ['full_name', 'Full Name', 'text'],
                        ['email', 'Email', 'email'],
                        ['username', 'Username', 'text'],
                        ['phone', 'Phone', 'text'],
                        ['specialization', 'Specialization', 'text'],
                        ['experience_years', 'Experience (years)', 'number'],
                        ['joining_date', 'Joining Date', 'date']
                    ];
                    foreach ($fields as $f):
                    ?>
                        <label style="font-size:10px;color:#829391">
                            <?php echo $f[1]; ?>
                            <input name="<?php echo $f[0]; ?>" type="<?php echo $f[2]; ?>"
                                value="<?php echo htmlspecialchars($edit[$f[0]] ?? ($f[0] === 'joining_date' ? date('Y-m-d') : '')); ?>"
                                <?php echo $f[0] === 'experience_years' ? 'min="0"' : ''; ?>
                                style="display:block;width:100%;margin-top:6px;padding:11px;border-radius:7px;border:1px solid #1a3032;background:#0a1719;color:#fff"
                                required>
                        </label>
                    <?php endforeach; ?>

                    <div style="grid-column:1/-1">
<!-- // Run the database query. -->
                        <button class="btn" type="submit"><?php echo $edit ? 'Update Trainer' : 'Add Trainer'; ?></button>
                    </div>
                </form>
            </div>

            <div class="section">
                <div class="section-head">
                    <h2>All Trainers</h2>
                    <span style="font-size:10px;color:#637572"><?php echo $list->num_rows; ?> records</span>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Specialization</th>
                                <th>Experience</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list->num_rows): while ($row = $list->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?><br><span style="color:#607270"><?php echo htmlspecialchars($row['phone'] ?? '—'); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['specialization'] ?? '—'); ?></td>
                                        <td><?php echo (int)$row['experience_years']; ?> yrs</td>
                                        <td><?php echo htmlspecialchars($row['joining_date'] ?? '—'); ?></td>
                                        <td><span class="badge"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td>
                                            <div class="actions">
                                                <a class="action" href="trainers.php?edit=<?php echo $row['id']; ?>">Edit</a>
<!-- // Run the database query. -->
                                                <a class="action" href="trainers.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this trainer?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty">No trainers yet. Add the first trainer above.</div>
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
