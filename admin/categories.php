<?php
require_once '../view/config.php';
require_once '../includes/Util.php';
require_once '../includes/Database.php';

// Yêu cầu xác thực admin
Util::requireAuth();
Util::requireRole('admin');

$db = Database::getInstance();
$message = '';

// Xử lý các thao tác danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $categoryId = filter_var($_POST['category_id'] ?? 0, FILTER_VALIDATE_INT);
    $name = Util::sanitizeInput($_POST['name'] ?? '');

    // Ghi log để gỡ lỗi
    error_log("POST data: action=$action, category_id=$categoryId, name=$name");

    if ($action === 'create') {
        if (empty($name)) {
            $message = 'Vui lòng nhập tên danh mục';
        } else {
            try {
                // Kiểm tra xem danh mục đã tồn tại chưa
                $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                
                if ($stmt->fetch()) {
                    $message = 'Danh mục đã tồn tại';
                } else {
                    $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
                    $result = $stmt->execute([$name]);
                    
                    if ($result) {
                        Util::flashMessage('success', 'Thêm danh mục thành công');
                        Util::redirect('../admin/categories.php');
                    } else {
                        $message = 'Không thể thêm danh mục';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Lỗi: ' . $e->getMessage();
                error_log("Create error: " . $e->getMessage());
            }
        }
    } elseif ($action === 'update' && $categoryId) {
        if (!$categoryId || empty($name)) {
            $message = 'Dữ liệu không hợp lệ';
        } else {
            try {
                // Kiểm tra xem danh mục có tồn tại không
                $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                if (!$stmt->fetch()) {
                    $message = 'Danh mục không tồn tại';
                } else {
                    // Kiểm tra xem tên danh mục đã tồn tại cho danh mục khác chưa
                    $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                    $stmt->execute([$name, $categoryId]);
                    
                    if ($stmt->fetch()) {
                        $message = 'Tên danh mục đã tồn tại';
                    } else {
                        $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
                        $result = $stmt->execute([$name, $categoryId]);
                        
                        if ($result) {
                            Util::flashMessage('success', 'Cập nhật danh mục thành công');
                            Util::redirect('../admin/categories.php');
                        } else {
                            $message = 'Không thể cập nhật danh mục';
                        }
                    }
                }
            } catch (PDOException $e) {
                $message = 'Lỗi: ' . $e->getMessage();
                error_log("Update error: " . $e->getMessage());
            }
        }
    } elseif ($action === 'delete' && $categoryId) {
        try {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$categoryId]);
            
            if ($result) {
                Util::flashMessage('success', 'Xóa danh mục thành công');
                Util::redirect('../admin/categories.php');
            } else {
                $message = 'Không thể xóa danh mục';
            }
        } catch (PDOException $e) {
            $message = 'Lỗi: ' . $e->getMessage();
            error_log("Delete error: " . $e->getMessage());
        }
    } else {
        $message = 'Hành động không hợp lệ';
        error_log("Invalid action: $action, category_id=$categoryId");
    }
}

// Lấy tất cả danh mục với thống kê
$categories = $db->query("
    SELECT c.*, 
        COUNT(DISTINCT uc.user_id) as total_users,
        COUNT(DISTINCT b.id) as total_bookings,
        COALESCE(SUM(p.amount), 0) as total_revenue
    FROM categories c
    LEFT JOIN user_categories uc ON c.id = uc.category_id
    LEFT JOIN users u ON uc.user_id = u.id AND u.role = 'kol_koc'
    LEFT JOIN bookings b ON u.id = b.kol_koc_id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'paid'
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();

require_once '../includes/header.php';
?>

<!-- Include admin CSS -->
<link rel="stylesheet" href="../assets/css/admin1.css">

<div class="categories-container">
    <div class="header-flex">
        <h1>Quản lý danh mục</h1>
        <div class="btn-flex">
            <button onclick="showCategoryForm()" class="btn">Thêm danh mục</button>
            <a href="dashboard.php" class="btn btn-outline">
                ← Quay lại
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="flash-message error">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên danh mục</th>
                    <th>KOL/KOC</th>
                    <th>Đơn đặt</th>
                    <th>Doanh thu</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($category['name']) ?>
                        </td>
                        <td class="center">
                            <?= number_format($category['total_users']) ?>
                        </td>
                        <td class="center">
                            <?= number_format($category['total_bookings']) ?>
                        </td>
                        <td class="center">
                            <?= number_format($category['total_revenue']) ?> VNĐ
                        </td>
                        <td class="center">
                            <div class="btn-flex" style="justify-content: center;">
                                <button onclick='editCategory(<?= htmlspecialchars(json_encode($category, JSON_UNESCAPED_UNICODE)) ?>)' 
                                    class="btn btn-outline">
                                    Sửa
                                </button>
                                <form method="POST" action="" style="display: inline;"
                                    onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                    <button type="submit" class="btn btn-error">
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" class="table-empty">
                            Chưa có danh mục nào
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Form Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <h2 id="categoryFormTitle" class="modal-title">Thêm danh mục mới</h2>
        
        <form method="POST" action="" id="categoryForm" onsubmit="return validateForm()">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="category_id" id="formCategoryId" value="">

            <div class="form-group">
                <label for="name">Tên danh mục</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="hideCategoryModal()" class="btn btn-outline">
                    Hủy
                </button>
                <button type="submit" class="btn">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCategoryForm() {
    document.getElementById('categoryFormTitle').textContent = 'Thêm danh mục mới';
    document.getElementById('categoryForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('formCategoryId').value = '';
    document.getElementById('categoryModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function editCategory(category) {
    if (!category || !category.id || !category.name) {
        alert('Dữ liệu danh mục không hợp lệ');
        console.error('Invalid category data:', category);
        return;
    }
    console.log('Editing category:', category);
    document.getElementById('categoryFormTitle').textContent = 'Chỉnh sửa danh mục';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formCategoryId').value = category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('categoryModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function hideCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function validateForm() {
    const action = document.getElementById('formAction').value;
    const categoryId = document.getElementById('formCategoryId').value;
    const name = document.getElementById('name').value.trim();
    
    if (!name) {
        alert('Vui lòng nhập tên danh mục');
        return false;
    }
    
    if (action === 'update' && !categoryId) {
        alert('ID danh mục không hợp lệ');
        console.error('Missing category ID for update');
        return false;
    }
    
    console.log('Form submission:', { action, categoryId, name });
    return true;
}

// Đóng modal khi nhấp ra ngoài
document.getElementById('categoryModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('categoryModal')) {
        hideCategoryModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>