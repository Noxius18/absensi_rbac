<?php
session_start();
require_once 'config.php';
require_once 'lib/auth.php';

// Cek apakah user memiliki akses ke halaman ini
if (!isset($_SESSION['user_id']) || !hasPermission('roles', 'edit')) {
    header('Location: index.php');
    exit;
}

// Ambil daftar roles
$stmt = $pdo->prepare("SELECT * FROM Roles");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar permissions
$stmt = $pdo->prepare("SELECT * FROM Permissions");
$stmt->execute();
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$successMessage = ''; // Variable untuk menyimpan pesan sukses

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['permissions'])) {
    $role_id = $_POST['role_id'];
    $selected_permissions = $_POST['permissions'];

    // Ambil daftar permission_id yang sudah ada untuk role ini
    $stmt = $pdo->prepare("SELECT permission_id FROM Role_Permissions WHERE role_id = :role_id");
    $stmt->execute(['role_id' => $role_id]);
    $current_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Hitung permissions yang perlu ditambahkan atau dihapus
    $permissions_to_add = array_diff($selected_permissions, $current_permissions);
    $permissions_to_remove = array_diff($current_permissions, $selected_permissions);

    // Tambahkan permissions baru
    if (!empty($permissions_to_add)) {
        $stmt = $pdo->prepare("INSERT INTO Role_Permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
        foreach ($permissions_to_add as $permission_id) {
            $stmt->execute(['role_id' => $role_id, 'permission_id' => $permission_id]);
        }
    }

    // Hapus permissions yang tidak lagi diperlukan
    if (!empty($permissions_to_remove)) {
        $stmt = $pdo->prepare("DELETE FROM Role_Permissions WHERE role_id = :role_id AND permission_id = :permission_id");
        foreach ($permissions_to_remove as $permission_id) {
            $stmt->execute(['role_id' => $role_id, 'permission_id' => $permission_id]);
        }
    }

    $successMessage = "Berhasil update permissions!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Role</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<div class="max-w-4xl mx-auto mt-10 p-6 bg-white shadow-md rounded-lg">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Edit Role Permissions</h2>

    <?php if ($successMessage): ?>
        <div class="p-4 mb-6 text-green-800 bg-green-100 border border-green-300 rounded-md">
            <p class="text-sm font-medium"><?php echo $successMessage; ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="mb-6">
        <label for="role_id" class="block text-gray-700 font-semibold mb-2">Select Role:</label>
        <select 
            name="role_id" 
            id="role_id" 
            required 
            onchange="this.form.submit()"
            class="block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Select Role --</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['role_id']; ?>" 
                    <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($role['role_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (isset($_POST['role_id'])): ?>
        <?php
        $role_id = $_POST['role_id'];
        $stmt = $pdo->prepare("SELECT permission_id FROM Role_Permissions WHERE role_id = :role_id");
        $stmt->execute(['role_id' => $role_id]);
        $current_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        ?>

        <form method="POST">
            <input type="hidden" name="role_id" value="<?php echo $role_id; ?>">

            <h3 class="text-xl font-semibold text-gray-800 mb-4">
                Permissions for <?php echo htmlspecialchars($roles[array_search($role_id, array_column($roles, 'role_id'))]['role_name']); ?>:
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($permissions as $permission): ?>
                    <label class="flex items-center space-x-2">
                        <input 
                            type="checkbox" 
                            name="permissions[]" 
                            value="<?php echo $permission['permission_id']; ?>" 
                            <?php echo in_array($permission['permission_id'], $current_permissions) ? 'checked' : ''; ?>
                            class="form-checkbox h-5 w-5 text-blue-600"
                        >
                        <span class="text-gray-700">
                            <?php echo htmlspecialchars($permission['resource'] . ' - ' . $permission['action']); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

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
        </form>
    <?php endif; ?>
</div>
</body>
</html>
