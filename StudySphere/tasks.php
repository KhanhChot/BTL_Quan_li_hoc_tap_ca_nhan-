<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getUserId();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $task_name = sanitize($_POST['task_name']);
            $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
            $description = sanitize($_POST['description']);
            $deadline = $_POST['deadline'];
            $priority = $_POST['priority'];
            
            if (empty($task_name)) {
                $error = 'Vui lòng nhập tên công việc';
            } else {
                $stmt = $conn->prepare("INSERT INTO tasks (user_id, subject_id, task_name, description, deadline, priority) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $user_id, $subject_id, $task_name, $description, $deadline, $priority);
                
                if ($stmt->execute()) {
                    setFlashMessage('Thêm công việc thành công', 'success');
                    redirect('tasks.php');
                }
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id']);
            $task_name = sanitize($_POST['task_name']);
            $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
            $description = sanitize($_POST['description']);
            $deadline = $_POST['deadline'];
            $priority = $_POST['priority'];
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE tasks SET task_name = ?, subject_id = ?, description = ?, deadline = ?, priority = ?, status = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sissssii", $task_name, $subject_id, $description, $deadline, $priority, $status, $id, $user_id);
            
            if ($stmt->execute()) {
                if ($status === 'completed') {
                    $conn->query("UPDATE tasks SET completed_at = NOW() WHERE id = $id");
                }
                setFlashMessage('Cập nhật công việc thành công', 'success');
                redirect('tasks.php');
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            
            if ($stmt->execute()) {
                setFlashMessage('Xóa công việc thành công', 'success');
                redirect('tasks.php');
            }
        } elseif ($action === 'toggle_status') {
            $id = intval($_POST['id']);
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $status, $id, $user_id);
            $success = $stmt->execute();
            
            if ($success && $status === 'completed') {
                $conn->query("UPDATE tasks SET completed_at = NOW() WHERE id = $id");
            }
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);
                exit();
            }
            
            setFlashMessage('Cập nhật trạng thái thành công', 'success');
            redirect('tasks.php');
        } elseif ($action === 'add_progress') {
            $task_id = intval($_POST['task_id']);
            $notes = sanitize($_POST['notes']);
            
            $stmt = $conn->prepare("INSERT INTO progress_logs (task_id, user_id, notes) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $task_id, $user_id, $notes);
            
            if ($stmt->execute()) {
                setFlashMessage('Đã thêm ghi chú tiến độ', 'success');
                redirect('tasks.php');
            }
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$where_clause = "WHERE t.user_id = $user_id";

if ($filter === 'today') {
    $today = date('Y-m-d');
    $where_clause .= " AND DATE(t.deadline) = '$today'";
} elseif ($filter === 'pending') {
    $where_clause .= " AND t.status = 'pending'";
} elseif ($filter === 'completed') {
    $where_clause .= " AND t.status = 'completed'";
} elseif ($filter === 'overdue') {
    $where_clause .= " AND t.deadline < NOW() AND t.status != 'completed'";
}

$tasks = $conn->query("SELECT t.*, s.subject_name, s.color 
                       FROM tasks t 
                       LEFT JOIN subjects s ON t.subject_id = s.id 
                       $where_clause 
                       ORDER BY t.deadline ASC");

$subjects = $conn->query("SELECT * FROM subjects WHERE user_id = $user_id ORDER BY subject_name");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Công Việc - Hệ Thống Quản Lý Học Tập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-tasks"></i> Quản Lý Công Việc</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="fas fa-plus"></i> Thêm công việc
                    </button>
                </div>
                
                <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="btn-group mb-3" role="group">
                    <a href="tasks.php?filter=all" class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                        <i class="fas fa-list"></i> Tất cả
                    </a>
                    <a href="tasks.php?filter=today" class="btn btn-outline-primary <?= $filter === 'today' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-day"></i> Hôm nay
                    </a>
                    <a href="tasks.php?filter=pending" class="btn btn-outline-warning <?= $filter === 'pending' ? 'active' : '' ?>">
                        <i class="fas fa-clock"></i> Chưa làm
                    </a>
                    <a href="tasks.php?filter=completed" class="btn btn-outline-success <?= $filter === 'completed' ? 'active' : '' ?>">
                        <i class="fas fa-check"></i> Hoàn thành
                    </a>
                    <a href="tasks.php?filter=overdue" class="btn btn-outline-danger <?= $filter === 'overdue' ? 'active' : '' ?>">
                        <i class="fas fa-exclamation-triangle"></i> Quá hạn
                    </a>
                </div>
                
                <?php if ($tasks->num_rows > 0): ?>
                    <?php while ($task = $tasks->fetch_assoc()): ?>
                    <div class="task-item <?= $task['status'] === 'completed' ? 'completed' : '' ?>">
                        <div class="row align-items-center">
                            <div class="col-md-1">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $task['status'] === 'completed' ? 'pending' : 'completed' ?>">
                                    <button type="submit" class="btn btn-link p-0" style="font-size: 1.5rem;">
                                        <i class="fas <?= $task['status'] === 'completed' ? 'fa-check-circle text-success' : 'fa-circle text-muted' ?>"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-7">
                                <h5 class="task-title mb-1"><?= htmlspecialchars($task['task_name']) ?></h5>
                                <?php if ($task['description']): ?>
                                <p class="text-muted small mb-1"><?= htmlspecialchars($task['description']) ?></p>
                                <?php endif; ?>
                                <div>
                                    <?php if ($task['subject_name']): ?>
                                    <span class="badge" style="background-color: <?= $task['color'] ?>">
                                        <?= htmlspecialchars($task['subject_name']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?= getPriorityBadge($task['priority']) ?>
                                    <?= getStatusBadge($task['status']) ?>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <small class="<?= getDeadlineClass($task['deadline'], $task['status']) ?>">
                                    <i class="fas fa-clock"></i><br>
                                    <?= formatDateTime($task['deadline']) ?>
                                </small>
                            </div>
                            <div class="col-md-2 text-end">
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="addProgress(<?= $task['id'] ?>)">
                                    <i class="fas fa-sticky-note"></i> Ghi chú
                                </button>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editTask(<?= htmlspecialchars(json_encode($task)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Bạn có chắc muốn xóa công việc này?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> Không có công việc nào
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm Công Việc Mới</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên công việc <span class="text-danger">*</span></label>
                            <input type="text" name="task_name" class="form-control" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Môn học</label>
                                    <select name="subject_id" class="form-select">
                                        <option value="">-- Không có --</option>
                                        <?php $subjects->data_seek(0); while ($subject = $subjects->fetch_assoc()): ?>
                                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Deadline</label>
                                    <input type="datetime-local" name="deadline" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Độ ưu tiên</label>
                            <select name="priority" class="form-select">
                                <option value="low">Thấp</option>
                                <option value="medium" selected>Trung bình</option>
                                <option value="high">Cao</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm công việc</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Chỉnh Sửa Công Việc</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên công việc <span class="text-danger">*</span></label>
                            <input type="text" name="task_name" id="edit_task_name" class="form-control" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Môn học</label>
                                    <select name="subject_id" id="edit_subject_id" class="form-select">
                                        <option value="">-- Không có --</option>
                                        <?php $subjects->data_seek(0); while ($subject = $subjects->fetch_assoc()): ?>
                                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Deadline</label>
                                    <input type="datetime-local" name="deadline" id="edit_deadline" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Độ ưu tiên</label>
                                    <select name="priority" id="edit_priority" class="form-select">
                                        <option value="low">Thấp</option>
                                        <option value="medium">Trung bình</option>
                                        <option value="high">Cao</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select name="status" id="edit_status" class="form-select">
                                        <option value="pending">Chưa làm</option>
                                        <option value="in_progress">Đang làm</option>
                                        <option value="completed">Hoàn thành</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="progressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-sticky-note"></i> Thêm Ghi Chú Tiến Độ</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_progress">
                        <input type="hidden" name="task_id" id="progress_task_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="notes" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu ghi chú</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
    function editTask(task) {
        document.getElementById('edit_id').value = task.id;
        document.getElementById('edit_task_name').value = task.task_name;
        document.getElementById('edit_subject_id').value = task.subject_id || '';
        document.getElementById('edit_description').value = task.description || '';
        document.getElementById('edit_priority').value = task.priority;
        document.getElementById('edit_status').value = task.status;
        
        if (task.deadline) {
            const deadline = new Date(task.deadline);
            const formatted = deadline.toISOString().slice(0, 16);
            document.getElementById('edit_deadline').value = formatted;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
        modal.show();
    }
    
    function addProgress(taskId) {
        document.getElementById('progress_task_id').value = taskId;
        const modal = new bootstrap.Modal(document.getElementById('progressModal'));
        modal.show();
    }
    </script>
</body>
</html>
