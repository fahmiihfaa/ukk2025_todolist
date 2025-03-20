<?php
$koneksi = mysqli_connect("localhost", "root", "", "ukk2025_todolist");

date_default_timezone_set('Asia/Jakarta'); 

// tambah task
if (isset($_POST['add_task'])) {
    $task = $_POST['task'];
    $description = $_POST['description']; 
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $kategori_id = $_POST['kategori_id'];
    
    // Validasi tanggal agar tidak bisa menambahkan task untuk hari sebelumnya
    $today = date('Y-m-d');
    if (strtotime($due_date) < strtotime($today)) {
        echo "<script>alert('Tidak dapat menambahkan task untuk tanggal yang sudah lewat!')</script>";
        echo "<script>window.location='index.php';</script>";
        exit;
    }
    
    if (!empty($task) && !empty($priority) && !empty($due_date)) {
        mysqli_query($koneksi, "INSERT INTO task (task, description, priority, due_date, status, kategori_id) VALUES ('$task', '$description', '$priority', '$due_date','0', '$kategori_id')");
        
        echo "<script>alert('Task berhasil ditambahkan')</script>";
        echo "<script>window.location='index.php';</script>";
    } else {
        echo "<script>alert('Task gagal ditambahkan')</script>";
        echo "<script>window.location='index.php';</script>";
    }
}

// edit task
if (isset($_POST['edit_task'])) {
    $id = $_POST['id'];
    $task = $_POST['task'];
    $description = $_POST['description']; 
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $kategori_id = $_POST['kategori_id'];
    
    // Validasi tanggal agar tidak bisa mengubah task ke hari sebelumnya
    $today = date('Y-m-d');
    if (strtotime($due_date) < strtotime($today)) {
        echo "<script>alert('Tidak dapat mengubah task ke tanggal yang sudah lewat!')</script>";
        echo "<script>window.location='index.php';</script>";
        exit;
    }
    
    if (!empty($task) && !empty($priority) && !empty($due_date)) {
        mysqli_query($koneksi, "UPDATE task SET task = '$task', description = '$description', priority = '$priority', due_date = '$due_date', kategori_id = '$kategori_id' WHERE id = '$id'");
        
        echo "<script>alert('Task berhasil diperbarui')</script>";
        echo "<script>window.location='index.php';</script>";
    } else {
        echo "<script>alert('Task gagal diperbarui')</script>";
        echo "<script>window.location='index.php';</script>";
    }
}

// Buat tabel kategori jika belum ada
$check_kategori_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'kategori'");
if (mysqli_num_rows($check_kategori_table) == 0) {
    mysqli_query($koneksi, "CREATE TABLE kategori (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(50) NOT NULL
    )");
    
    // Tambahkan beberapa kategori default
    mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori) VALUES 
        ('Pekerjaan'), ('Pribadi'), ('Sekolah'), ('Belanja'), ('Lainnya')");
}

// Periksa apakah kolom kategori_id sudah ada di tabel task
$check_column = mysqli_query($koneksi, "SHOW COLUMNS FROM task LIKE 'kategori_id'");
if (mysqli_num_rows($check_column) == 0) {
    // Tambahkan kolom kategori_id
    mysqli_query($koneksi, "ALTER TABLE task ADD COLUMN kategori_id INT(11) DEFAULT 5");
    
    // Tambahkan foreign key
    mysqli_query($koneksi, "ALTER TABLE task ADD CONSTRAINT fk_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET DEFAULT");
}

// task selesai
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    mysqli_query($koneksi, "UPDATE task SET status = '1' WHERE id = '$id'");
    echo "<script>alert('Task berhasil diselesaikan')</script>";
    echo "<script>window.location='index.php';</script>";
}

// task undo
if (isset($_GET['undo'])) {
    $id = $_GET['undo'];
    mysqli_query($koneksi, "UPDATE task SET status = '0' WHERE id = '$id'");
    echo "<script>alert('Status task berhasil diubah menjadi belum selesai')</script>";
    echo "<script>window.location='index.php';</script>";
}

// hapus task
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM task WHERE id = '$id'");
    echo "<script>alert('Task berhasil dihapus')</script>";
    echo "<script>window.location='index.php';</script>";
}

// menampilkan task dengan pencarian, filter prioritas, dan pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$priority_filter = isset($_GET['priority_filter']) ? $_GET['priority_filter'] : '';
$kategori_filter = isset($_GET['kategori_filter']) ? $_GET['kategori_filter'] : '';
$where_clause = "";

// Membangun WHERE clause berdasarkan pencarian dan filter prioritas
$conditions = [];

if (!empty($search)) {
    $search = mysqli_real_escape_string($koneksi, $search);
    $conditions[] = "(task LIKE '%$search%' OR description LIKE '%$search%')";
}

if (!empty($priority_filter)) {
    $priority_filter = mysqli_real_escape_string($koneksi, $priority_filter);
    $conditions[] = "priority = '$priority_filter'";
}

if (!empty($kategori_filter)) {
    $kategori_filter = mysqli_real_escape_string($koneksi, $kategori_filter);
    $conditions[] = "kategori_id = '$kategori_filter'";
}

if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// Pagination
$per_page = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Menghitung total task untuk pagination
$count_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM task $where_clause");
$total_rows = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_rows / $per_page);

// Query untuk mengambil data dengan pagination
$result = mysqli_query($koneksi, "SELECT task.*, kategori.nama_kategori 
                                  FROM task 
                                  LEFT JOIN kategori ON task.kategori_id = kategori.id 
                                  $where_clause 
                                  ORDER BY status ASC, priority DESC, due_date ASC 
                                  LIMIT $start, $per_page");

// Ambil semua kategori untuk dropdown
$kategori_result = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Mendapatkan tanggal hari ini untuk validasi input
$today = date('Y-m-d');

// Hitung jumlah task berdasarkan status
$total_tasks = $total_rows; // Menggunakan total dari count query
$completed_tasks = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM task WHERE status = 1" . (!empty($where_clause) ? " AND " . substr($where_clause, 6) : ""));
$completed_count = mysqli_fetch_assoc($completed_tasks)['total'];
$incomplete_tasks = $total_tasks - $completed_count;
$completion_percentage = ($total_tasks > 0) ? round(($completed_count / $total_tasks) * 100) : 0;

// Fungsi untuk membuat URL pagination dengan mempertahankan parameter search dan priority_filter
function get_pagination_url($page, $search = '', $priority_filter = '', $kategori_filter = '') {
    $url = "index.php?page=" . $page;
    if (!empty($search)) {
        $url .= "&search=" . urlencode($search);
    }
    if (!empty($priority_filter)) {
        $url .= "&priority_filter=" . urlencode($priority_filter);
    }
    if (!empty($kategori_filter)) {
        $url .= "&kategori_filter=" . urlencode($kategori_filter);
    }
    return $url;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow | UKK RPL 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    
</head>
<body>
    <div class="container app-container">
        <!-- Header -->
        <div class="app-header text-center">
            <h1 class="app-title">
                <i class="fas fa-check-circle me-2"></i>TaskFlow
            </h1>
            <p class="mt-2 mb-0">Kelola tugas anda dengan efisien dan lancar</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $total_tasks; ?></div>
                        <div class="stat-label">Total Task</div>
                    </div>
                    <i class="fas fa-tasks fa-2x text-muted"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $completed_count; ?></div>
                        <div class="stat-label">Task Selesai</div>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $incomplete_tasks; ?></div>
                        <div class="stat-label">Task Belum Selesai</div>
                    </div>
                    <i class="fas fa-clock fa-2x text-warning"></i>
                </div>
            </div>
        </div>
        
        <!-- Progress -->
        <div class="task-form mb-4">
            <h6 class="mb-2">Progress Task</h6>
            <div class="progress mb-2">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $completion_percentage; ?>%" 
                    aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between">
                <small class="text-muted">Progress: <?php echo $completion_percentage; ?>%</small>
                <small class="text-muted"><?php echo $completed_count; ?>/<?php echo $total_tasks; ?> task selesai</small>
            </div>
        </div>
        
        <!-- Form -->
        <div class="task-form">
            <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Tambah Task Baru</h5>
            <form action="" method="post">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Nama Task</label>
                        <input type="text" name="task" class="form-control" placeholder="Masukan task baru..." autocomplete="off" autofocus required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Prioritas</label>
                        <select name="priority" class="form-select" required>
                            <option value="">--Pilih Prioritas--</option>
                            <option value="1">Low</option>
                            <option value="2">Medium</option>
                            <option value="3">High</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tenggat Waktu</label>
                        <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo $today; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            <option value="">--Pilih Kategori--</option>
                            <?php while ($kategori = mysqli_fetch_assoc($kategori_result)) { ?>
                                <option value="<?php echo $kategori['id']; ?>"><?php echo htmlspecialchars($kategori['nama_kategori']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" placeholder="Masukkan deskripsi task (opsional)..." rows="3"></textarea>
                    </div>
                </div>
                <button class="btn btn-add-task w-100" name="add_task">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Task
                </button>
            </form>
        </div>
        
        <!-- Search Box -->
        <div class="task-form search-container">
            <h5 class="mb-3"><i class="fas fa-search me-2"></i>Cari Task</h5>
            <form action="" method="get">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="position-relative d-flex">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control search-input" placeholder="Cari task atau deskripsi..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary search-button ms-2">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($search) || !empty($priority_filter) || !empty($kategori_filter)) { ?>
                                    <a href="index.php" class="btn btn-secondary search-clear w-30">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                            <?php } ?>
                        </div>
                    </div>
            
                    <div class="col-md-12">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select name="priority_filter" class="form-select">
                                    <option value="">Semua Prioritas</option>
                                    <option value="1" <?php echo ($priority_filter == '1') ? 'selected' : ''; ?>>Low</option>
                                    <option value="2" <?php echo ($priority_filter == '2') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="3" <?php echo ($priority_filter == '3') ? 'selected' : ''; ?>>High</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="kategori_filter" class="form-select">
                                    <option value="">Semua Kategori</option>
                                    <?php 
                                    // Reset pointer kategori
                                    mysqli_data_seek($kategori_result, 0);
                                    while ($kategori = mysqli_fetch_assoc($kategori_result)) { ?>
                                        <option value="<?php echo $kategori['id']; ?>" <?php echo ($kategori_filter == $kategori['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
    
            <?php if (!empty($search) || !empty($priority_filter) || !empty($kategori_filter)) { ?>
                <p class="search-results mt-2">
                    Menampilkan hasil: 
                    <?php if (!empty($search)) { ?>
                        pencarian untuk "<?php echo htmlspecialchars($search); ?>"
                    <?php } ?>
            
                    <?php if (!empty($search) && (!empty($priority_filter) || !empty($kategori_filter))) { ?>
                        dengan 
                    <?php } ?>
            
                    <?php if (!empty($priority_filter)) { ?>
                        prioritas <?php echo ($priority_filter == '1') ? 'Low' : (($priority_filter == '2') ? 'Medium' : 'High'); ?>
                    <?php } ?>
            
                    <?php if (!empty($priority_filter) && !empty($kategori_filter)) { ?>
                        dan 
                    <?php } ?>
            
                    <?php if (!empty($kategori_filter)) { 
                        // Ambil nama kategori
                        $kategori_name_query = mysqli_query($koneksi, "SELECT nama_kategori FROM kategori WHERE id = '$kategori_filter'");
                        $kategori_name = mysqli_fetch_assoc($kategori_name_query)['nama_kategori'];
                    ?>
                        kategori "<?php echo htmlspecialchars($kategori_name); ?>"
                    <?php } ?>
            
                    (<?php echo $total_tasks; ?> hasil)
                </p>
            <?php } ?>
        </div>
        
        <!-- Task List -->
        <div class="tasks-container">
            <h5 class="mb-3">
                <i class="fas fa-list-ul me-2"></i>Daftar Task
                <small class="text-muted">(Halaman <?php echo $page; ?> dari <?php echo max(1, $total_pages); ?>)</small>
            </h5>
            
            <?php if ($total_tasks > 0) { ?>
                <div class="table-responsive">
                    <table class="table task-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Task</th>
                                <th>Kategori</th>
                                <th>Prioritas</th>
                                <th>Tenggat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = (($page - 1) * $per_page) + 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                $is_completed = $row['status'] == 1;
                                $row_class = $is_completed ? 'completed-task' : '';
                                
                                // Set priority class
                                $priority_class = '';
                                $priority_text = '';
                                
                                if ($row['priority'] == 1) {
                                    $priority_class = 'priority-low';
                                    $priority_text = 'Low';
                                } elseif ($row['priority'] == 2) {
                                    $priority_class = 'priority-medium';
                                    $priority_text = 'Medium';
                                } else {
                                    $priority_class = 'priority-high';
                                    $priority_text = 'High';
                                }
                                
                                // Format date
                                $due_date = date('d M Y', strtotime($row['due_date']));
                                // Check if task is due today
                                $is_due_today = $row['due_date'] == date('Y-m-d');

                                // Tambahkan pengecekan apakah task sudah melewati tenggat waktu
                                $is_overdue = !$is_completed && strtotime($row['due_date']) < strtotime(date('Y-m-d'));

                                // Tentukan class untuk baris
                                $row_class = $is_completed ? 'completed-task' : '';
                                if ($is_overdue) {
                                    $row_class .= ' overdue-task';
                                }

                                // Modifikasi date_badge untuk menampilkan peringatan jika sudah melewati tenggat
                                $date_badge = '';
                                if ($is_due_today) {
                                    $date_badge = '<span class="badge bg-warning ms-2">Hari Ini</span>';
                                } elseif ($is_overdue) {
                                    $date_badge = '<span class="badge bg-danger ms-2">Tenggat Terlewati!</span>';
                                }

                                $description = !empty($row['description']) ? htmlspecialchars($row['description']) : '<em>Tidak ada deskripsi</em>';
                                
                                if (!empty($search)) {
                                    $description = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="bg-warning">$1</span>', $description);
                                }
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo $no++; ?></td>
                                <td class="task-column">
                                    <div class="task-content">
                                        <div class="task-name">
                                            <?php
                                            if (!empty($search)) {
                                                $highlighted_text = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="bg-warning">$1</span>', htmlspecialchars($row['task']));
                                                echo $highlighted_text;
                                            } else {
                                                echo htmlspecialchars($row['task']);
                                            }
                                            ?>
                                        </div>
                                        <div class="task-description">
                                            <?php echo $description; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="task-category">
                                        <?php echo htmlspecialchars($row['nama_kategori']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="task-priority <?php echo $priority_class; ?>">
                                        <?php echo $priority_text; ?>
                                    </span>
                                </td>
                                <td class="task-date">
                                    <i class="far fa-calendar-alt me-1"></i><?php echo $due_date; ?>
                                    <?php echo $date_badge; ?>
                                </td>
                                <td>
                                    <?php if ($is_completed) { ?>
                                        <span class="task-status status-complete">
                                            <i class="fas fa-check-circle me-1"></i>Selesai
                                        </span>
                                    <?php } else { ?>
                                        <span class="task-status status-incomplete">
                                            <i class="fas fa-clock me-1"></i>Belum Selesai
                                        </span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="d-flex">
                                        <?php if (!$is_completed) { ?>
                                            <a href="?complete=<?php echo $row['id']; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($priority_filter) ? '&priority_filter='.urlencode($priority_filter) : ''; ?><?php echo '&page='.$page; ?>" class="btn btn-task btn-complete" title="Tandai Selesai">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php } else { ?>
                                            <a href="?undo=<?php echo $row['id']; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($priority_filter) ? '&priority_filter='.urlencode($priority_filter) : ''; ?><?php echo '&page='.$page; ?>" class="btn btn-task btn-undo" title="Batalkan Selesai">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                        <?php } ?>
                                        
                                        <button type="button" class="btn btn-task btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>" title="Edit Task">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <a href="?delete=<?php echo $row['id']; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($priority_filter) ? '&priority_filter='.urlencode($priority_filter) : ''; ?><?php echo '&page='.$page; ?>" class="btn btn-task btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus task ini?')" title="Hapus Task">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Modal Edit -->
                            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $row['id']; ?>">
                                                <i class="fas fa-edit me-2"></i>Edit Task
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="" method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="task" class="form-label">Nama Task</label>
                                                    <input type="text" class="form-control" id="task" name="task" value="<?php echo htmlspecialchars($row['task']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="description" class="form-label">Deskripsi</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="kategori_id" class="form-label">Kategori</label>
                                                    <select class="form-select" id="kategori_id" name="kategori_id" required>
                                                        <?php 
                                                        // Reset pointer kategori
                                                        mysqli_data_seek($kategori_result, 0);
                                                        while ($kategori = mysqli_fetch_assoc($kategori_result)) { ?>
                                                            <option value="<?php echo $kategori['id']; ?>" <?php echo ($row['kategori_id'] == $kategori['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($kategori['nama_kategori']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="priority" class="form-label">Prioritas</label>
                                                    <select class="form-select" id="priority" name="priority" required>
                                                        <option value="1" <?php echo ($row['priority'] == 1) ? 'selected' : ''; ?>>Low</option>
                                                        <option value="2" <?php echo ($row['priority'] == 2) ? 'selected' : ''; ?>>Medium</option>
                                                        <option value="3" <?php echo ($row['priority'] == 3) ? 'selected' : ''; ?>>High</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="due_date" class="form-label">Tenggat Waktu</label>
                                                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $row['due_date']; ?>" min="<?php echo $today; ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary" name="edit_task">
                                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1) { ?>
                <nav aria-label="Task pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- First Page -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo get_pagination_url(1, $search, $priority_filter); ?>" aria-label="First">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        
                        <!-- Previous Page -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo get_pagination_url($page - 1, $search, $priority_filter); ?>" aria-label="Previous">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <?php
                        // Determine the range of page numbers to display
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        // Display page numbers
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . get_pagination_url($i, $search, $priority_filter) . '">' . $i . '</a></li>';
                        }
                        ?>
                        
                        <!-- Next Page -->
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo get_pagination_url($page + 1, $search, $priority_filter); ?>" aria-label="Next">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        
                        <!-- Last Page -->
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo get_pagination_url($total_pages, $search, $priority_filter); ?>" aria-label="Last">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php } ?>
                
            <?php } else { ?>
                <!-- Empty state when no tasks -->
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <?php if (!empty($search) || !empty($priority_filter)) { ?>
                        <h5>Tidak Ada Hasil</h5>
                        <p>
                            <?php if (!empty($search) && !empty($priority_filter)) { ?>
                                Tidak ada task yang sesuai dengan pencarian "<?php echo htmlspecialchars($search); ?>" dan prioritas <?php echo ($priority_filter == '1') ? 'Low' : (($priority_filter == '2') ? 'Medium' : 'High'); ?>.
                            <?php } elseif (!empty($search)) { ?>
                                Tidak ada task yang sesuai dengan pencarian "<?php echo htmlspecialchars($search); ?>".
                            <?php } else { ?>
                                Tidak ada task dengan prioritas <?php echo ($priority_filter == '1') ? 'Low' : (($priority_filter == '2') ? 'Medium' : 'High'); ?>.
                            <?php } ?>
                        </p>
                        <a href="index.php" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Semua Task
                        </a>
                    <?php } else { ?>
                        <h5>Belum Ada Task</h5>
                        <p>Tambahkan task baru untuk mulai mencatat aktivitas Anda.</p>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-4 text-muted">
            <p>UKK RPL 2025 &copy; TaskFlow - Aplikasi Todo List</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>