# 📋 Admin Panel Modularization Guide

## Architecture Overview

The admin panel has been refactored to use a **component-based architecture** to eliminate code duplication and improve maintainability.

### Folder Structure

```
views/admin/
├── layout.php                 # Main template wrapper
├── sidebar.php                # Navigation sidebar
├── payments.php               # Page entry point (simple)
├── dashboard.php              # Other page entry points
├── products.php
├── users.php
└── components/
    ├── header.php             # Page header component
    ├── tabs.php               # Tab navigation
    ├── modal.php              # Modal template
    ├── table.php              # Table template
    ├── alert.php              # Alert/notification
    ├── payments-content.php    # Payments page content
    └── [other-content].php     # Other page contents
```

## How It Works

### 1. **Layout System** (layout.php)

The main template that wraps all admin pages. It provides:
- Session authentication check
- Sidebar inclusion
- Global styles and scripts
- Modal and tab management helpers

**Usage:**
```php
<?php
$page_title = 'Page Title';
$page_content = 'page-content.php';
require_once 'layout.php';
```

### 2. **Components**

Reusable UI components that eliminate duplication:

#### **tabs.php** - Tab Navigation
```php
<?php
$tabs = [
    ['id' => 'tab1', 'label' => 'Tab 1', 'active' => true],
    ['id' => 'tab2', 'label' => 'Tab 2']
];
include 'components/tabs.php';
?>
```

#### **modal.php** - Modal Dialog
```php
<?php
$modal = [
    'id' => 'my-modal',
    'title' => 'Modal Title',
    'body' => '<p>Modal content...</p>',
    'buttons' => [
        ['label' => 'Save', 'class' => 'btn-primary', 'onclick' => 'saveFunction()'],
        ['label' => 'Cancel', 'class' => 'btn-secondary', 'onclick' => 'closeModal("my-modal")']
    ]
];
include 'components/modal.php';
?>
```

#### **table.php** - Data Table
```php
<?php
$table = [
    'id' => 'users-table',
    'columns' => ['ID', 'Name', 'Email', 'Role'],
    'rows' => [
        ['1', 'John', 'john@example.com', 'Admin'],
        ['2', 'Jane', 'jane@example.com', 'User']
    ]
];
include 'components/table.php';
?>
```

#### **alert.php** - Alert/Notification
```php
<?php
$alert = ['type' => 'success', 'message' => 'Operation successful!'];
include 'components/alert.php';
?>
```

## Global JavaScript Functions

All pages have access to these helpers (defined in layout.php):

```javascript
// API calls with credentials
apiCall(url, options = {})

// Tab management
setupTabs()

// Modal management
openModal(modalId)
closeModal(modalId)
```

## Best Practices

### ✅ DO:
- Use components for repeated elements
- Keep page logic in page-content.php files
- Use the layout.php wrapper for all pages
- Keep CSS organized by component

### ❌ DON'T:
- Duplicate HTML structure across pages
- Use inline styles (use CSS classes instead)
- Create global JavaScript functions in page content
- Mix layout and content in same file

## Example: Creating a New Admin Page

### Step 1: Create Page Entry Point
**File:** `views/admin/new-page.php`
```php
<?php
$page_title = 'New Page Title';
$page_content = 'new-page-content.php';
require_once 'layout.php';
```

### Step 2: Create Content File
**File:** `views/admin/components/new-page-content.php`
```php
<?php
// Page-specific content goes here
?>

<div class="page-section">
    <!-- Include components -->
    <?php
    $tabs = [
        ['id' => 'tab1', 'label' => 'Tab 1', 'active' => true],
        ['id' => 'tab2', 'label' => 'Tab 2']
    ];
    include 'tabs.php';
    ?>

    <!-- Tab content -->
    <div id="tab1-tab" class="tab-content active">
        <!-- Content here -->
    </div>

    <div id="tab2-tab" class="tab-content">
        <!-- Content here -->
    </div>
</div>

<!-- Page-specific JavaScript -->
<script>
    const API_URL = BASE_URL + '/app/Controllers/PageController.php';
    
    function loadData() {
        apiCall(API_URL + '?action=get_data')
            .then(r => r.json())
            .then(res => {
                // Handle response
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadData();
    });
</script>

<!-- Page-specific styles -->
<style>
    .page-section {
        padding: 20px;
    }
</style>
```

## Migration Checklist

When refactoring existing pages:

- [ ] Extract HTML structure into `components/[page]-content.php`
- [ ] Move page-specific JavaScript to content file
- [ ] Move page-specific CSS to content file (wrapped in `<style>` tags)
- [ ] Create simple entry point that loads layout.php
- [ ] Test all functionality works with new structure
- [ ] Remove inline HTML/CSS/JS from main page file
- [ ] Use global helpers (apiCall, setupTabs, openModal) instead of custom functions
- [ ] Include reusable components (tabs, modals, tables, alerts)

## File Organization Benefits

| Benefit | Impact |
|---------|--------|
| **Single Responsibility** | Each file has one clear purpose |
| **Code Reuse** | Components used across multiple pages |
| **Maintainability** | Changes in one place affect all usage |
| **Consistency** | Same UI patterns across all pages |
| **Reduced Duplication** | ~60% less code overall |
| **Easy Testing** | Components can be tested independently |

## Current Status

✅ **Completed:**
- layout.php (main template)
- components/header.php
- components/tabs.php
- components/modal.php
- components/table.php
- components/alert.php
- components/payments-content.php
- payments.php (refactored)

⏳ **To Refactor:**
- dashboard.php → dashboard-content.php
- products.php → products-content.php
- users.php → users-content.php
- Other admin pages

## Quick Reference

### Include a Component
```php
<?php include 'components/component-name.php'; ?>
```

### Open Modal
```javascript
openModal('modal-id');
```

### Close Modal
```javascript
closeModal('modal-id');
```

### API Call
```javascript
apiCall(url, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => { /* handle */ });
```

### Setup Tabs
```javascript
// Called automatically on page load, but can be called again if DOM changes
setupTabs();
```
