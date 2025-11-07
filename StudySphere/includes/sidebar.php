<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
                   href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'subjects.php' ? 'active' : '' ?>" 
                   href="subjects.php">
                    <i class="fas fa-book"></i>
                    <span>Môn học</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : '' ?>" 
                   href="tasks.php">
                    <i class="fas fa-tasks"></i>
                    <span>Công việc</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : '' ?>" 
                   href="schedule.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Lịch học</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'goals.php' ? 'active' : '' ?>" 
                   href="goals.php">
                    <i class="fas fa-trophy"></i>
                    <span>Mục tiêu</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'stats.php' ? 'active' : '' ?>" 
                   href="stats.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Thống kê</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-mascot mt-4 text-center">
            <div class="mascot-container">
                <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/People/Student.png" 
                     alt="Mascot" width="80" height="80" class="mascot-img">
                <p class="small text-muted mt-2 mascot-text">Chào bạn!</p>
            </div>
        </div>
    </div>
</nav>
