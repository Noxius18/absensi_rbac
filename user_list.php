<?php
session_start();

require_once 'config.php';
require_once 'lib/auth.php';

if (!isset($_SESSION['user_id']) || !hasPermission('users', 'view')) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT Users.user_id AS ID, 
           Users.username AS Nama, 
           Users.email AS Email, 
           Roles.role_name AS Role, 
           Users.status AS Status 
    FROM Users 
    JOIN Roles ON Users.role_id = Roles.role_id
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List User</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<div class="max-w-6xl mx-auto mt-10 p-6 bg-white shadow-md rounded-lg">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Daftar User</h2>

    <table class="w-full table-auto border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-200 text-gray-700">
                <th class="px-4 py-2 border border-gray-300">ID</th>
                <th class="px-4 py-2 border border-gray-300">Nama</th>
                <th class="px-4 py-2 border border-gray-300">Email</th>
                <th class="px-4 py-2 border border-gray-300">Role</th>
                <th class="px-4 py-2 border border-gray-300">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr class="text-gray-700 hover:bg-gray-100">
                    <td class="px-4 py-2 border border-gray-300 text-center"><?php echo htmlspecialchars($user['ID']); ?></td>
                    <td class="px-4 py-2 border border-gray-300"><?php echo htmlspecialchars($user['Nama']); ?></td>
                    <td class="px-4 py-2 border border-gray-300"><?php echo htmlspecialchars($user['Email']); ?></td>
                    <td class="px-4 py-2 border border-gray-300"><?php echo htmlspecialchars($user['Role']); ?></td>
                    <td class="px-4 py-2 border border-gray-300 text-center">
                        <span class="<?php echo $user['Status'] == 'aktif' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' ?>";>
                            <?php echo htmlspecialchars($user['Status']); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="flex justify-between items-center mt-6">
        <button 
            type="submit" 
            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            Update Permissions
        </button>
        <a href="dashboard.php" 
            class="text-blue-600 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500">
            Back to Dashboard
        </a>
    </div>
</div>

</body>
</html>
