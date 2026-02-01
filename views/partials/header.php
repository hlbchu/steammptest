<?php
// Skip header in embedded views
if (isset($_GET['embed']) && $_GET['embed'] === '1') {
	return;
}

// Use legacy header layout as original
include __DIR__ . '/header-layout.php';
?>
