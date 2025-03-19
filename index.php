<?php
$koneksi = mysqli_connect("localhost", "root", "", "ukk2025_todolist");

// tambah task
if (isset($_POST['add_task'])) {
    $task = $_POST['task'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    
    if (!empty($task) && !empty($priority) && !empty($due_date)) {
        mysqli_query($koneksi, "INSERT INTO task (task, priority, due_date, status) VALUES ('$task', '$priority', '$due_date','0')");
        
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
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    
    if (!empty($task) && !empty($priority) && !empty($due_date)) {
        mysqli_query($koneksi, "UPDATE task SET task = '$task', priority = '$priority', due_date = '$due_date' WHERE id = '$id'");
        
        echo "<script>alert('Task berhasil diperbarui')</script>";
        echo "<script>window.location='index.php';</script>";
    } else {
        echo "<script>alert('Task gagal diperbarui')</script>";
        echo "<script>window.location='index.php';</script>";
    }
}

// task selesai
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    mysqli_query($koneksi, "UPDATE task SET status = '1' WHERE id = '$id'");
    echo "<script>alert('Task berhasil diselesaikan')</script>";
    echo "<script>window.location='index.php';</script>";
}

// hapus task
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM task WHERE id = '$id'");
    echo "<script>alert('Task berhasil dihapus')</script>";
    echo "<script>window.location='index.php';</script>";
}

// menampilkan task
$result = mysqli_query($koneksi, "SELECT * FROM task ORDER BY status ASC, priority DESC, due_date ASC");
?>

tolong jelaskan dan sebutkan fungsi dari kode di atas

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Todo List | UKK RPL 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container mt-2">
        <h2 class="text-center mb-4">Aplikasi To Do List</h2>
        <form action="" method="post" class="border rounded bg-light p-2">
            <label class="form-label">Nama Task</label>
            <input type="text" name="task" class="form-control" placeholder="Masukan Task Baru" autocomplete="off" autofocus required>
            <label class="form-label">Prioritas</label>
            <select name="priority" class="form-control" required>
                <option value="">--Pilih Prioritas--</option>
                <option value="1">Low</option>
                <option value="2">Medium</option>
                <option value="3">High</option>
            </select>
            <label class="form-label">Tanggal</label>
            <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            <button class="btn btn-primary w-100 mt-2" name="add_task">Tambah Task</button>
        </form>
        <br>
        
        <!-- Tabel untuk menampilkan data -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Task</th>
                    <th>Priority</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <?php
            if (mysqli_num_rows($result) > 0) {
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)) {
            ?>
            <tr>
                <td><?php echo $no++ ?></td>
                <td><?php echo $row['task']?></td>
                <td><?php 
                    if ($row['priority'] == 1) {
                        echo "Low";
                    } elseif ($row['priority'] == 2) {
                        echo "Medium";
                    } else {
                        echo "High";
                    }
                ?></td>
                <td><?php echo $row['due_date']?></td>
                <td><?php
                    if ($row['status'] == 0) {
                        echo "<span style='color: red;'>Belum Selesai</span>";
                    } else {
                        echo "<span style='color: green;'>Selesai</span>";
                    }
                ?></td>
                <td>
                    <?php if ($row['status'] == 0) { ?>
                        <a href="?complete=<?php echo $row['id'] ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Selesai</a>
                    <?php } ?>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <a href="?delete=<?php echo $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus task ini?')"><i class="fas fa-trash"></i> Hapus</a>
                </td>
            </tr>
            
            <!-- Modal Edit untuk setiap task -->
            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-dark">
                            <h5 class="modal-title" id="editModalLabel<?php echo $row['id']; ?>">Edit Task</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <div class="mb-3">
                                    <label for="task" class="form-label">Nama Task</label>
                                    <input type="text" class="form-control" id="task" name="task" value="<?php echo $row['task']; ?>" required>
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
                                    <label for="due_date" class="form-label">Tanggal</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $row['due_date']; ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-info" name="edit_task">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php
                }
            }
            ?>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>