<?php
// Load the file needed by this page
require_once __DIR__ . '/config/db.php';

$contactSuccess = '';
$contactError = '';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '') {
        $contactError = 'Please fill in all fields with a valid email address.';
    } else {
        // Get data from the database
        $stmt = $conn->prepare('INSERT INTO contact_messages(name,email,subject,message) VALUES(?,?,?,?)');
        if ($stmt) {
            // Add values to the prepared query
            $stmt->bind_param('ssss', $name, $email, $subject, $message);
            // Run the database query
            if ($stmt->execute()) {
                $contactSuccess = 'Thanks! Your message has been sent to FitTrack.';
            } else {
                $contactError = 'Your message could not be saved. Please try again.';
            }
            $stmt->close();
        } else {
// Run the database query.
            $contactError = 'Contact service is not configured yet. Import database/update-contact-messages.sql first.';
        }
    }
}

function landing_count($conn, $sql)
{
    // Get data from the database
    $r = $conn->query($sql);
    return $r ? (int)$r->fetch_assoc()['total'] : 0;
}

// Run the database query.
$totalMembers = landing_count($conn, "SELECT COUNT(*) total FROM members");
// Run the database query.
$activeMembers = landing_count($conn, "SELECT COUNT(*) total FROM members WHERE status='active'");
// Run the database query.
$totalTrainers = landing_count($conn, "SELECT COUNT(*) total FROM trainers WHERE status='active'");
// Run the database query.
$totalWorkoutPlans = landing_count($conn, "SELECT COUNT(*) total FROM workout_plans");
// Run the database query.
$currentMonthMembers = landing_count($conn, "SELECT COUNT(*) total FROM members WHERE joined_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND joined_date <= CURDATE()");
// Run the database query.
$previousMonthMembers = landing_count($conn, "SELECT COUNT(*) total FROM members WHERE joined_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND joined_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')");
if ($previousMonthMembers > 0) {
    $growth = (($currentMonthMembers - $previousMonthMembers) / $previousMonthMembers) * 100;
    $growthText = ($growth >= 0 ? '+' : '') . number_format($growth, 1) . '%';
} else {
    $growthText = $currentMonthMembers > 0 ? '+' . $currentMonthMembers : '0%';
}
$memberInitials = [];
// Get data from the database
$r = $conn->query("SELECT u.full_name FROM members m JOIN users u ON u.id=m.user_id WHERE m.status='active' ORDER BY m.id DESC LIMIT 3");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $parts = preg_split('/\s+/', trim($row['full_name']));
        $memberInitials[] = strtoupper(substr($parts[0], 0, 1) . (count($parts) > 1 ? substr($parts[count($parts) - 1], 0, 1) : ''));
    }
}
while (count($memberInitials) < 3) $memberInitials[] = '--';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitTrack Gym Management System">
    <title>FitTrack — Gym Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="page-loader">
        <div class="loader-logo">Fit<span>Track</span></div>
        <div class="loader-line"><span></span></div>
    </div>

    <!-- Main header area. -->
    <header class="navbar" id="navbar">
        <a href="#" class="brand" aria-label="FitTrack home">
            <span class="brand-mark">F</span>
            <span>Fit<span>Track</span></span>
        </a>

    <!-- Main navigation links. -->
        <nav class="nav-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </nav>

        <a href="login.php" class="nav-btn">Portal Login <span>↗</span></a>

        <button class="menu-btn" id="menuBtn" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
    </header>

    <!-- Main page content. -->
    <main>
    <!-- Main page section. -->
        <section class="hero" id="home">
            <div class="hero-bg"></div>
            <div class="hero-grid"></div>

            <div class="hero-content reveal">
                <div class="eyebrow"><i></i> Gym Management System</div>
                <h1>Train harder.<br><em>Manage smarter.</em></h1>
                <p>
                    One powerful platform to manage members, trainers, memberships,
                    attendance, payments and fitness progress — all in one place.
                </p>

                <div class="hero-actions">
                    <a href="login.php" class="primary-btn">
                        Get Started <span>→</span>
                    </a>
                    <a href="#features" class="secondary-btn">
                        Explore Features <span>↓</span>
                    </a>
                </div>

                <div class="hero-stats">
                    <div><strong><?php echo number_format($totalMembers); ?></strong><span>Members Managed</span></div>
                    <div><strong><?php echo number_format($activeMembers); ?></strong><span>Active Members</span></div>
                    <div><strong><?php echo number_format($totalTrainers); ?></strong><span>Active Trainers</span></div>
                </div>
            </div>

            <div class="hero-visual reveal reveal-delay">
                <div class="floating-card card-top">
                    <span class="mini-icon">↗</span>
                    <div><strong><?php echo htmlspecialchars($growthText); ?></strong><small>Member Growth</small></div>
                </div>

                <div class="hero-photo">
                    <div class="photo-overlay"></div>
                    <div class="photo-caption">
                        <span>01 / 03</span>
                        <strong>DISCIPLINE<br>BUILDS RESULTS.</strong>
                    </div>
                </div>

                <div class="floating-card card-bottom">
                    <div class="avatar-stack">
                        <?php foreach ($memberInitials as $initial): ?><span><?php echo htmlspecialchars($initial); ?></span><?php endforeach; ?><span>+</span>
                    </div>
                    <div><strong><?php echo number_format($activeMembers); ?> Active</strong><small>Gym Members</small></div>
                </div>
            </div>

            <div class="scroll-hint">
                <span>SCROLL TO EXPLORE</span>
                <i></i>
            </div>
        </section>

    <!-- Main page section. -->
        <section class="feature-strip" id="features">
            <div class="section-heading reveal">
                <div>
                    <span class="section-tag">WHY FITTRACK</span>
                    <h2>Everything your gym<br><em>needs to move forward.</em></h2>
                </div>
                <p>Simple tools. Powerful management. A better experience for your entire gym.</p>
            </div>

            <div class="feature-grid">
                <article class="feature-card reveal">
                    <div class="feature-number">01</div>
                    <div class="feature-icon">◎</div>
                    <h3>Member Management</h3>
                    <p>Keep member profiles, memberships and fitness information organized.</p>
                    <span class="feature-arrow">↗</span>
                </article>

                <article class="feature-card featured reveal reveal-delay">
                    <div class="feature-number">02</div>
                    <div class="feature-icon">↗</div>
                    <h3>Smart Dashboard</h3>
                    <p>See attendance, revenue, active members and important activity at a glance.</p>
                    <span class="feature-arrow">↗</span>
                </article>

                <article class="feature-card reveal reveal-delay-2">
                    <div class="feature-number">03</div>
                    <div class="feature-icon">◷</div>
                    <h3>Progress Tracking</h3>
                    <p>Track workouts, diet plans and member progress over time.</p>
                    <span class="feature-arrow">↗</span>
                </article>
            </div>
        </section>

    <!-- Main page section. -->
        <section class="portal-section" id="about">
            <div class="portal-copy reveal">
                <span class="section-tag">ONE SYSTEM. THREE EXPERIENCES.</span>
                <h2>Built for every<br><em>part of your gym.</em></h2>
                <p>FitTrack gives each user the tools they actually need, without overwhelming them with unnecessary complexity.</p>

                <div class="portal-list">
                    <div><span>01</span><strong>Admin</strong><small>Complete gym control</small></div>
                    <div><span>02</span><strong>Trainer</strong><small>Manage clients & plans</small></div>
                    <div><span>03</span><strong>Member</strong><small>Track your fitness journey</small></div>
                </div>
            </div>

            <div class="portal-visual reveal reveal-delay">
                <div class="dashboard-window">
                    <div class="window-bar">
                        <span></span><span></span><span></span>
                        <small>FitTrack Dashboard</small>
                    </div>
                    <div class="fake-dashboard">
    <!-- Dashboard sidebar navigation. -->
                        <aside>
                            <b>Fit<span>Track</span></b>
                            <i></i><i></i><i></i><i></i><i></i><i></i>
                        </aside>
                        <div class="fake-main">
                            <div class="fake-title"></div>
                            <div class="fake-cards"><i></i><i></i><i></i></div>
                            <div class="fake-chart"><span></span><span></span><span></span><span></span><span></span><span></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- Main page section. -->
        <section class="contact-section" id="contact">
            <div class="contact-header reveal">
                <span class="section-tag">GET IN TOUCH</span>
                <h2>Let’s build a<br><em>stronger gym.</em></h2>
                <p>Have a question about FitTrack? Get in touch with our team.</p>
            </div>
            <div class="contact-grid">
                <div class="contact-info reveal">
                    <div class="contact-item">
                        <div class="contact-icon">⌖</div>
                        <div><small>LOCATION</small><strong>Karachi, Pakistan</strong></div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">☎</div>
                        <div><small>PHONE</small><strong>+92 300 0000000</strong></div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">✉</div>
                        <div><small>EMAIL</small><strong>info@fittrackgym.com</strong></div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">◷</div>
                        <div><small>OPENING HOURS</small><strong>Mon – Sat · 6:00 AM – 11:00 PM</strong></div>
                    </div>
                    <a href="https://wa.me/" target="_blank" rel="noopener" class="whatsapp-btn">Chat on WhatsApp <span>↗</span></a>
                </div>
    <!-- Form used to collect or submit data. -->
                <form class="contact-form reveal reveal-delay" method="post" action="#contact">
                    <input type="hidden" name="contact_form" value="1">
                    <?php if ($contactSuccess): ?><div class="contact-alert success"><?php echo htmlspecialchars($contactSuccess); ?></div><?php endif; ?>
                    <?php if ($contactError): ?><div class="contact-alert error"><?php echo htmlspecialchars($contactError); ?></div><?php endif; ?>
                    <div class="form-row">
                        <div class="contact-field"><label>Name</label><input type="text" name="name" placeholder="Your name" required></div>
                        <div class="contact-field"><label>Email</label><input type="email" name="email" placeholder="you@example.com" required></div>
                    </div>
                    <div class="contact-field"><label>Subject</label><input type="text" name="subject" placeholder="How can we help?" required></div>
                    <div class="contact-field"><label>Message</label><textarea name="message" rows="6" placeholder="Write your message..." required></textarea></div>
                    <button type="submit" class="primary-btn contact-submit">Send Message <span>→</span></button>
                </form>
            </div>
        </section>

    <!-- Main page section. -->
        <section class="cta-section">
            <div class="cta-glow"></div>
            <span class="section-tag">READY TO GET STARTED?</span>
            <h2>Make your gym<br><em>work smarter.</em></h2>
            <p>Manage your gym from one clean, powerful system.</p>
            <a href="login.php" class="primary-btn">Enter FitTrack <span>→</span></a>
        </section>
    </main>

    <!-- Website footer area. -->
    <footer>
        <div class="footer-main">
            <div class="footer-about">
                <div class="brand footer-brand"><span class="brand-mark">F</span><span>Fit<span>Track</span></span></div>
                <p>A smarter way to manage your gym, members and fitness journey.</p>
                <div class="social-links">
                    <a href="https://www.instagram.com/" target="_blank" rel="noopener">IG</a>
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener">FB</a>
                    <a href="https://wa.me/" target="_blank" rel="noopener">WA</a>
                    <a href="https://www.youtube.com/" target="_blank" rel="noopener">YT</a>
                </div>
            </div>
            <div class="footer-column">
                <h4>QUICK LINKS</h4><a href="#home">Home</a><a href="#features">Features</a><a href="#about">About</a><a href="#contact">Contact</a>
            </div>
            <div class="footer-column">
                <h4>PORTALS</h4><a href="login.php">Admin Portal</a><a href="login.php">Trainer Portal</a><a href="login.php">Member Portal</a>
            </div>
            <div class="footer-column">
                <h4>CONTACT</h4><span>Karachi, Pakistan</span><span>+92 300 0000000</span><span>info@fittrackgym.com</span>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> FitTrack Gym Management System</p><span>Train. Track. Transform.</span>
        </div>
    </footer>

    <!-- JavaScript used on this page. -->
    <script src="assets/js/app.js"></script>
</body>

</html>
