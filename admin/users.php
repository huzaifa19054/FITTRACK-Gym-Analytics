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
    // Run the database query.
    if ($id === (int)$_SESSION['user_id']) $error = 'You cannot delete your own account.';
    else {
        // Get data from the database
        $s = $conn->prepare('DELETE FROM users WHERE id=?');
        $s->bind_param('i', $id);
        if ($s->execute()) $message = 'User deleted successfully.';
        // Run the database query.
        else $error = 'Could not delete user.';
        $s->close();
    }
}
// Check if an action was requested
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    if ($id === (int)$_SESSION['user_id']) $error = 'You cannot deactivate your own account.';
    else {
        // Get data from the database
        $s = $conn->prepare("UPDATE users SET status=IF(status='active','inactive','active') WHERE id=?");
        $s->bind_param('i', $id);
        $s->execute();
        $message = 'User status updated.';
        $s->close();
    }
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Check which form action was selected
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $role = trim($_POST['role'] ?? '');
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';
        if (!in_array($role, ['admin', 'trainer', 'member'], true) || $name === '' || $email === '' || $username === '') $error = 'Name, email, username and valid role are required.';
        elseif (!in_array($status, ['active', 'inactive'], true)) $error = 'Invalid status.';
        elseif (!$id && $password === '') $error = 'Password is required for a new user.';
        else {
            // Get data from the database
            $r = $conn->prepare('SELECT id FROM roles WHERE name=? LIMIT 1');
            $r->bind_param('s', $role);
            $r->execute();
            $role_id = (int)($r->get_result()->fetch_assoc()['id'] ?? 0);
            $r->close();
            if (!$role_id) $error = 'Role not found.';
            else {
                // Start a database transaction
                $conn->begin_transaction();
                try {
                    if ($id) {
                        if ($password !== '') {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            // Get data from the database
                            $s = $conn->prepare('UPDATE users SET role_id=?,full_name=?,email=?,username=?,password=?,phone=?,status=? WHERE id=?');
                            $s->bind_param('issssssi', $role_id, $name, $email, $username, $hash, $phone, $status, $id);
                        } else {
                            // Get data from the database
                            $s = $conn->prepare('UPDATE users SET role_id=?,full_name=?,email=?,username=?,phone=?,status=? WHERE id=?');
                            $s->bind_param('isssssi', $role_id, $name, $email, $username, $phone, $status, $id);
                        }
                        if (!$s->execute()) throw new Exception($conn->error);
                        $s->close();
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        // Get data from the database
                        $s = $conn->prepare('INSERT INTO users(role_id,full_name,email,username,password,phone,status) VALUES(?,?,?,?,?,?,?)');
                        $s->bind_param('issssss', $role_id, $name, $email, $username, $hash, $phone, $status);
                        if (!$s->execute()) throw new Exception($conn->error);
                        $id = $conn->insert_id;
                        $s->close();
                    }
                    if ($role === 'trainer') {
                        // Get data from the database
                        $q = $conn->prepare('SELECT id FROM trainers WHERE user_id=?');
                        // Add values to the prepared query
                        $q->bind_param('i', $id);
                        $q->execute();
                        $exists = $q->get_result()->num_rows;
                        $q->close();
                        if (!$exists) {
                            // Get data from the database
                            $q = $conn->prepare('INSERT INTO trainers(user_id,joining_date,status) VALUES(?,CURDATE(),?)');
                            // Add values to the prepared query
                            $q->bind_param('is', $id, $status);
                            $q->execute();
                            $q->close();
                        }
                    }
                    if ($role === 'member') {
                        // Get data from the database
                        $q = $conn->prepare('SELECT id FROM members WHERE user_id=?');
                        // Add values to the prepared query
                        $q->bind_param('i', $id);
                        $q->execute();
                        $exists = $q->get_result()->num_rows;
                        $q->close();
                        if (!$exists) {
                            // Get data from the database
                            $q = $conn->prepare('INSERT INTO members(user_id,joined_date,status) VALUES(?,CURDATE(),?)');
                            // Add values to the prepared query
                            $q->bind_param('is', $id, $status);
                            $q->execute();
                            $q->close();
                        }
                    }
                    // Save all database changes
                    $conn->commit();
                    $message = $id ? 'User saved successfully.' : 'User created successfully.';
                } catch (Exception $e) {
                    // Undo the changes if something fails
                    $conn->rollback();
                    $error = (strpos($e->getMessage(), 'Duplicate') !== false) ? 'Email or username already exists.' : 'Could not save user.';
                }
            }
        }
    }
}
$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $s = $conn->prepare("SELECT u.*,r.name role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?");
    $s->bind_param('i', $id);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
    $s->close();
}
// Get data from the database
$users = $conn->query("SELECT u.id,u.full_name,u.email,u.username,u.phone,u.status,u.created_at,r.name role FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id DESC");
// Run the database query.
$total = (int)$conn->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'];
// Run the database query.
$active = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE status='active'")->fetch_assoc()['c'];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>User Management · FitTrack</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="app">
        <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="members.php"><span class="ico">◎</span>Members</a><a class="nav" href="trainers.php"><span class="ico">✦</span>Trainers</a><a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a><a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a><a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav" href="progress.php"><span class="ico">↗</span>Progress</a><a class="nav" href="reports.php"><span class="ico">▥</span>Reports</a>
            <div class="nav-label">ACCOUNT</div><a class="nav active" href="users.php"><span class="ico">♙</span>User Management</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
        <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>User Management</h1>
                    <p>Manage portal accounts, roles and access status.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?> · Administrator</div>
            </div><?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><div class="grid">
                <div class="stat"><small>Total Users</small><strong><?php echo $total; ?></strong><i>♙</i></div>
                <div class="stat"><small>Active Accounts</small><strong><?php echo $active; ?></strong><i>✓</i></div>
                <!-- // Run the database query. -->
                <div class="stat"><small>Administrators</small><strong><?php echo (int)$conn->query("SELECT COUNT(*) c FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin'")->fetch_assoc()['c']; ?></strong><i>◆</i></div>
                <!-- // Run the database query. -->
                <div class="stat"><small>Staff & Members</small><strong><?php echo max(0, $total - (int)$conn->query("SELECT COUNT(*) c FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin'")->fetch_assoc()['c']); ?></strong><i>◎</i></div>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit User' : 'Create User'; ?></h2><?php if ($edit): ?><a class="btn secondary" href="users.php">Cancel</a><?php endif; ?>
                </div>
                <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
                    <!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo (int)($edit['id'] ?? 0); ?>"><label>Full Name<input class="form-control" name="full_name" value="<?php echo htmlspecialchars($edit['full_name'] ?? ''); ?>" required></label><label>Role<select class="form-control" name="role" required><?php foreach (['admin' => 'Administrator', 'trainer' => 'Trainer', 'member' => 'Member'] as $v => $label): ?><option value="<?php echo $v; ?>" <?php echo (($edit['role'] ?? '') === $v) ? 'selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></label><label>Email<input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($edit['email'] ?? ''); ?>" required></label><label>Username<input class="form-control" name="username" value="<?php echo htmlspecialchars($edit['username'] ?? ''); ?>" required></label><label>Phone<input class="form-control" name="phone" value="<?php echo htmlspecialchars($edit['phone'] ?? ''); ?>"></label><label>Status<select class="form-control" name="status">
                            <option value="active" <?php echo (($edit['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($edit['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            // Run the database query.
                        </select></label><label style="grid-column:1/-1">Password<input class="form-control" type="password" name="password" placeholder="<?php echo $edit ? 'Leave blank to keep current password' : 'Set login password'; ?>" <?php echo $edit ? '' : 'required'; ?>></label>
                    <!-- // Run the database query. -->
                    <div style="grid-column:1/-1"><button class="btn" type="submit"><?php echo $edit ? 'Update User' : 'Create User'; ?></button></div>
                </form>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2>Portal Users</h2><span style="font-size:10px;color:#637572"><?php echo $total; ?> accounts</span>
                </div>
                <div class="table-wrap">
                    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($users && $users->num_rows): while ($u = $users->fetch_assoc()): ?><tr>
                                        <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong><br><span class="mini"><?php echo htmlspecialchars($u['email']); ?></span></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><span class="badge"><?php echo ucfirst($u['role']); ?></span></td>
                                        <td><?php echo htmlspecialchars($u['phone'] ?: '—'); ?></td>
                                        <td><span class="badge" style="background:<?php echo $u['status'] === 'active' ? '#12352f' : '#302526'; ?>;color:<?php echo $u['status'] === 'active' ? '#54d5bc' : '#d99b9b'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                        <td>
                                            <!-- // Run the database query. -->
                                            <div class="actions"><a class="action" href="users.php?edit=<?php echo $u['id']; ?>">Edit</a><a class="action" href="users.php?toggle=<?php echo $u['id']; ?>"><?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></a><?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?><a class="action" href="users.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('Delete this user account?');">Delete</a><?php endif; ?></div>
                                        </td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="7">
                                        <div class="empty">No users found.</div>
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