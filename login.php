<?php

// Connect this page with the database
// Load the file needed by this page
require_once __DIR__ . '/config/db.php';

// Start the session if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    // Start the user session
    session_start();
}

// This variable will store error messages
$error = '';

// Get the selected role from URL or form
$role = strtolower($_GET['role'] ?? $_POST['role'] ?? '');

// These are the only roles allowed in our system
$validRoles = ['admin', 'trainer', 'member'];

// Check if selected role is valid
if (!in_array($role, $validRoles, true)) {
    $role = '';
}

// Information for each portal
$portalData = [

    // Admin portal information
    'admin' => [
        'title' => 'Admin Portal',
        'desc' => 'Complete control over members, revenue and gym operations.',
        'icon' => '⌘'
    ],

    // Trainer portal information
    'trainer' => [
        'title' => 'Trainer Portal',
        'desc' => 'Manage members, workouts, diets, classes and progress.',
        'icon' => '✦'
    ],

    // Member portal information
    'member' => [
        'title' => 'Member Portal',
        'desc' => 'View membership, attendance, workouts and your fitness progress.',
        'icon' => '◎'
    ]
];

// Check if the form was submitted
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get username from the form
    $u = trim($_POST['username'] ?? '');

    // Get password from the form
    $p = $_POST['password'] ?? '';

    // Get selected role from the form
    $role = strtolower($_POST['role'] ?? '');

    // Check if the selected role is valid
    if (!in_array($role, $validRoles, true)) {

        // Show an error if role is not valid
        $error = 'Please select a valid portal.';
    } else {

        // Prepare the SQL query to find the user
        // Get data from the database
        $q = $conn->prepare(
// Run the database query.
            "SELECT u.*, r.name role
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE (u.username = ? OR u.email = ?)
             AND r.name = ?
             AND u.status = 'active'
             LIMIT 1"
        );

        // Put the user values into the ? marks
        // Add values to the prepared query
        $q->bind_param('sss', $u, $u, $role);

        // Run the SQL query
        $q->execute();

        // Get the user data from the database
        $x = $q->get_result()->fetch_assoc();

        // Check if user exists and password is correct
        if ($x && password_verify($p, $x['password'])) {

            // Create a new session ID after login
            session_regenerate_id(true);

            // Create a new security token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Save user ID in session
            $_SESSION['user_id'] = $x['id'];

            // Save user's full name in session
            $_SESSION['full_name'] = $x['full_name'];

            // Save user's role in session
            $_SESSION['role'] = $x['role'];

            // Check if the dashboard file exists
            if (is_file(__DIR__ . "/{$role}/dashboard.php")) {

                // Send user to the correct dashboard
                header("Location: {$role}/dashboard.php");

                // Stop the code after redirect
                exit;
            }

            // Remove session data if dashboard does not exist
            session_unset();

            // Show an error message
            $error =
                ucfirst($role) .
                ' portal is being finalized. Admin portal is currently available.';
        } else {

            // Show error when login details are wrong
            $error =
                'Invalid username, password, or selected portal.';
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Choose Your Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body class="portal-page">
    <div class="portal-bg"></div>
    <div class="portal-layout">
    <!-- Main page section. -->
        <section class="portal-visual-side">
            <a href="index.php" class="portal-logo"><span>F</span> Fit<span>Track</span></a>
            <div class="portal-visual-copy">
                <span>FITNESS MANAGEMENT SYSTEM</span>
                <h1>Stronger people.<br><em>Smarter management.</em></h1>
                <p>One connected system for your gym team and members.</p>
            </div>
            <div class="portal-visual-bottom"><b>01</b><span>SECURE ACCESS</span><i></i><span>FITTRACK</span></div>
        </section>

    <!-- Main page section. -->
        <section class="portal-selection-side">
            <?php if (!$role): ?>
                <div class="selection-head"><span>WELCOME TO FITTRACK</span>
                    <h2>Choose your <em>portal.</em></h2>
                    <p>Select your role to continue to the appropriate workspace.</p>
                </div>
                <div class="portal-choice-grid-new">
                    <?php foreach ($portalData as $key => $portal): ?>
                        <a class="portal-choice-new" href="login.php?role=<?php echo $key; ?>">
                            <div class="choice-icon"><?php echo $portal['icon']; ?></div>
                            <div class="choice-content"><small>0<?php echo array_search($key, array_keys($portalData)) + 1; ?></small>
                                <h3><?php echo htmlspecialchars($portal['title']); ?></h3>
                                <p><?php echo htmlspecialchars($portal['desc']); ?></p>
                            </div>
                            <span class="choice-arrow">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="index.php" class="portal-back">← Back to website</a>
            <?php else: ?>
                <div class="login-card-new">
                    <a href="login.php" class="change-portal-new">← Change portal</a>
                    <div class="selected-portal-new">
                        <div class="choice-icon"> <?php echo $portalData[$role]['icon']; ?> </div>
                        <div><small>SELECTED PORTAL</small>
                            <h3><?php echo htmlspecialchars($portalData[$role]['title']); ?></h3>
                        </div>
                    </div>
                    <div class="selection-head login-head"><span>WELCOME BACK</span>
                        <h2>Sign in to <em>FitTrack.</em></h2>
                        <p>Enter your account details to access your workspace.</p>
                    </div>
                    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <!-- Form used to collect or submit data. -->
                    <form method="post" class="login-form-new">
                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                        <label>Username or Email<input name="username" placeholder="Enter username or email" required autofocus></label>
                        <label>Password<input type="password" name="password" placeholder="Enter password" required></label>
                        <button type="submit">Sign In <span>→</span></button>
                        <div class="demo-note-new"><small>DEMO ACCESS</small><b><?php echo htmlspecialchars($role); ?> / password</b></div>
                    </form>
                </div>
                <a href="index.php" class="portal-back">← Back to website</a>
            <?php endif; ?>
        </section>
    </div>
    <!-- JavaScript used on this page. -->
    <script>
        window.addEventListener('load', () => document.body.classList.add('ready'));
    </script>
    <!-- JavaScript used on this page. -->
    <script src="assets/js/admin.js"></script>
</body>

</html>
