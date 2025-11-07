<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getUserId();
$flash = getFlashMessage();

// Xử lý form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $goal_name = sanitize($_POST['goal_name']);
        $description = sanitize($_POST['description']);
        $target_date = !empty($_POST['target_date']) ? sanitize($_POST['target_date']) : null;
        
        $stmt = $conn->prepare("INSERT INTO goals (user_id, goal_name, description, target_date, status, progress_percentage) VALUES (?, ?, ?, ?, 'not_started', 0)");
        $stmt->bind_param("isss", $user_id, $goal_name, $description, $target_date);
        
        if ($stmt->execute()) {
            setFlashMessage('Thêm mục tiêu thành công!', 'success');
        } else {
            setFlashMessage('Có lỗi xảy ra khi thêm mục tiêu!', 'danger');
        }
        redirect('goals.php');
    } 
    elseif ($action === 'update_progress') {
        $id = intval($_POST['id']);
        $progress = intval($_POST['progress_percentage']);
        $status = sanitize($_POST['status']);
        
        $progress = max(0, min(100, $progress)); // Giới hạn 0-100
        
        $stmt = $conn->prepare("UPDATE goals SET progress_percentage = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("isii", $progress, $status, $id, $user_id);
        
        if ($stmt->execute()) {
            setFlashMessage('Cập nhật tiến độ thành công!', 'success');
        } else {
            setFlashMessage('Có lỗi xảy ra khi cập nhật!', 'danger');
        }
        redirect('goals.php');
    }
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
            setFlashMessage('Xóa mục tiêu thành công!', 'success');
        } else {
            setFlashMessage('Có lỗi xảy ra khi xóa mục tiêu!', 'danger');
        }
        redirect('goals.php');
    }
}

// Lấy danh sách mục tiêu
$goals_query = "SELECT * FROM goals WHERE user_id = ? ORDER BY 
                FIELD(status, 'in_progress', 'not_started', 'completed', 'cancelled'),
                target_date ASC";
$stmt = $conn->prepare($goals_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goals = $stmt->get_result();

// Thống kê mục tiêu
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'not_started' THEN 1 ELSE 0 END) as not_started
                FROM goals WHERE user_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mục Tiêu - Quản Lý Học Tập</title>
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
                    <h1 class="h2">
                        <i class="fas fa-trophy"></i> Mục Tiêu Học Tập Dài Hạn
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                        <i class="fas fa-plus"></i> Thêm mục tiêu
                    </button>
                </div>
                
                <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Thống kê mục tiêu -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="card-body">
                                <h3><?= $stats['total'] ?></h3>
                                <p class="mb-0">Tổng mục tiêu</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                            <div class="card-body">
                                <h3><?= $stats['in_progress'] ?></h3>
                                <p class="mb-0">Đang thực hiện</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                            <div class="card-body">
                                <h3><?= $stats['not_started'] ?></h3>
                                <p class="mb-0">Chưa bắt đầu</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                            <div class="card-body">
                                <h3><?= $stats['completed'] ?></h3>
                                <p class="mb-0">Hoàn thành</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Danh sách mục tiêu -->
                <div class="row">
                    <?php if ($goals->num_rows > 0): ?>
                        <?php while ($goal = $goals->fetch_assoc()): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-bullseye text-primary me-2"></i>
                                            <?= htmlspecialchars($goal['goal_name']) ?>
                                        </h5>
                                        <?= getGoalStatusBadge($goal['status']) ?>
                                    </div>
                                    
                                    <?php if ($goal['description']): ?>
                                    <p class="card-text text-muted small"><?= htmlspecialchars($goal['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($goal['target_date']): ?>
                                    <p class="small mb-2">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        Mục tiêu: <?= formatDate($goal['target_date']) ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <!-- Progress bar -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Tiến độ</small>
                                            <small class="fw-bold"><?= $goal['progress_percentage'] ?>%</small>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= $goal['progress_percentage'] ?>%; 
                                                        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);"
                                                 aria-valuenow="<?= $goal['progress_percentage'] ?>" 
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateProgressModal<?= $goal['id'] ?>">
                                            <i class="fas fa-edit"></i> Cập nhật tiến độ
                                        </button>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Bạn có chắc muốn xóa mục tiêu này?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $goal['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-footer text-muted small">
                                    Tạo: <?= formatDate($goal['created_at']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal cập nhật tiến độ -->
                        <div class="modal fade" id="updateProgressModal<?= $goal['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Cập Nhật Tiến Độ</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="update_progress">
                                            <input type="hidden" name="id" value="<?= $goal['id'] ?>">
                                            
                                            <h6><?= htmlspecialchars($goal['goal_name']) ?></h6>
                                            <hr>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Tiến độ (%) <span class="text-danger">*</span></label>
                                                <input type="number" name="progress_percentage" class="form-control" 
                                                       min="0" max="100" value="<?= $goal['progress_percentage'] ?>" required>
                                                <small class="text-muted">Nhập từ 0 đến 100</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                                <select name="status" class="form-select" required>
                                                    <option value="not_started" <?= $goal['status'] == 'not_started' ? 'selected' : '' ?>>Chưa bắt đầu</option>
                                                    <option value="in_progress" <?= $goal['status'] == 'in_progress' ? 'selected' : '' ?>>Đang thực hiện</option>
                                                    <option value="completed" <?= $goal['status'] == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                                    <option value="cancelled" <?= $goal['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Lưu cập nhật
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Activities/Trophy.png" 
                                         alt="Trophy" width="150" height="150" class="mb-3">
                                    <h5>Chưa có mục tiêu nào!</h5>
                                    <p class="text-muted">Hãy tạo mục tiêu đầu tiên để bắt đầu hành trình chinh phục ước mơ của bạn!</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                                        <i class="fas fa-plus"></i> Thêm mục tiêu ngay
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-4">
                    <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Hand%20gestures/Flexed%20Biceps.png" 
                         width="60" height="60" alt="Strong">
                    <p class="text-muted mt-2">Kiên trì theo đuổi mục tiêu, thành công sẽ đến!</p>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal Thêm Mục Tiêu -->
    <div class="modal fade" id="addGoalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm Mục Tiêu Mới</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên mục tiêu <span class="text-danger">*</span></label>
                            <input type="text" name="goal_name" class="form-control" 
                                   placeholder="VD: Đạt GPA 3.5 cuối kỳ" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả chi tiết</label>
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="Mô tả chi tiết về mục tiêu này..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ngày mục tiêu</label>
                            <input type="date" name="target_date" class="form-control">
                            <small class="text-muted">Tùy chọn: Chọn ngày dự kiến hoàn thành</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Tạo mục tiêu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
