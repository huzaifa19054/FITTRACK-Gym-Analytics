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
    $stmt = $conn->prepare("DELETE FROM membership_plans WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Membership plan deleted successfully.';
// Run the database query.
    else $error = 'Cannot delete this plan because it is being used by an existing membership.';
    $stmt->close();
}

// Check if an action was requested
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    // Get data from the database
    $stmt = $conn->prepare("UPDATE membership_plans SET status=IF(status='active','inactive','active') WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Plan status updated.';
// Run the database query.
    else $error = 'Unable to update plan status.';
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $duration = (int)($_POST['duration_months'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $id = (int)($_POST['id'] ?? 0);

    if ($name === '' || $duration < 1 || $price < 0) {
        $error = 'Plan name, duration and a valid price are required.';
    } elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error = 'Invalid plan status.';
    } elseif ($id) {
        // Get data from the database
        $stmt = $conn->prepare("UPDATE membership_plans SET name=?,duration_months=?,price=?,description=?,status=? WHERE id=?");
        // Add values to the prepared query
        $stmt->bind_param("sidssi", $name, $duration, $price, $description, $status, $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Membership plan updated successfully.';
// Run the database query.
        else $error = 'Could not update membership plan.';
        $stmt->close();
    } else {
        // Get data from the database
        $stmt = $conn->prepare("INSERT INTO membership_plans(name,duration_months,price,description,status) VALUES(?,?,?,?,?)");
        // Add values to the prepared query
        $stmt->bind_param("sidss", $name, $duration, $price, $description, $status);
        // Run the database query
        if ($stmt->execute()) $message = 'Membership plan added successfully.';
        else $error = 'Could not add membership plan.';
        $stmt->close();
    }
}

$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare("SELECT id,name,duration_months,price,description,status FROM membership_plans WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get data from the database
$list = $conn->query("SELECT p.id,p.name,p.duration_months,p.price,p.description,p.status,
    (SELECT COUNT(*) FROM memberships m WHERE m.plan_id=p.id) AS used_count
    FROM membership_plans p ORDER BY p.id DESC");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Membership Plans — FitTrack</title>
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
            gap: 13px;
            margin-bottom: 18px
        }

        .plan-card {
            background: #0d1c1e;
            border: 1px solid #182c2e;
            border-radius: 12px;
            padding: 20px;
            transition: .25s
        }

        .plan-card:hover {
            transform: translateY(-3px);
            border-color: #24514a
        }

        .plan-card h3 {
            font: 700 17px Manrope;
            margin: 0 0 7px
        }

        .price {
            font: 800 26px Manrope;
            color: #16c7a4
        }

        .price small {
            font: 400 10px;
            color: #718381
        }

        .plan-card p {
            font-size: 10px;
            color: #748582;
            line-height: 1.7;
            min-height: 32px
        }

        @media(max-width:800px) {
            .plan-grid {
                grid-template-columns: 1fr
            }

            .form-grid {
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
            <a class="nav active" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a>
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
                    <h1>Membership Plans</h1>
                    <p>Create and manage pricing plans offered by your gym.</p>
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
                    <h2><?php echo $edit ? 'Edit Membership Plan' : 'Add New Membership Plan'; ?></h2>
                    <?php if ($edit): ?><a class="btn secondary" href="membership-plans.php">Cancel</a><?php endif; ?>
                </div>

    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>

                    <label style="font-size:10px;color:#829391">Plan Name
                        <input class="form-control" name="name" type="text" placeholder="e.g. Premium Monthly"
                            value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" required>
                    </label>

                    <label style="font-size:10px;color:#829391">Duration (months)
                        <input class="form-control" name="duration_months" type="number" min="1" value="<?php echo htmlspecialchars($edit['duration_months'] ?? 1); ?>" required>
                    </label>

                    <label style="font-size:10px;color:#829391">Price (PKR)
                        <input class="form-control" name="price" type="number" min="0" step="0.01" placeholder="5000"
                            value="<?php echo htmlspecialchars($edit['price'] ?? ''); ?>" required>
                    </label>

                    <label style="font-size:10px;color:#829391">Status
<!-- // Run the database query. -->
                        <select class="form-control" name="status">
                            <option value="active" <?php echo (($edit['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($edit['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </label>

                    <label style="font-size:10px;color:#829391;grid-column:1/-1">Description
                        <textarea class="form-control" name="description" rows="3" placeholder="Short description of what this plan includes"><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
                    </label>

<!-- // Run the database query. -->
                    <div style="grid-column:1/-1"><button class="btn" type="submit"><?php echo $edit ? 'Update Plan' : 'Add Plan'; ?></button></div>
                </form>
            </div>

            <?php
            // Get data from the database
            $cardResult = $conn->query("SELECT id,name,duration_months,price,description,status FROM membership_plans WHERE status='active' ORDER BY price ASC");
            if ($cardResult && $cardResult->num_rows):
            ?>
                <div class="plan-grid">
                    <?php while ($plan = $cardResult->fetch_assoc()): ?>
                        <div class="plan-card">
                            <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="price">PKR <?php echo number_format((float)$plan['price']); ?> <small>/ <?php echo (int)$plan['duration_months']; ?> month<?php echo $plan['duration_months'] != 1 ? 's' : ''; ?></small></div>
                            <p><?php echo htmlspecialchars($plan['description'] ?: 'Flexible gym membership plan.'); ?></p>
                            <a class="action" href="membership-plans.php?edit=<?php echo $plan['id']; ?>">Edit Plan</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-head">
                    <h2>All Membership Plans</h2>
                    <span style="font-size:10px;color:#637572"><?php echo $list->num_rows; ?> records</span>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Used By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list->num_rows): while ($row = $list->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                        <td><?php echo (int)$row['duration_months']; ?> month<?php echo $row['duration_months'] != 1 ? 's' : ''; ?></td>
                                        <td>PKR <?php echo number_format((float)$row['price']); ?></td>
                                        <td><?php echo htmlspecialchars($row['description'] ?: '—'); ?></td>
                                        <td><?php echo (int)$row['used_count']; ?></td>
                                        <td><span class="badge" style="<?php echo $row['status'] === 'inactive' ? 'background:#2c2820;color:#d4b46a' : ''; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td>
                                            <div class="actions">
                                                <a class="action" href="membership-plans.php?edit=<?php echo $row['id']; ?>">Edit</a>
                                                <a class="action" href="membership-plans.php?toggle=<?php echo $row['id']; ?>"><?php echo $row['status'] === 'active' ? 'Disable' : 'Activate'; ?></a>
<!-- // Run the database query. -->
                                                <?php if ((int)$row['used_count'] === 0): ?><a class="action" href="membership-plans.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this membership plan?')">Delete</a><?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty">No membership plans yet. Create your first plan above.</div>
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
