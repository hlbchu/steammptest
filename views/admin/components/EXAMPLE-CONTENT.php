<?php
/**
 * EXAMPLE: Sample content file for a new admin page
 * Location: views/admin/components/example-content.php
 * 
 * This file is included by layout.php
 * You have access to $page_title and all global helpers
 */
?>

<div class="example-section">
    <!-- Example 1: Using Tabs Component -->
    <?php
    $tabs = [
        ['id' => 'data', 'label' => 'Dữ Liệu', 'active' => true],
        ['id' => 'settings', 'label' => 'Cài Đặt']
    ];
    include 'tabs.php';
    ?>

    <div id="data-tab" class="tab-content active">
        <!-- Example 2: Using Table Component -->
        <?php
        $table = [
            'id' => 'example-table',
            'columns' => ['ID', 'Tên', 'Email', 'Hành động'],
            'rows' => [
                ['1', 'Người dùng 1', 'user1@example.com', '<button class="btn btn-edit">Sửa</button>'],
                ['2', 'Người dùng 2', 'user2@example.com', '<button class="btn btn-edit">Sửa</button>']
            ]
        ];
        include 'table.php';
        ?>
    </div>

    <div id="settings-tab" class="tab-content">
        <!-- Example 3: Using Form Groups -->
        <form id="settings-form">
            <div class="form-group">
                <label for="setting1">Cài Đặt 1:</label>
                <input type="text" id="setting1" placeholder="Nhập giá trị...">
            </div>

            <div class="form-group">
                <label for="setting2">Cài Đặt 2:</label>
                <select id="setting2">
                    <option value="">-- Chọn --</option>
                    <option value="1">Tùy chọn 1</option>
                    <option value="2">Tùy chọn 2</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Lưu</button>
        </form>
    </div>
</div>

<!-- Example 4: Using Modal Component -->
<?php
$modal = [
    'id' => 'example-modal',
    'title' => 'Ví dụ Modal',
    'body' => '
    <form id="example-form">
        <div class="form-group">
            <label>Nhập dữ liệu:</label>
            <input type="text" id="modal-input" required>
        </div>
    </form>
    ',
    'buttons' => [
        ['label' => 'Lưu', 'class' => 'btn-primary', 'onclick' => 'saveExample()'],
        ['label' => 'Hủy', 'class' => 'btn-secondary', 'onclick' => 'closeModal("example-modal")']
    ]
];
include 'modal.php';
?>

<!-- Action button to open modal -->
<button class="btn btn-primary" onclick="openModal('example-modal')" style="margin-top: 20px;">
    Mở Modal
</button>

<!-- Example 5: Using Alert Component -->
<?php
$alert = ['type' => 'info', 'message' => 'Đây là một thông báo ví dụ'];
include 'alert.php';
?>

<!-- Page-specific JavaScript -->
<script>
    // You have access to global helpers:
    // - apiCall(url, options)
    // - setupTabs()
    // - openModal(modalId)
    // - closeModal(modalId)
    // - BASE_URL constant

    // Define your API endpoint
    const API_URL = BASE_URL + '/app/Controllers/ExampleController.php';

    // Example function: Load data via API
    function loadData() {
        apiCall(API_URL + '?action=get_data')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Dữ liệu:', data);
                    // Cập nhật giao diện
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => console.error('Lỗi:', error));
    }

    // Example function: Save data via API
    function saveExample() {
        const inputValue = document.getElementById('modal-input').value;
        
        if (!inputValue) {
            alert('Vui lòng nhập dữ liệu');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'save_data');
        formData.append('data', inputValue);

        apiCall(API_URL, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closeModal('example-modal');
                    loadData(); // Tải lại dữ liệu
                }
            })
            .catch(error => console.error('Lỗi:', error));
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadData();

        // Handle form submission
        document.getElementById('settings-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const setting1 = document.getElementById('setting1').value;
            const setting2 = document.getElementById('setting2').value;

            console.log('Cài đặt:', { setting1, setting2 });
            // Gửi đến server...
        });
    });
</script>

<!-- Page-specific styles -->
<style>
    .example-section {
        padding: 20px;
        background: linear-gradient(135deg, rgba(0, 180, 216, 0.1) 0%, rgba(0, 180, 216, 0.05) 100%);
        border-radius: 8px;
    }

    .example-section h2 {
        color: #00d4ff;
        margin-bottom: 20px;
    }
</style>
