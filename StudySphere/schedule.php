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
        $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
        $day_of_week = sanitize($_POST['day_of_week']);
        $start_time = sanitize($_POST['start_time']);
        $end_time = sanitize($_POST['end_time']);
        $location = sanitize($_POST['location']);
        $notes = sanitize($_POST['notes']);
        $week = !empty($_POST['week']) ? intval($_POST['week']) : null;
        $month = !empty($_POST['month']) ? intval($_POST['month']) : null;
        $year = !empty($_POST['year']) ? intval($_POST['year']) : date('Y');

        $stmt = $conn->prepare("INSERT INTO schedule (user_id, subject_id, day_of_week, start_time, end_time, location, notes, week, month, year) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssssiii", $user_id, $subject_id, $day_of_week, $start_time, $end_time, $location, $notes, $week, $month, $year);

        if ($stmt->execute()) {
            setFlashMessage('Thêm lịch học thành công!', 'success');
        } else {
            setFlashMessage('Có lỗi xảy ra khi thêm lịch học!', 'danger');
        }
        redirect('schedule.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM schedule WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            setFlashMessage('Xóa lịch học thành công!', 'success');
        } else {
            setFlashMessage('Có lỗi xảy ra khi xóa lịch học!', 'danger');
        }
        redirect('schedule.php');
    }
}

// Lấy danh sách môn học
$subjects = $conn->query("SELECT * FROM subjects WHERE user_id = $user_id ORDER BY subject_name");

// Lấy lịch học
$schedule_query = "SELECT sch.*, s.subject_name, s.color 
                   FROM schedule sch
                   LEFT JOIN subjects s ON sch.subject_id = s.id
                   WHERE sch.user_id = ?
                   ORDER BY 
                       FIELD(sch.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                       sch.start_time";
$stmt = $conn->prepare($schedule_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$schedules = $stmt->get_result();

// Tổ chức lịch theo ngày
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$schedule_by_day = array_fill_keys($days, []);

$schedules->data_seek(0);
while ($sch = $schedules->fetch_assoc()) {
    $schedule_by_day[$sch['day_of_week']][] = $sch;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Học - Quản Lý Học Tập</title>
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
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-calendar-alt"></i> Lịch Học / Thời Khóa Biểu
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                        <i class="fas fa-plus"></i> Thêm lịch học
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= $flash['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="calendar-view">
                    <div class="row g-3">
                        <?php foreach ($days as $day): ?>
                            <div class="col-md-12 col-lg-6 col-xl-4">
                                <div class="card h-100">
                                    <div class="card-header text-white"
                                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-day me-2"></i>
                                            <?= getDayOfWeekVietnamese($day) ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($schedule_by_day[$day])): ?>
                                            <?php foreach ($schedule_by_day[$day] as $sch): ?>
                                                <div class="schedule-item mb-2 p-2 rounded"
                                                    style="border-left: 4px solid <?= $sch['color'] ?? '#667eea' ?>; background: #f8f9fa;">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1 fw-bold">
                                                                <?= htmlspecialchars($sch['subject_name'] ?? 'Không rõ môn') ?></h6>
                                                            <?php if ($sch['week'] || $sch['month'] || $sch['year']): ?>
                                                                <p class="mb-1 small text-secondary">
                                                                    <i class="fas fa-calendar-week me-1"></i>
                                                                    <?= $sch['week'] ? "Tuần {$sch['week']}, " : '' ?>
                                                                    <?= $sch['month'] ? "Tháng {$sch['month']}, " : '' ?>
                                                                    <?= $sch['year'] ? $sch['year'] : '' ?>
                                                                </p>
                                                            <?php endif; ?>
                                                            <p class="mb-1 small">
                                                                <i class="fas fa-clock me-1"></i>
                                                                <?= substr($sch['start_time'], 0, 5) ?> -
                                                                <?= substr($sch['end_time'], 0, 5) ?>
                                                            </p>
                                                            <?php if ($sch['location']): ?>
                                                                <p class="mb-1 small">
                                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                                    <?= htmlspecialchars($sch['location']) ?>
                                                                </p>
                                                            <?php endif; ?>
                                                            <?php if ($sch['notes']): ?>
                                                                <p class="mb-0 small text-muted fst-italic">
                                                                    <?= htmlspecialchars($sch['notes']) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <form method="POST" style="display: inline;"
                                                            onsubmit="return confirm('Bạn có chắc muốn xóa lịch học này?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?= $sch['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center text-muted py-3">
                                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                                <p class="small mb-0">Chưa có lịch học</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Travel%20and%20places/Alarm%20Clock.png"
                        width="60" height="60" alt="Clock">
                    <p class="text-muted mt-2">Quản lý thời gian hiệu quả để thành công!</p>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Thêm Lịch Học -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm Lịch Học Mới</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tuần</label>
                                    <select name="week" class="form-select">
                                        <option value="">-- Chọn tuần --</option>
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                            <option value="<?= $i ?>">Tuần <?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tháng</label>
                                    <select name="month" class="form-select">
                                        <option value="">-- Chọn tháng --</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?= $i ?>">Tháng <?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Năm</label>
                                    <input type="number" name="year" class="form-control" value="<?= date('Y') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Môn học</label>
                            <select name="subject_id" class="form-select">
                                <option value="">-- Không chọn --</option>
                                <?php $subjects->data_seek(0);
                                while ($subject = $subjects->fetch_assoc()): ?>
                                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Tùy chọn: Chọn môn học liên quan</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Thứ <span class="text-danger">*</span></label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="Monday">Thứ Hai</option>
                                <option value="Tuesday">Thứ Ba</option>
                                <option value="Wednesday">Thứ Tư</option>
                                <option value="Thursday">Thứ Năm</option>
                                <option value="Friday">Thứ Sáu</option>
                                <option value="Saturday">Thứ Bảy</option>
                                <option value="Sunday">Chủ Nhật</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Giờ bắt đầu <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Giờ kết thúc <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa điểm</label>
                            <input type="text" name="location" class="form-control"
                                placeholder="VD: Phòng A101, Giảng đường B">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="notes" class="form-control" rows="2"
                                placeholder="Ghi chú bổ sung (nếu có)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu lịch học
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