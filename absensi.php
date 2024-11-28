<?php
session_start();

require_once 'config.php';
require_once 'lib/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek role user
$stmt = $pdo->prepare("SELECT role_id FROM Users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user_role = $stmt->fetchColumn();

// Ambil data absensi berdasarkan role
if ($user_role == 1) {
    // Admin: Bisa melihat semua absensi
    $stmt = $pdo->prepare("
        SELECT a.absensi_id, u.username, p.project_name, a.check_in, a.check_out
        FROM Absensi a
        JOIN Users u ON a.user_id = u.user_id
        JOIN Projects p ON a.project_id = p.project_id
    ");
    $stmt->execute();
    $absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($user_role == 2) {
    // Project Manager: Bisa melihat absensi pekerja dalam proyek yang mereka kelola
    $stmt = $pdo->prepare("
        SELECT a.absensi_id, u.username, p.project_name, a.check_in, a.check_out
        FROM Absensi a
        JOIN Users u ON a.user_id = u.user_id
        JOIN Projects p ON a.project_id = p.project_id
        WHERE p.manager_id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($user_role == 3) {
    // Worker: Hanya melihat absensi sendiri
    $stmt = $pdo->prepare("
        SELECT a.absensi_id, p.project_name, a.check_in, a.check_out
        FROM Absensi a
        JOIN Projects p ON a.project_id = p.project_id
        WHERE a.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Proses Check In
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_in') {
        $project_id = $_POST['project_id'];

        // Cek apakah user sudah melakukan check-in
        $stmt = $pdo->prepare("SELECT * FROM Absensi WHERE user_id = :user_id AND project_id = :project_id AND check_out IS NULL");
        $stmt->execute(['user_id' => $user_id, 'project_id' => $project_id]);
        $existing_absensi = $stmt->fetch();

        if (!$existing_absensi) {
            // Lakukan check-in
            $stmt = $pdo->prepare("INSERT INTO Absensi (user_id, project_id, check_in) VALUES (:user_id, :project_id, NOW())");
            $stmt->execute(['user_id' => $user_id, 'project_id' => $project_id]);
            header('Location: absensi.php');
            exit;
        } else {
            echo "<script>alert('Anda sudah melakukan check-in!');</script>";
        }
    }

    // Proses Check Out
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_out') {
        $absensi_id = $_POST['absensi_id'];

        // Lakukan check-out
        $stmt = $pdo->prepare("UPDATE Absensi SET check_out = NOW() WHERE absensi_id = :absensi_id AND user_id = :user_id");
        $stmt->execute(['absensi_id' => $absensi_id, 'user_id' => $user_id]);
        header('Location: absensi.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
<div class="max-w-7xl mx-auto mt-10 p-6 bg-white shadow-md rounded-lg">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Absensi</h2>

    <!-- Tabel Absensi -->
    <table class="w-full table-auto border-collapse border border-gray-300 mb-6">
        <thead>
        <tr class="bg-gray-200 text-gray-700">
            <?php if ($user_role == 1 || $user_role == 2): ?>
                <th class="px-4 py-2 border border-gray-300">Nama Pekerja</th>
            <?php endif; ?>
            <th class="px-4 py-2 border border-gray-300">Nama Proyek</th>
            <th class="px-4 py-2 border border-gray-300">Check In</th>
            <th class="px-4 py-2 border border-gray-300">Check Out</th>
            <?php if ($user_role == 1 || $user_role == 2 || $user_role == 3): ?>
                <th class="px-4 py-2 border border-gray-300">Aksi</th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($absensi as $row): ?>
            <tr class="text-gray-700 hover:bg-gray-100">
                <?php if ($user_role == 1 || $user_role == 2): ?>
                    <td class="px-4 py-2 border border-gray-300"><?php echo htmlspecialchars($row['username'] ?? '-'); ?></td>
                <?php endif; ?>
                <td class="px-4 py-2 border border-gray-300"><?php echo htmlspecialchars($row['project_name']); ?></td>
                <td class="px-4 py-2 border border-gray-300 text-center"><?php echo $row['check_in'] ? date('Y-m-d H:i:s', strtotime($row['check_in'])) : '-'; ?></td>
                <td class="px-4 py-2 border border-gray-300 text-center"><?php echo $row['check_out'] ? date('Y-m-d H:i:s', strtotime($row['check_out'])) : '-'; ?></td>
                <td class="px-4 py-2 border border-gray-300 text-center">
                    <?php if ($user_role == 3 && !$row['check_out']): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="absensi_id" value="<?php echo $row['absensi_id']; ?>">
                            <button name="action" value="check_out" type="submit"
                                    class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">
                                Check Out
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Form Check In (Worker Only) -->
    <?php if ($user_role == 3): ?>
        <h3 class="text-lg font-bold text-gray-800 mb-4">Check In</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="check_in">
            <div>
                <label for="project_id" class="block text-gray-700 font-semibold">Pilih Proyek</label>
                <select name="project_id" id="project_id" required
                        class="block w-full mt-1 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Pilih Proyek --</option>
                    <?php
                    $stmt = $pdo->prepare("SELECT project_id, project_name FROM Projects");
                    $stmt->execute();
                    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($projects as $project): ?>
                        <option value="<?php echo $project['project_id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-between items-center mt-6">
                <button 
                    type="submit" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Check In
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
