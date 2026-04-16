# HCMUE Library — Hệ thống Quản lý Thư viện

Ứng dụng quản lý thư viện xây dựng trên **Laminas MVC Skeleton** theo kiến trúc MVC chuẩn. Repo hiện được chuẩn hóa theo module `Library` để tránh lệch schema và lỗi bootstrap.

## Yêu cầu hệ thống

| Phần mềm | Phiên bản |
|---|---|
| PHP | ≥ 8.1 (khuyến nghị 8.2) |
| PHP extension | `intl` (khuyến nghị bật trong cả CLI và FPM/Apache) |
| MySQL / MariaDB | ≥ 8.0 |
| Composer | ≥ 2.x |

## Khởi động nhanh

### 1. Cài đặt dependencies
```bash
composer install
```

### 2. Tạo Database
```bash
# Sử dụng MySQL CLI
mysql -u root -p < database.sql

# Hoặc import file database.sql qua phpMyAdmin
```

### 3. Cấu hình Database
Chỉnh sửa `config/autoload/local.php`:
```php
return [
    'db' => [
        'hostname' => 'localhost',
        'database' => 'library_db',
        'username' => 'root',
        'password' => 'your_password',
    ],
];
```

### 4. Chạy ứng dụng

**Cách 1 — PHP Built-in Server (phát triển):**
```bash
# Windows XAMPP
C:\xampp\php\php.exe -S localhost:8000 -t public

# Linux / Mac
php -S localhost:8000 -t public
```

**Cách 2 — XAMPP Apache (Virtual Host):**
```apache
<VirtualHost *:80>
    ServerName library.local
    DocumentRoot "D:/PHP/public"
    <Directory "D:/PHP/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Truy cập: `http://localhost:8000/admin/auth`
Hoặc chỉ cần mở `http://localhost:8000/`, hệ thống sẽ tự chuyển hướng vào màn hình phù hợp.

**Tài khoản mặc định:**
- Admin: `admin` / `Admin@123`
- Sinh viên: `student1` / `Admin@123`

---

## Cấu trúc Module Library

```
module/Library/
├── config/
│   └── module.config.php      ← Routes + DI factory bindings
├── src/
│   ├── Module.php             ← Entry point
│   ├── Controller/
│   │   ├── AuthController.php        ← Login/Logout
│   │   ├── BookController.php        ← CRUD sách
│   │   ├── DashboardController.php   ← Tổng quan
│   │   └── TransactionController.php ← Mượn/Trả
│   ├── Model/
│   │   ├── Entity/
│   │   │   ├── Book.php
│   │   │   ├── User.php
│   │   │   └── BorrowRecord.php
│   │   └── Table/
│   │       ├── BookTable.php         ← Inventory management
│   │       ├── UserTable.php
│   │       └── BorrowTable.php       ← JOIN queries, overdue detection
│   ├── Form/
│   │   ├── BookForm.php              ← ISBN validation
│   │   ├── LoginForm.php
│   │   └── BorrowForm.php            ← Date validation
│   └── Factory/
│       ├── Controller/               ← Controller factories
│       ├── Form/                     ← Form factories (FormElementManager)
│       └── Table/                    ← TableGateway factories
└── view/
    └── library/
        ├── auth/login.phtml
        ├── dashboard/index.phtml
        ├── book/{index,add}.phtml
        └── transaction/{index,borrow}.phtml
```

## Cách hệ thống xử lý Dependency Injection

Laminas sử dụng **ServiceManager** để quản lý toàn bộ dependencies:

```
HTTP Request
    │
    ▼
public/index.php       ← Điểm vào duy nhất
    │
    ▼
ServiceManager         ← Container chứa tất cả services
    │ inject
    ▼
Controller::__construct(BookTable $table)   ← DI qua Constructor
    │ factory creates
    ▼
BookTableFactory       ← Lấy Adapter từ ServiceManager
    │ inject
    ▼
BookTable(TableGateway)← Nhận Adapter, không biết gì về config
    │ uses
    ▼
Laminas\Db\Adapter     ← Đọc từ config/autoload/global.php + local.php
```

**Tại sao cần Factory?**

```php
// Cách cũ (khó test, tạo coupling):
class BookController {
    public function __construct() {
        $pdo = new PDO('mysql:...');           // ❌ hardcoded
        $this->table = new BookTable($pdo);    // ❌ manual creation
    }
}

// Laminas DI (có thể mock, testable):
class BookController {
    public function __construct(BookTable $table) { // ✅ injected
        $this->table = $table;
    }
}

class BookControllerFactory {
    public function __invoke(ContainerInterface $c): BookController {
        return new BookController($c->get(BookTable::class)); // ✅ ServiceManager resolves
    }
}
```

Ngoài Controller/Table/Service, project hiện cũng đăng ký Form qua `form_elements` để controller không còn `new Form(...)` trực tiếp.

## API Routes

| URL | Method | Controller | Mô tả |
|---|---|---|---|
| `/` | GET | HomeController | Điều hướng tới đăng nhập hoặc dashboard |
| `/admin` | GET | HomeController | Điều hướng nội bộ của khu quản trị |
| `/admin/auth/login` | GET/POST | AuthController | Đăng nhập |
| `/admin/auth/logout` | GET | AuthController | Đăng xuất |
| `/admin/dashboard` | GET | DashboardController | Tổng quan |
| `/admin/books` | GET | BookController | Danh sách sách |
| `/admin/books/add` | GET/POST | BookController | Thêm sách |
| `/admin/books/edit/:id` | GET/POST | BookController | Sửa sách |
| `/admin/books/delete/:id` | POST | BookController | Xóa sách |
| `/admin/borrow` | GET | TransactionController | Danh sách phiếu |
| `/admin/borrow/borrow` | GET/POST | TransactionController | Lập phiếu mượn |
| `/admin/borrow/return/:id` | POST | TransactionController | Xác nhận trả |

## Ghi chú vận hành

- `Application` đã được tắt khỏi `config/modules.config.php`; phần đang hoạt động và được duy trì là `Library`.
- Cache config mặc định đã tắt để tránh tình trạng sửa module/config xong nhưng ứng dụng vẫn đọc cấu hình cũ trong `data/cache`.
