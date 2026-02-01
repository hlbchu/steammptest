document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.btn-action[data-id]');

    buttons.forEach(button => {
        button.addEventListener('click', async function () {
            const id = this.dataset.id;
            const isHot = this.dataset.hot === '1' ? 0 : 1;

            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('is_hot', isHot);

                const response = await fetch('../../app/Controllers/AdminProductController.php?action=toggleHot', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    // Update button state
                    this.dataset.hot = String(isHot);
                    this.textContent = isHot === 1 ? 'Bỏ Hot' : 'Đặt Hot';

                    const row = this.closest('tr');
                    const badgeCell = row.querySelector('td:nth-child(6)');
                    badgeCell.innerHTML = isHot === 1
                        ? '<span class="status-badge status-active">HOT</span>'
                        : '<span class="status-badge status-hidden">OFF</span>';
                } else {
                    alert(result.message || 'Có lỗi xảy ra');
                }
            } catch (error) {
                alert('Không thể cập nhật trạng thái Hot');
            }
        });
    });
});
