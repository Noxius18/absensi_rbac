<?php
session_start();

require_once 'config.php';
require_once 'lib/auth.php';

if (!isset($_SESSION['user_id']) || !hasPermission('projects', 'view')) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek role user
$stmt = $pdo->prepare("SELECT role_id FROM Users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user_role = $stmt->fetchColumn(); // Ambil role ID pengguna

// Tambah Proyek
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $manager_id = $_POST['manager_id'];
    $project_name = $_POST['project_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $stmt = $pdo->prepare("INSERT INTO Projects (manager_id, project_name, start_date, end_date) 
                           VALUES (:manager_id, :project_name, :start_date, :end_date)");
    $stmt->execute([
        'manager_id' => $manager_id,
        'project_name' => $project_name,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);

    header('Location: project_list.php');
    exit;
}

// Hapus Proyek (Hanya Admin dan Project Manager)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $project_id = $_POST['project_id'];

    if ($user_role == 1 || $user_role == 2) { // Admin atau Project Manager
        $stmt = $pdo->prepare("DELETE FROM Projects WHERE project_id = :project_id");
        $stmt->execute(['project_id' => $project_id]);
        header('Location: project_list.php');
        exit;
    } else {
        echo "<script>alert('Anda tidak memiliki izin untuk menghapus proyek!');</script>";
    }
}

// Fetch Proyek berdasarkan role
if ($user_role == 1) {
    // Admin: Bisa melihat semua proyek
    $stmt = $pdo->prepare("
        SELECT p.project_id, p.project_name, p.start_date, p.end_date, u.username AS manager 
        FROM Projects p 
        JOIN Users u ON p.manager_id = u.user_id
    ");
} elseif ($user_role == 2) {
    // Project Manager: Hanya melihat proyek yang dikelola
    $stmt = $pdo->prepare("
        SELECT p.project_id, p.project_name, p.start_date, p.end_date, u.username AS manager 
        FROM Projects p 
        JOIN Users u ON p.manager_id = u.user_id
        WHERE p.manager_id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
} else {
    // Worker: Tidak memiliki akses ke proyek
    $projects = [];
}

// Eksekusi query untuk mendapatkan proyek
if ($user_role == 1) {
    $stmt->execute();
}
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar manajer
if ($user_role == 1) {
    // Admin: Ambil semua manajer
    $stmt = $pdo->prepare("SELECT user_id, username FROM Users WHERE role_id = 2");
} elseif ($user_role == 2) {
    // Project Manager: Hanya ambil diri sendiri
    $stmt = $pdo->prepare("SELECT user_id, username FROM Users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
}
$managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<div class="max-w-6xl mx-auto mt-10 p-6 bg-white shadow-md rounded-lg">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Daftar Proyek</h2>

    <!-- Tabel Proyek -->
    <table class="w-full table-auto border-collapse border border-gray-300 mb-6">
        <thead>
            <tr class="bg-gray-200 text-gray-700">
                <th class="px-4 py-2 border border-gray-300">ID</th>
                <th class="px-4 py-2 border border-gray-300">Nama Proyek</th>
                <th class="px-4 py-2 border border-gray-300">Manajer</th>
                <th class="px-4 py-2 border border-gray-300">Tanggal Mulai</th>
                <th class="px-4 py-2 border border-gray-300">Tanggal Berakhir</th>
                <?php if ($user_role == 1 || $user_role == 2): ?>
                    <th class="px-4 py-2 border border-gray-300">Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project): ?>
                <tr class="text-gray-700 hover:bg-gray-100">
                    <td class="px-4 py-2 border border-gray-300 text-center"><?php echo htmlspecialchars($project['project_id']); ?></td>
                    <td class="px-4 py-2 border border-gray-300"><?php echo htmlspecialchars($project['project_name']); ?></td>
                    <td class="px-4 py-2 border border-gray-300"><?php echo htmlspecialchars($project['manager']); ?></td>
                    <td class="px-4 py-2 border border-gray-300 text-center"><?php echo htmlspecialchars($project['start_date']); ?></td>
                    <td class="px-4 py-2 border border-gray-300 text-center"><?php echo htmlspecialchars($project['end_date']); ?></td>
                    <?php if ($user_role == 1 || $user_role == 2): ?>
                        <td class="px-4 py-2 border border-gray-300 text-center">
                            <!-- Tombol Hapus -->
                            <form method="POST" class="inline">
                                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                <button name="action" value="delete" type="submit" 
                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 focus:outline-none">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Form Tambah Proyek -->
    <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah Proyek Baru</h3>
    <form action="project_list.php" method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add">
        <div>
            <label for="project_name" class="block text-gray-700 font-semibold">Nama Proyek</label>
            <input type="text" name="project_name" id="project_name" required 
                   class="block w-full mt-1 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="manager_id" class="block text-gray-700 font-semibold">Manajer</label>
            <select name="manager_id" id="manager_id" required 
                    class="block w-full mt-1 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Pilih Manajer --</option>
                <?php foreach ($managers as $manager): ?>
                    <option value="<?php echo $manager['user_id']; ?>" <?php if ($user_role == 2 && $manager['user_id'] == $user_id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($manager['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="start_date" class="block text-gray-700 font-semibold">Tanggal Mulai</label>
            <input type="date" name="start_date" id="start_date" required 
                   class="block w-full mt-1 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="end_date" class="block text-gray-700 font-semibold">Tanggal Berakhir</label>
            <input type="date" name="end_date" id="end _date" required 
                   class="block w-full mt-1 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex justify-between items-center mt-6">
            <button type="submit" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Tambah Proyek
            </button>
            <a href="dashboard.php" 
               class="text-blue-600 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500">
                Kembali ke Dashboard
            </a>
        </div>
    </form>
</div>

</body>
</html>