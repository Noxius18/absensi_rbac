<?php
session_start();

require_once 'config.php';
require_once 'lib/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$stmt = $pdo->prepare("SELECT username FROM Users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$username = $stmt->fetchColumn();

if(!$username) {
    $username = "Unknown User";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <!-- Navbar -->
    <nav class="bg-blue-600 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-white text-xl font-semibold">Dashboard</h1>
            <a href="logout.php" class="text-white text-sm hover:underline">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-4">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p class="text-gray-700 mb-4">Anda Login sebagai:  <?php echo htmlspecialchars($username); ?>.</p>

            <!-- Feature List -->
            <h3 class="text-xl font-semibold mb-2">Features</h3>
            <ul class="list-disc list-inside">
                <?php if (hasPermission('roles', 'edit')): ?>
                    <li><a href="role_permissions.php" class="text-blue-500 hover:underline">Edit Permission</a></li>
                <?php endif; ?>
                <?php if (hasPermission('users', 'view')): ?>
                    <li><a href="user_list.php" class="text-blue-500 hover:underline">View Users</a></li>
                <?php endif; ?>
                <?php if (hasPermission('projects', 'view')): ?>
                    <li><a href="project_list.php" class="text-blue-500 hover:underline">View Projects</a></li>
                <?php endif; ?>
                <?php if (hasPermission('absensi', 'view')): ?>
                    <li><a href="absensi.php" class="text-blue-500 hover:underline">View Attendance</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

</body>
</html>