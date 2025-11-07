<?php
/**
 * FILE CHỨA CÁC HÀM HELPER
 * 
 * Các hàm tiện ích dùng chung cho toàn bộ hệ thống
 */

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('index.php');
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['username'] ?? 'Guest';
}

function getFullName() {
    return $_SESSION['full_name'] ?? 'Guest';
}

function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function formatDate($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (empty($datetime)) return '';
    return date('d/m/Y H:i', strtotime($datetime));
}

function isDeadlinePassed($deadline) {
    if (empty($deadline)) return false;
    return strtotime($deadline) < time();
}

function getDeadlineClass($deadline, $status) {
    if ($status === 'completed') return 'text-success';
    if (empty($deadline)) return '';
    
    $diff = strtotime($deadline) - time();
    $hours = $diff / 3600;
    
    if ($hours < 0) return 'text-danger';
    if ($hours < 24) return 'text-warning';
    return '';
}

function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="badge bg-secondary">Thấp</span>',
        'medium' => '<span class="badge bg-warning">Trung bình</span>',
        'high' => '<span class="badge bg-danger">Cao</span>'
    ];
    return $badges[$priority] ?? '';
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-secondary">Chưa làm</span>',
        'in_progress' => '<span class="badge bg-primary">Đang làm</span>',
        'completed' => '<span class="badge bg-success">Hoàn thành</span>'
    ];
    return $badges[$status] ?? '';
}

function getGoalStatusBadge($status) {
    $badges = [
        'not_started' => '<span class="badge bg-secondary">Chưa bắt đầu</span>',
        'in_progress' => '<span class="badge bg-primary">Đang thực hiện</span>',
        'completed' => '<span class="badge bg-success">Hoàn thành</span>',
        'cancelled' => '<span class="badge bg-danger">Đã hủy</span>'
    ];
    return $badges[$status] ?? '';
}

function calculateProgress($conn, $user_id) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
              FROM tasks 
              WHERE user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['total'] == 0) return 0;
    
    return round(($result['completed'] / $result['total']) * 100);
}

function getTodayTasks($conn, $user_id) {
    $today = date('Y-m-d');
    $query = "SELECT t.*, s.subject_name, s.color 
              FROM tasks t
              LEFT JOIN subjects s ON t.subject_id = s.id
              WHERE t.user_id = ? 
              AND DATE(t.deadline) = ?
              ORDER BY t.deadline ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    return $stmt->get_result();
}

function getUpcomingTasks($conn, $user_id, $limit = 5) {
    $today = date('Y-m-d');
    $query = "SELECT t.*, s.subject_name, s.color 
              FROM tasks t
              LEFT JOIN subjects s ON t.subject_id = s.id
              WHERE t.user_id = ? 
              AND t.status != 'completed'
              AND t.deadline >= ?
              ORDER BY t.deadline ASC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $user_id, $today, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function getOverdueTasks($conn, $user_id) {
    $now = date('Y-m-d H:i:s');
    $query = "SELECT t.*, s.subject_name, s.color 
              FROM tasks t
              LEFT JOIN subjects s ON t.subject_id = s.id
              WHERE t.user_id = ? 
              AND t.status != 'completed'
              AND t.deadline < ?
              ORDER BY t.deadline ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $now);
    $stmt->execute();
    return $stmt->get_result();
}

function getTaskStats($conn, $user_id) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
              FROM tasks 
              WHERE user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getDayOfWeekVietnamese($day) {
    $days = [
        'Monday' => 'Thứ Hai',
        'Tuesday' => 'Thứ Ba',
        'Wednesday' => 'Thứ Tư',
        'Thursday' => 'Thứ Năm',
        'Friday' => 'Thứ Sáu',
        'Saturday' => 'Thứ Bảy',
        'Sunday' => 'Chủ Nhật'
    ];
    return $days[$day] ?? $day;
}

function getTasksForWeek($conn, $user_id) {
    $start_of_week = date('Y-m-d', strtotime('monday this week'));
    $end_of_week = date('Y-m-d', strtotime('sunday this week'));
    
    $query = "SELECT t.*, s.subject_name, s.color 
              FROM tasks t
              LEFT JOIN subjects s ON t.subject_id = s.id
              WHERE t.user_id = ? 
              AND DATE(t.deadline) BETWEEN ? AND ?
              ORDER BY t.deadline ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $user_id, $start_of_week, $end_of_week);
    $stmt->execute();
    return $stmt->get_result();
}
?>
