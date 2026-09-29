<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
$roles = ['admin', 'trainer', 'member'];
foreach ($roles as $role) {
    // Get data from the database
    $stmt = $conn->prepare("INSERT IGNORE INTO roles(name) VALUES(?)");
    // Add values to the prepared query
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $stmt->close();
}
$users = [
    ['admin', 'System Administrator', 'admin@fittrack.local', 'admin'],
    ['trainer', 'Demo Trainer', 'trainer@fittrack.local', 'trainer'],
    ['member', 'Demo Member', 'member@fittrack.local', 'member']
];
foreach ($users as [$role, $name, $email, $username]) {
    // Get data from the database
    $r = $conn->prepare("SELECT id FROM roles WHERE name=?");
    $r->bind_param("s", $role);
    $r->execute();
    $roleId = $r->get_result()->fetch_assoc()['id'];
    $r->close();
    // Get data from the database
    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();
    if (!$exists) {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        // Get data from the database
        $stmt = $conn->prepare("INSERT INTO users(role_id,full_name,email,username,password) VALUES(?,?,?,?,?)");
        // Add values to the prepared query
        $stmt->bind_param("issss", $roleId, $name, $email, $username, $hash);
        $stmt->execute();
        $uid = $conn->insert_id;
        $stmt->close();
        if ($role === 'trainer') {
            // Get data from the database
            $stmt = $conn->prepare("INSERT INTO trainers(user_id,specialization,experience_years,joining_date) VALUES(?,?,?,CURDATE())");
            $spec = 'Strength & Conditioning';
            $exp = 3;
            // Add values to the prepared query
            $stmt->bind_param("isi", $uid, $spec, $exp);
            $stmt->execute();
            $stmt->close();
        } elseif ($role === 'member') {
            // Get data from the database
            $stmt = $conn->prepare("INSERT INTO members(user_id,joined_date,height,weight) VALUES(?,CURDATE(),175,70)");
            // Add values to the prepared query
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
        }
    }
}
echo "Demo data installed. Login: admin / password";
?>
<?php
// Run the database query.
$planCount = (int)$conn->query("SELECT COUNT(*) c FROM membership_plans")->fetch_assoc()['c'];
if ($planCount === 0) {
    $plans = [
        ['Basic Monthly', 1, 3000, 'Gym access with standard facilities.'],
        ['Premium Quarterly', 3, 8000, 'Full gym access plus trainer guidance.'],
        ['Elite Annual', 12, 25000, 'Annual membership with premium support and classes.']
    ];
    // Get data from the database
    $stmt = $conn->prepare("INSERT INTO membership_plans(name,duration_months,price,description) VALUES(?,?,?,?)");
    foreach ($plans as [$n, $d, $p, $desc]) $stmt->bind_param("sids", $n, $d, $p, $desc) && $stmt->execute();
    $stmt->close();
}
?>
