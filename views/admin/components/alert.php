<?php
/**
 * Alert/Notification Component
 * Usage:
 * $alert = ['type' => 'success', 'message' => 'Operation successful!'];
 * include 'components/alert.php';
 * 
 * Types: success, error, warning, info
 */
?>
<?php if (!empty($alert)): ?>
<div class="alert alert-<?php echo $alert['type'] ?? 'info'; ?> alert-dismissible">
    <strong><?php echo ucfirst($alert['type'] ?? 'Info'); ?>!</strong> 
    <?php echo $alert['message']; ?>
    <button type="button" class="close-btn" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>

<style>
.alert {
    padding: 12px 20px;
    margin-bottom: 15px;
    border-radius: 4px;
    border-left: 4px solid;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.alert-success {
    background-color: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.alert-error {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffc107;
    color: #856404;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #17a2b8;
    color: #0c5460;
}

.alert .close-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    opacity: 0.5;
}

.alert .close-btn:hover {
    opacity: 1;
}
</style>
