<?php
/**
 * Tab Navigation Component
 * Usage: 
 * $tabs = [
 *     ['id' => 'tab1', 'label' => 'Tab 1', 'active' => true],
 *     ['id' => 'tab2', 'label' => 'Tab 2']
 * ];
 * include 'components/tabs.php';
 */
?>
<div class="bank-tabs">
    <?php foreach ($tabs as $tab): ?>
        <button class="tab-btn <?php echo ($tab['active'] ?? false) ? 'active' : ''; ?>" data-tab="<?php echo $tab['id']; ?>">
            <?php echo $tab['label']; ?>
        </button>
    <?php endforeach; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTabs();
    });
</script>
