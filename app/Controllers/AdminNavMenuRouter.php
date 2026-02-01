<?php
/**
 * Admin Navigation Menu Controller Router
 */
session_start();

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once 'AdminNavMenuController.php';

$db = new Database();
$controller = new AdminNavMenuController();

$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'index':
        $controller->index($db);
        break;
    case 'create':
        $controller->create($db);
        break;
    case 'update':
        $controller->update($db);
        break;
    case 'delete':
        $controller->delete($db);
        break;
    case 'toggleActive':
        $controller->toggleActive($db);
        break;
    case 'getSubmenus':
        $controller->getSubmenus($db);
        break;
    case 'createSubmenu':
        $controller->createSubmenu($db);
        break;
    case 'updateSubmenu':
        $controller->updateSubmenu($db);
        break;
    case 'deleteSubmenu':
        $controller->deleteSubmenu($db);
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Action not found']);
        break;
}
