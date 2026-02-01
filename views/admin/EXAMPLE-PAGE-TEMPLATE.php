<?php
/**
 * EXAMPLE: How to create a new admin page
 * Copy this file and modify for your needs
 */

$page_title = 'Page Title Here';
$page_content = 'page-slug-content.php';

// Load the main layout template
require_once 'layout.php';

/**
 * That's it! The layout.php will:
 * 1. Check authentication
 * 2. Include sidebar.php
 * 3. Display page header with your $page_title
 * 4. Include components/[page-slug]-content.php
 * 5. Provide global JavaScript helpers
 * 6. Apply global styles
 */
