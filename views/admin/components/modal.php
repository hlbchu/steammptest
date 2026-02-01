<?php
/**
 * Modal Component
 * Usage:
 * $modal = [
 *     'id' => 'modal-id',
 *     'title' => 'Modal Title',
 *     'body' => 'Modal content HTML',
 *     'buttons' => [
 *         ['label' => 'Save', 'class' => 'btn-primary', 'onclick' => 'saveFunction()'],
 *         ['label' => 'Cancel', 'class' => 'btn-secondary', 'data-dismiss' => 'modal']
 *     ]
 * ];
 * include 'components/modal.php';
 */
?>
<div id="<?php echo $modal['id']; ?>" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $modal['title']; ?></h2>
            <button class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <?php echo $modal['body']; ?>
        </div>
        <?php if (!empty($modal['buttons'])): ?>
        <div class="modal-footer">
            <?php foreach ($modal['buttons'] as $btn): ?>
                <button class="btn <?php echo $btn['class'] ?? 'btn-secondary'; ?>" 
                        <?php echo isset($btn['onclick']) ? 'onclick="' . $btn['onclick'] . '"' : ''; ?>
                        <?php echo isset($btn['data-dismiss']) ? 'data-dismiss="modal"' : ''; ?>>
                    <?php echo $btn['label']; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
