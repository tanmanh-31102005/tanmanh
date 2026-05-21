# Nền Tảng Đặt Lịch KOL/KOC (KOL/KOC Booking Platform)

Một nền tảng web chuyên nghiệp được xây dựng bằng ngôn ngữ PHP (theo mô hình tách biệt Model - View - Logic) và cơ sở dữ liệu MySQL, giúp kết nối các Thương hiệu (Brands) với các KOL/KOC để hợp tác quảng bá sản phẩm, dịch vụ. Nền tảng hỗ trợ toàn bộ quy trình từ tìm kiếm, đặt lịch, nhắn tin trao đổi cho đến thanh toán và đánh giá hiệu quả công việc.

---

## 🚀 Các Tính Năng Chính

Dự án phân chia phân quyền rõ ràng thành 3 vai trò chính cùng với các chức năng dành cho khách truy cập:

### 1. Dành cho Khách (Chưa đăng nhập)
* **Khám phá & Tìm kiếm:** Xem danh sách KOL/KOC nổi bật trên trang chủ, tìm kiếm KOL/KOC theo danh mục lĩnh vực (Food, Travel, Fashion, Làm Đẹp) hoặc lọc theo số lượng người theo dõi (followers).
* **Xem hồ sơ công khai:** Xem thông tin chi tiết của KOL/KOC bao gồm giới thiệu, các dịch vụ cung cấp, bảng giá, portfolio dự án đã thực hiện và các đánh giá (reviews) từ thương hiệu khác.
* **Đăng ký tài khoản:** Đăng ký tài khoản với vai trò tương ứng (**Brand** hoặc **KOL/KOC**). Tài khoản mới đăng ký sẽ ở trạng thái chờ duyệt (Pending) từ Admin để đảm bảo chất lượng hệ thống.

### 2. Dành cho Thương hiệu (Brands)
* **Tìm kiếm nâng cao:** Tìm kiếm chi tiết và bộ lọc KOL/KOC phù hợp với chiến dịch marketing của doanh nghiệp.
* **Gửi yêu cầu đặt lịch (Booking):** Đặt dịch vụ cụ thể của KOL/KOC, chỉ định deadline hoàn thành và số lượng bài viết (posts) yêu cầu.
* **Nhắn tin trực tiếp (Messages):** Chat trực tiếp với KOL/KOC để thảo luận chi tiết về kịch bản, yêu cầu công việc.
* **Quản lý đơn đặt (Bookings):** Theo dõi trạng thái đơn đặt lịch (Chờ duyệt, Đã nhận, Bị từ chối, Hoàn thành, Đã hủy).
* **Thanh toán (Payments):** Quản lý trạng thái thanh toán và thực hiện thanh toán cho đơn đặt dịch vụ (ví dụ: Chuyển khoản ngân hàng - Bank Transfer).
* **Đánh giá (Reviews):** Viết đánh giá kèm số sao (rating) cho KOL/KOC sau khi chiến dịch hoàn thành xuất sắc.

### 3. Dành cho KOL/KOC
* **Quản lý dịch vụ (Services):** Tạo mới, chỉnh sửa hoặc xóa các gói dịch vụ quảng cáo của bản thân kèm theo mức giá cụ thể (ví dụ: Gói Review Quán ăn, Gói Nhảy TikTok, Gói Review Thời trang...).
* **Quản lý đơn đặt lịch:** Tiếp nhận yêu cầu đặt dịch vụ từ các Thương hiệu, lựa chọn Đồng ý (Accept) hoặc Từ chối (Decline) dựa trên lịch trình và nội dung yêu cầu.
* **Hồ sơ cá nhân (Profile):** Cập nhật thông tin chi tiết: Tên hiển thị, Ảnh đại diện (avatar), Lĩnh vực hoạt động (Industry), Số lượng người theo dõi (Followers), Link mạng xã hội, và tiểu sử (Bio).
* **Quản lý Portfolio:** Hiển thị các sản phẩm truyền thông, video hoặc hình ảnh các dự án nổi bật đã thực hiện để tạo uy tín với nhà quảng cáo.

### 4. Dành cho Quản trị viên (Admin)
* **Trang tổng quan (Dashboard):** Xem các số liệu thống kê tổng quát của hệ thống dưới dạng biểu đồ trực quan (Doanh thu, tổng số user, số lượng booking thành công, v.v.).
* **Quản lý người dùng (Users):** Duyệt yêu cầu đăng ký tài khoản (chuyển trạng thái từ `pending` sang `active`), khóa/mở khóa tài khoản hoặc xóa người dùng khỏi hệ thống.
* **Quản lý danh mục (Categories):** Quản lý các lĩnh vực hoạt động chính của KOL/KOC (Thêm, sửa, xóa danh mục như Food, Travel, Fashion...).
* **Quản lý giao dịch thanh toán:** Theo dõi lịch sử giao dịch, cập nhật trạng thái thanh toán của các Brand.
* **Cài đặt hệ thống (Settings):** Cấu hình các thông tin chung của website như: Địa chỉ liên hệ, email hỗ trợ, số điện thoại, ảnh nền trang chủ, nội dung bài viết giới thiệu (Blog Content) và ghim (pin) các KOL nổi bật lên trang chủ.
* **Xuất báo cáo PDF:** Hỗ trợ tính năng xuất thống kê báo cáo tài chính và hiệu suất hệ thống ra file PDF chuyên nghiệp.

---

## 🛠️ Công Nghệ Sử Dụng

* **Backend:** PHP thuần (PHP 8.x), kết nối cơ sở dữ liệu qua thư viện **PDO** (sử dụng mẫu thiết kế Singleton đảm bảo hiệu năng kết nối tốt nhất).
* **Frontend:** HTML5, CSS3 thuần (tối ưu hóa giao diện Dark Mode hiện đại, responsive đầy đủ trên thiết bị di động), JavaScript tương tác thời gian thực.
* **Cơ sở dữ liệu:** MySQL / MariaDB.
* **Thư viện bên thứ ba (Composer):**
  * `phpmailer/phpmailer`: Xử lý gửi email tự động (như lấy lại mật khẩu, thông báo tài khoản).
  * `tecnickcom/tcpdf`: Thư viện tạo và xuất dữ liệu báo cáo thống kê dưới dạng PDF chất lượng cao.

---

## 📁 Cấu Trúc Thư Mục Dự Án

```text
Report4/
├── admin/               # Chứa giao diện và logic của Quản trị viên (Dashboard, quản lý user, danh mục, cài đặt hệ thống)
├── assets/              # Lưu trữ tài nguyên tĩnh (Hình ảnh, CSS giao diện admin, JavaScript)
├── brand/               # Chứa các trang nghiệp vụ dành riêng cho Thương hiệu (Thanh toán,...)
├── includes/            # Chứa các file dùng chung và lõi hệ thống
│   ├── Database.php     # Kết nối Database (Singleton class sử dụng PDO)
│   ├── Util.php         # Các hàm tiện ích (Kiểm tra xác thực, định dạng tiền tệ, upload file, flash messages)
│   ├── header.php       # Phần đầu trang web (Meta SEO, thanh điều hướng linh hoạt theo vai trò)
│   └── footer.php       # Phần chân trang web
├── models/              # Chứa các lớp Model ánh xạ cơ sở dữ liệu (xử lý logic nghiệp vụ)
│   ├── Booking.php
│   ├── Payment.php
│   ├── Service.php
│   ├── Settings.php
│   └── User.php
├── uploads/             # Thư mục lưu trữ tệp tin tải lên từ người dùng (ảnh đại diện, ảnh portfolio, ảnh danh mục)
│   ├── avatars/
│   └── backgrounds/
├── vendor/              # Các thư viện PHP được cài đặt tự động bởi Composer
├── view/                # Chứa giao diện chính và logic các trang người dùng (Trang chủ, Đăng ký, Đăng nhập, Hồ sơ, Nhắn tin...)
│   └── config.php       # File cấu hình cơ sở dữ liệu và hằng số toàn hệ thống
├── composer.json        # Định nghĩa các thư viện phụ thuộc của dự án
├── kol_koc_booking2.sql # File xuất bản cơ sở dữ liệu MySQL
└── .gitignore           # File cấu hình các thư mục cần loại trừ khi đẩy lên Git
```

---

## ⚙️ Hướng Dẫn Cài Đặt & Chạy Dự Án

Để chạy dự án này trên môi trường local của bạn, hãy thực hiện theo các bước chi tiết sau:

### 1. Yêu cầu hệ thống
* Đã cài đặt **PHP >= 8.0**
* Đã cài đặt **MySQL / MariaDB**
* Đã cài đặt **Composer** (Quản lý thư viện PHP)
* Khuyên dùng bộ công cụ **XAMPP**, **Laragon** hoặc **WampServer** để nhanh chóng khởi tạo Apache và MySQL Server.

### 2. Tải mã nguồn về máy
Sao chép hoặc tải file zip mã nguồn về và giải nén thư mục dự án vào thư mục gốc của Web Server của bạn (ví dụ: `C:/xampp/htdocs/` nếu dùng XAMPP).

### 3. Cấu hình cơ sở dữ liệu
1. Mở công cụ quản trị cơ sở dữ liệu của bạn (ví dụ: **phpMyAdmin** tại địa chỉ `http://localhost/phpmyadmin`).
2. Tạo một cơ sở dữ liệu mới có tên là: `kol_koc_booking2` với mã hóa collation là `utf8mb4_general_ci`.
3. Chọn cơ sở dữ liệu vừa tạo, chuyển sang tab **Import** (Nhập), chọn tệp tin cơ sở dữ liệu `kol_koc_booking2.sql` có sẵn trong thư mục gốc dự án và nhấn **Import / Go** để nhập toàn bộ cấu trúc bảng và dữ liệu mẫu.

### 4. Cài đặt các thư viện phụ thuộc
Mở Terminal / Command Prompt tại thư mục gốc của dự án (`Report4`) và chạy lệnh sau để tải và cài đặt các thư viện PHPMailer và TCPDF:
```bash
composer install
```

### 5. Cấu hình hệ thống trong mã nguồn
Mở file [config.php](file:///d:/HTML/Report4/Report4/view/config.php) tại đường dẫn `view/config.php` và cấu hình các thông số phù hợp với máy chủ của bạn:

```php
// Cấu hình cơ sở dữ liệu
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');       // Username MySQL của bạn
define('DB_PASS', '');           // Password MySQL của bạn
define('DB_NAME', 'kol_koc_booking2');

// Đường dẫn trang web (Ví dụ chạy trên localhost qua cổng mặc định)
define('SITE_URL', 'http://localhost/Report4');
```

*Lưu ý: Tùy thuộc vào tên thư mục bạn đặt trong thư mục `htdocs`, hãy sửa lại `SITE_URL` tương ứng (ví dụ: `http://localhost/Report4` hoặc `http://localhost/kol-koc-booking`).*

### 6. Tài khoản chạy thử nghiệm
Cơ sở dữ liệu mẫu đi kèm đã được cấu hình sẵn một số tài khoản demo để bạn dễ dàng đăng nhập và thử nghiệm các tính năng:

| Vai trò (Role) | Email đăng nhập | Mật khẩu mẫu | Trạng thái |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@example2.com` | `123456` | Đã kích hoạt (Active) |
| **Quản trị viên (Admin)** | `phantaib52@gmail.com` | `123456` | Đã kích hoạt (Active) |
| **Thương hiệu (Brand)** | `taibrand@gmail.com` | `123456` | Đã kích hoạt (Active) |
| **KOL / KOC** | `quankhonggo@gmail.com` | `123456` | Đã kích hoạt (Active) |

---

## 📌 Khởi động và Sử dụng
1. Đảm bảo dịch vụ **Apache** và **MySQL** trong XAMPP / Laragon đã được bật.
2. Mở trình duyệt web và truy cập đường dẫn trang chủ:
   ```text
   http://localhost/Report4/view/
   ```
3. Đăng nhập bằng tài khoản Admin để truy cập khu vực `/admin/dashboard.php` duyệt các tài khoản đăng ký mới, hoặc sử dụng các tài khoản Brand và KOL/KOC để tiến hành luồng nghiệp vụ tìm kiếm, đặt lịch, nhắn tin và thanh toán.
