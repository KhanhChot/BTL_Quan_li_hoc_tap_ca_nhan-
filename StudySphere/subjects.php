<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getUserId();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $subject_name = sanitize($_POST['subject_name']);
            $subject_code = sanitize($_POST['subject_code']);
            $color = sanitize($_POST['color']);
            $credits = intval($_POST['credits']);
            $description = sanitize($_POST['description']);
            
            if (empty($subject_name)) {
                $error = 'Vui lòng nhập tên môn học';
            } else {
                $stmt = $conn->prepare("INSERT INTO subjects (user_id, subject_name, subject_code, color, credits, description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssis", $user_id, $subject_name, $subject_code, $color, $credits, $description);
                
                if ($stmt->execute()) {
                    setFlashMessage('Thêm môn học thành công', 'success');
                    redirect('subjects.php');
                } else {
                    $error = 'Có lỗi xảy ra';
                }
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id']);
            $subject_name = sanitize($_POST['subject_name']);
            $subject_code = sanitize($_POST['subject_code']);
            $color = sanitize($_POST['color']);
            $credits = intval($_POST['credits']);
            $description = sanitize($_POST['description']);
            
            $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, color = ?, credits = ?, description = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssisii", $subject_name, $subject_code, $color, $credits, $description, $id, $user_id);
            
            if ($stmt->execute()) {
                setFlashMessage('Cập nhật môn học thành công', 'success');
                redirect('subjects.php');
            } else {
                $error = 'Có lỗi xảy ra';
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            
            if ($stmt->execute()) {
                setFlashMessage('Xóa môn học thành công', 'success');
                redirect('subjects.php');
            }
        }
    }
}

$subjects = $conn->query("SELECT * FROM subjects WHERE user_id = $user_id ORDER BY created_at DESC");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Môn Học - Hệ Thống Quản Lý Học Tập</title>
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
                    <h1 class="h2"><i class="fas fa-book"></i> Quản Lý Môn Học</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus"></i> Thêm môn học
                    </button>
                </div>
                
                <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <?php if ($subjects->num_rows > 0): ?>
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card" style="border-left: 5px solid <?= $subject['color'] ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($subject['subject_name']) ?></h5>
                                        <span class="badge" style="background-color: <?= $subject['color'] ?>">
                                            <?= $subject['credits'] ?> tín chỉ
                                        </span>
                                    </div>
                                    
                                    <?php if ($subject['subject_code']): ?>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($subject['subject_code']) ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($subject['description']): ?>
                                    <p class="card-text small"><?= htmlspecialchars($subject['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editSubject(<?= htmlspecialchars(json_encode($subject)) ?>)">
                                            <i class="fas fa-edit"></i> Sửa
                                        </button>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Bạn có chắc muốn xóa môn học này?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $subject['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> Chưa có môn học nào. Hãy thêm môn học mới!
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm Môn Học Mới</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên môn học <span class="text-danger">*</span></label>
                            <input type="text" name="subject_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mã môn học</label>
                            <input type="text" name="subject_code" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Số tín chỉ</label>
                            <input type="number" name="credits" class="form-control" value="3" min="1" max="10">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Màu sắc</label>
                            <input type="hidden" name="color" id="subject_color" value="#3B82F6">
                            <div class="color-picker-container">
                                <div class="color-option selected" data-color="#3B82F6" style="background-color: #3B82F6"></div>
                                <div class="color-option" data-color="#10B981" style="background-color: #10B981"></div>
                                <div class="color-option" data-color="#F59E0B" style="background-color: #F59E0B"></div>
                                <div class="color-option" data-color="#EF4444" style="background-color: #EF4444"></div>
                                <div class="color-option" data-color="#8B5CF6" style="background-color: #8B5CF6"></div>
                                <div class="color-option" data-color="#EC4899" style="background-color: #EC4899"></div>
                                <div class="color-option" data-color="#14B8A6" style="background-color: #14B8A6"></div>
                                <div class="color-option" data-color="#F97316" style="background-color: #F97316"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm môn học</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Chỉnh Sửa Môn Học</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên môn học <span class="text-danger">*</span></label>
                            <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mã môn học</label>
                            <input type="text" name="subject_code" id="edit_subject_code" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Số tín chỉ</label>
                            <input type="number" name="credits" id="edit_credits" class="form-control" min="1" max="10">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Màu sắc</label>
                            <input type="hidden" name="color" id="edit_color">
                            <div class="color-picker-container">
                                <div class="color-option" data-color="#3B82F6" style="background-color: #3B82F6"></div>
                                <div class="color-option" data-color="#10B981" style="background-color: #10B981"></div>
                                <div class="color-option" data-color="#F59E0B" style="background-color: #F59E0B"></div>
                                <div class="color-option" data-color="#EF4444" style="background-color: #EF4444"></div>
                                <div class="color-option" data-color="#8B5CF6" style="background-color: #8B5CF6"></div>
                                <div class="color-option" data-color="#EC4899" style="background-color: #EC4899"></div>
                                <div class="color-option" data-color="#14B8A6" style="background-color: #14B8A6"></div>
                                <div class="color-option" data-color="#F97316" style="background-color: #F97316"></div>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
    function editSubject(subject) {
        document.getElementById('edit_id').value = subject.id;
        document.getElementById('edit_subject_name').value = subject.subject_name;
        document.getElementById('edit_subject_code').value = subject.subject_code || '';
        document.getElementById('edit_credits').value = subject.credits;
        document.getElementById('edit_color').value = subject.color;
        document.getElementById('edit_description').value = subject.description || '';
        
        document.querySelectorAll('#editSubjectModal .color-option').forEach(option => {
            option.classList.remove('selected');
            if (option.dataset.color === subject.color) {
                option.classList.add('selected');
            }
        });
        
        const modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
        modal.show();
    }
    
    document.querySelectorAll('#editSubjectModal .color-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('#editSubjectModal .color-option').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('edit_color').value = this.dataset.color;
        });
    });
    </script>
</body>
</html>
