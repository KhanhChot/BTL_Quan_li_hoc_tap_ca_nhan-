<img width="1340" height="621" alt="image" src="https://github.com/user-attachments/assets/ba63cb77-7782-48bf-a559-690ce75bef03" />
📖 1. Giới thiệu
Hệ thống Quản lý Mục tiêu Học tập Cá nhân được xây dựng nhằm giúp sinh viên lập kế hoạch, theo dõi tiến độ và đánh giá kết quả học tập của bản thân trong suốt quá trình học đại học.
Ứng dụng giúp người dùng đặt ra mục tiêu ngắn hạn và dài hạn, quản lý các môn học, theo dõi tiến trình hoàn thành, nhận thông báo nhắc nhở và thống kê kết quả học tập một cách trực quan, dễ hiểu.
Thay vì ghi chú thủ công hoặc quản lý rời rạc trên giấy tờ, hệ thống mang đến một giải pháp quản lý thông minh, hiện đại và dễ sử dụng, hỗ trợ sinh viên nâng cao năng suất học tập và tự phát triển bản thân.
🔧 2. Các công nghệ được sử dụng
<img width="1347" height="626" alt="image" src="https://github.com/user-attachments/assets/a4ef9cd0-b043-49fd-8daf-bd46d272adee" />
🚀 3. Hình ảnh các chức năng
### Trang dashboard
<img width="2493" height="1313" alt="image" src="https://github.com/user-attachments/assets/d9ba2c9c-df50-4ec8-b529-06bca41b1be1" />
### Trang quản lí môn học
<img width="2491" height="1307" alt="image" src="https://github.com/user-attachments/assets/a1c55ccb-6c7a-4d2a-ab69-55a59b9e139b" />
### Trang quản lí công việc
<img width="2494" height="1306" alt="image" src="https://github.com/user-attachments/assets/da324301-bcfb-429a-8160-b4508617352d" />
### Trang lịch học / thời khóa biểu
<img width="2488" height="1307" alt="image" src="https://github.com/user-attachments/assets/364874eb-1886-42d9-a46e-4d385f0c0d78" />
### Trang mục tiêu học tập
<img width="2492" height="1302" alt="image" src="https://github.com/user-attachments/assets/8fbd032c-3935-430c-87a4-4aad5868a936" />
### Trang thống kê 
<img width="2490" height="1310" alt="image" src="https://github.com/user-attachments/assets/440554ca-12d8-4b07-9461-3176c6ccb7c3" />
## ⚙️ 4. Cài đặt
4.1. Cài đặt công cụ, môi trường và các thư viện cần thiết
Tải và cài đặt XAMPP
🔗 https://www.apachefriends.org/download.html
(Khuyến nghị dùng bản PHP 8.x)
Cài đặt Visual Studio Code và các extension:
PHP Intelephense
MySQL
Prettier - Code Formatter
4.2. Tải project
Clone project về thư mục htdocs trong XAMPP (ví dụ ổ C):
cd C:\xampp\htdocs
git clone https://github.com/yourusername/QuanLyMucTieuHocTap.git
Truy cập qua trình duyệt:
👉 http://localhost/authentication_login.php
4.3. Setup database
Mở XAMPP Control Panel, Start Apache và MySQL
Sau đó tạo database trong MySQL Workbench:
CREATE DATABASE IF NOT EXISTS quan_ly_muc_tieu
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
4.4. Setup tham số kết nối
Mở file config.php trong project và cập nhật:
<?php
function getDbConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "quan_ly_muc_tieu";
    $port = 3306;

    $conn = mysqli_connect($servername, $username, $password, $dbname, $port);
    if (!$conn) {
        die("Kết nối database thất bại: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8");
    return $conn;
}
?>
4.5. Chạy hệ thống
Mở XAMPP Control Panel → Start Apache và MySQL
Truy cập hệ thống qua:
👉 http://localhost/index.php
4.6. Đăng nhập lần đầu

Tài khoản mặc định:
Tên đăng nhập: admin  
Mật khẩu: 123456

Sau khi đăng nhập, quản trị viên có thể:
Thêm/sửa/xoá mục tiêu học tập
Quản lý người dùng
Theo dõi tiến độ và thống kê
