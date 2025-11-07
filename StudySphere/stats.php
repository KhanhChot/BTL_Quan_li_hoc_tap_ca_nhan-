<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getUserId();
$flash = getFlashMessage();

$task_stats = getTaskStats($conn, $user_id);

$query = "SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
          FROM tasks 
          WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          GROUP BY DATE(created_at)
          ORDER BY date";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$chart_data = $stmt->get_result();

$subjects_query = "SELECT s.subject_name, s.color, COUNT(t.id) as task_count
                   FROM subjects s
                   LEFT JOIN tasks t ON s.id = t.subject_id
                   WHERE s.user_id = ?
                   GROUP BY s.id, s.subject_name, s.color
                   ORDER BY task_count DESC
                   LIMIT 5";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subject_stats = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê - Quản Lý Học Tập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-chart-line"></i> Thống Kê Chi Tiết
                    </h1>
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="fas fa-print"></i> In báo cáo
                    </button>
                </div>
                
                <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Tổng công việc</h6>
                                        <h2 class="mb-0 fw-bold"><?= $task_stats['total'] ?></h2>
                                    </div>
                                    <i class="fas fa-tasks stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Hoàn thành</h6>
                                        <h2 class="mb-0 fw-bold"><?= $task_stats['completed'] ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card text-white" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Đang làm</h6>
                                        <h2 class="mb-0 fw-bold"><?= $task_stats['in_progress'] ?></h2>
                                    </div>
                                    <i class="fas fa-spinner stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card text-white" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Tỉ lệ hoàn thành</h6>
                                        <h2 class="mb-0 fw-bold"><?= $task_stats['total'] > 0 ? round(($task_stats['completed'] / $task_stats['total']) * 100) : 0 ?>%</h2>
                                    </div>
                                    <i class="fas fa-chart-pie stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Biểu đồ tiến độ 30 ngày</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="progressChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Môn học phổ biến</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($subject_stats->num_rows > 0): ?>
                                    <div class="chart-container">
                                        <canvas id="subjectChart"></canvas>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Objects/Open%20Book.png" 
                                             alt="Empty" width="100" height="100">
                                        <p class="text-muted mt-3">Chưa có dữ liệu môn học</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Smilies/Star-Struck.png" 
                         width="60" height="60" alt="Star">
                    <p class="text-muted mt-2">Tiếp tục phát huy! Bạn đang làm rất tốt!</p>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        const chartData = <?= json_encode(array_values($chart_data->fetch_all(MYSQLI_ASSOC))) ?>;
        const subjectData = <?= json_encode(array_values($subject_stats->fetch_all(MYSQLI_ASSOC))) ?>;
        
        if (chartData.length > 0) {
            const ctx = document.getElementById('progressChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(d => new Date(d.date).toLocaleDateString('vi-VN')),
                    datasets: [{
                        label: 'Tổng công việc',
                        data: chartData.map(d => d.total),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Hoàn thành',
                        data: chartData.map(d => d.completed),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }
        
        if (subjectData.length > 0) {
            const ctx2 = document.getElementById('subjectChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: subjectData.map(d => d.subject_name),
                    datasets: [{
                        data: subjectData.map(d => d.task_count),
                        backgroundColor: subjectData.map(d => d.color)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
