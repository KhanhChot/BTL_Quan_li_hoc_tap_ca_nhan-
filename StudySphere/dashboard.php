<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getUserId();
$stats = getTaskStats($conn, $user_id);
$today_tasks = getTodayTasks($conn, $user_id);
$upcoming_tasks = getUpcomingTasks($conn, $user_id);
$progress = calculateProgress($conn, $user_id);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quản Lý Học Tập</title>
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
                    <h1 class="h2"><i class="fas fa-home"></i> Dashboard</h1>
                    <div class="text-muted">
                        <i class="fas fa-calendar"></i> <?= date('d/m/Y') ?>
                    </div>
                </div>
                
                <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Tổng công việc</h6>
                                        <h2 class="mb-0"><?= $stats['total'] ?></h2>
                                    </div>
                                    <i class="fas fa-tasks fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Hoàn thành</h6>
                                        <h2 class="mb-0"><?= $stats['completed'] ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Đang làm</h6>
                                        <h2 class="mb-0"><?= $stats['in_progress'] ?></h2>
                                    </div>
                                    <i class="fas fa-spinner fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-secondary mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Chưa làm</h6>
                                        <h2 class="mb-0"><?= $stats['pending'] ?></h2>
                                    </div>
                                    <i class="fas fa-clock fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-chart-line"></i> Tiến độ tổng quan</h5>
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?= $progress ?>%;" 
                                         aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?= $progress ?>%
                                    </div>
                                </div>
                                <p class="text-muted mt-2 mb-0">
                                    Bạn đã hoàn thành <?= $stats['completed'] ?> / <?= $stats['total'] ?> công việc
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-calendar-day"></i> Công việc hôm nay
                            </div>
                            <div class="card-body">
                                <?php if ($today_tasks->num_rows > 0): ?>
                                    <div class="list-group">
                                        <?php while ($task = $today_tasks->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?= htmlspecialchars($task['task_name']) ?>
                                                        <?php if ($task['subject_name']): ?>
                                                            <span class="badge" style="background-color: <?= $task['color'] ?>">
                                                                <?= htmlspecialchars($task['subject_name']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="<?= getDeadlineClass($task['deadline'], $task['status']) ?>">
                                                        <i class="fas fa-clock"></i> <?= formatDateTime($task['deadline']) ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <?= getStatusBadge($task['status']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center my-3">
                                        <i class="fas fa-smile"></i> Không có công việc nào hôm nay
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-list-ul"></i> Công việc sắp tới
                            </div>
                            <div class="card-body">
                                <?php if ($upcoming_tasks->num_rows > 0): ?>
                                    <div class="list-group">
                                        <?php while ($task = $upcoming_tasks->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?= htmlspecialchars($task['task_name']) ?>
                                                        <?php if ($task['subject_name']): ?>
                                                            <span class="badge" style="background-color: <?= $task['color'] ?>">
                                                                <?= htmlspecialchars($task['subject_name']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="<?= getDeadlineClass($task['deadline'], $task['status']) ?>">
                                                        <i class="fas fa-clock"></i> <?= formatDateTime($task['deadline']) ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <?= getPriorityBadge($task['priority']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center my-3">
                                        <i class="fas fa-check-circle"></i> Không có công việc sắp tới
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
