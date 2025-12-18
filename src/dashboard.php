<?php
ob_start(); 
session_start();

// This dashboard is accessible to both Students and Admins
// We'll show/hide certain sections based on the user's role

// 1. Security: Redirect to login if session is missing
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.html");
    exit;
}

// 2. Determine Role
$role = $_SESSION['user_role'] ?? 'Student';
$isAdmin = ($role === 'Admin');

// 3. Database Connection (For fetching Quick Stats)
$host = '127.0.0.1';
$dbname = 'course';
$username = 'admin';
$password = 'password123';

$stats = ['assignments' => 0, 'resources' => 0, 'weeks' => 0];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get counts for the cards
    $stats['assignments'] = $pdo->query("SELECT COUNT(*) FROM assignments")->fetchColumn();
    $stats['resources'] = $pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();
    $stats['weeks'] = $pdo->query("SELECT COUNT(*) FROM weeks")->fetchColumn();

} catch (Exception $e) {
    // Fail silently if DB issues, just show 0
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Dashboard</title>
    <link rel="stylesheet" href="common/styles.css">
    <style>
        /* Extra style for the Admin Section */
        .admin-panel {
            background-color: #f8fafc;
            border: 2px dashed #cbd5e1;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        .admin-panel h3 {
            color: var(--pico-primary);
            margin-top: 0;
        }
        .admin-link-group {
            display: flex;
            flex-direction: column; 
            gap: 10px;
        }
        .admin-link-group a {
            font-size: 0.9rem; 
            display: block;
        }
    </style>
</head>
<body>

    <main class="container">

        <!-- Header -->
        <header>
            <h1>ITCS333 Dashboard</h1>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                    <p style="color: var(--pico-muted-color);">
                        Role: <strong><?php echo htmlspecialchars($role); ?></strong>
                    </p>
                </div>
                <a href="auth/logout.php" role="button" class="btn-delete">Logout</a>
            </div>
        </header>

        <hr>

        <!-- ADMIN ONLY SECTION -->
        <?php if ($isAdmin): ?>
        <section class="admin-panel">
            <h3>🛠️ Admin Control Panel</h3>
            <p>Manage course content and users.</p>

            <div class="grid">
                <!-- User Management -->
                <article class="card" style="margin-bottom:0;">
                    <h4>Users</h4>
                    <p>Manage students & admins</p>
                    <a href="admin/manage_users.html" role="button" class="secondary">Manage Users</a>
                </article>

                <!-- Content Management Links -->
                <article class="card" style="margin-bottom:0;">
                    <h4>Create Content</h4>
                    <div class="admin-link-group">
                        <a href="assignments/admin.html">➕ Add Assignment</a>
                        <a href="weekly/admin.html">➕ Add Weekly Schedule</a>
                        <a href="resources/admin.html">➕ Add Resource</a>
                    </div>
                </article>
            </div>
        </section>
        <?php endif; ?>

        <!-- GENERAL COURSE MODULES (Visible to Everyone) -->
        <h3>Course Modules</h3>
        <div class="grid">

            <!-- 1. Weekly Schedule -->
            <article class="card">
                <h2>📅 Weekly Schedule</h2>
                <p><strong><?php echo $stats['weeks']; ?></strong> Active Weeks</p>
                <p style="font-size: 0.85rem; color: #64748b;">Course timeline and materials.</p>
                <a href="weekly/list.html" role="button">View Schedule</a>
            </article>

            <!-- 2. Assignments -->
            <article class="card">
                <h2>📝 Assignments</h2>
                <p><strong><?php echo $stats['assignments']; ?></strong> Posted</p>
                <p style="font-size: 0.85rem; color: #64748b;">View tasks and submit work.</p>
                <a href="assignments/list.html" role="button">View Assignments</a>
            </article>

        </div>

        <div class="grid">

            <!-- 3. Resources -->
            <article class="card">
                <h2>📚 Resources</h2>
                <p><strong><?php echo $stats['resources']; ?></strong> Files</p>
                <p style="font-size: 0.85rem; color: #64748b;">References and links.</p>
                <a href="resources/list.html" role="button">View Resources</a>
            </article>

            <!-- 4. Discussion Board -->
            <article class="card">
                <h2>💬 Discussion</h2>
                <p>Community</p>
                <p style="font-size: 0.85rem; color: #64748b;">Chat with classmates.</p>
                <a href="discussion/baord.html" role="button" class="contrast">Open Board</a>
            </article>

        </div>

    </main>

</body>
</html>
<?php
ob_end_flush(); // Send the output to the browser
?>