<?php
/**
 * Table Component
 * Usage:
 * $table = [
 *     'id' => 'transactions-table',
 *     'columns' => ['ID', 'Username', 'Amount', 'Status', 'Date'],
 *     'rows' => [
 *         ['1', 'user1', '100.000', 'Success', '2024-01-01'],
 *         ['2', 'user2', '50.000', 'Pending', '2024-01-02']
 *     ],
 *     'responsive' => true
 * ];
 * include 'components/table.php';
 */
?>
<table id="<?php echo $table['id'] ?? 'data-table'; ?>" class="admin-table <?php echo ($table['responsive'] ?? false) ? 'responsive' : ''; ?>">
    <thead>
        <tr>
            <?php foreach ($table['columns'] as $column): ?>
                <th><?php echo $column; ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($table['rows'])): ?>
            <?php foreach ($table['rows'] as $row): ?>
                <tr>
                    <?php if (is_array($row)): ?>
                        <?php foreach ($row as $cell): ?>
                            <td><?php echo $cell; ?></td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo count($table['columns']); ?>" class="text-center">No data available</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
