# ✅ Game Download System - Testing Checklist

## 🗂️ Installation & Setup

- [ ] Database tables created successfully
  ```bash
  mysql -u root -p steamweb < database/add-game-downloads.sql
  ```
  
- [ ] Verify tables exist
  ```sql
  SHOW TABLES LIKE '%download%';
  ```
  
- [ ] Check sample data inserted
  ```sql
  SELECT COUNT(*) FROM game_downloads;
  SELECT COUNT(*) FROM download_links_history;
  ```

## 🛠️ Files & Code

- [ ] `AdminGameDownloadController.php` created
- [ ] `game-downloads.php` view created
- [ ] `admin-sidebar.php` component created
- [ ] `public/index.php` routing updated
- [ ] `add-game-downloads.sql` database schema

## 🔗 Routing Tests

### Access Admin Panel
- [ ] URL: `/public/index.php?page=admin&section=game-downloads`
- [ ] Page loads without errors
- [ ] Sidebar displays correctly
- [ ] "📥 Link Tải Game" menu is active

### API Endpoints
- [ ] POST `?action=admin_game_download_add` → Works
- [ ] POST `?action=admin_game_download_update` → Works
- [ ] POST `?action=admin_game_download_delete` → Works
- [ ] POST `?action=admin_game_download_detail` → Returns JSON
- [ ] POST `?action=admin_game_download_stats` → Returns JSON

## 🎨 UI Tests

### Table Display
- [ ] All columns display: ID, Game, Tên Tải, Kích Thước, Phương Thức, Phiên Bản, Lượt Tải, Người Dùng, Trạng Thái, Hành Động
- [ ] Sample data shows in table
- [ ] Badge colors correct (direct=blue, torrent=purple, mirror=green)
- [ ] Status badges correct (active=green, inactive=red)

### Filters
- [ ] Filter by Game works
- [ ] Filter by Status works
- [ ] Search by keyword works
- [ ] Filters can be combined

### Modal Forms

#### Add Modal
- [ ] Click "+ Thêm Link Tải" opens modal
- [ ] Modal title: "Thêm Link Tải Game"
- [ ] All fields present and empty
- [ ] Required fields marked with *
- [ ] Can submit and create new record

#### Edit Modal
- [ ] Click "✎ Sửa" button opens modal
- [ ] Modal title: "Sửa Link Tải Game"
- [ ] All fields pre-filled with existing data
- [ ] Can edit and update record
- [ ] URL history logged if URL changed

#### Delete
- [ ] Click "✕ Xóa" shows confirmation
- [ ] Confirm deletes record
- [ ] Page refreshes and record gone
- [ ] Related logs still exist (cascade delete controlled)

#### Statistics Modal
- [ ] Click "📊" shows stats
- [ ] Stats accurate for that download
- [ ] Shows: Total Downloads, Users, Completed, Failed

## 🔐 Security Tests

### Authentication
- [ ] Non-logged-in user cannot access
- [ ] Non-admin user cannot access
- [ ] Session check enforced

### Data Validation
- [ ] Cannot submit empty required fields
- [ ] Invalid URLs rejected
- [ ] Product ID validated
- [ ] SQL injection protection (parameterized queries)

### Authorization
- [ ] Only admin can modify downloads
- [ ] User cannot access other user's data
- [ ] Foreign key constraints enforced

## 📊 Database Tests

### game_downloads table
```sql
SELECT * FROM game_downloads;
-- Should show: id, product_id, download_name, download_url, file_size, download_method, version, system_requirements, description, is_active, created_at, updated_at
```

- [ ] Table structure correct
- [ ] Unique constraint on (product_id, version)
- [ ] Foreign key to products works
- [ ] Default is_active = 1

### user_game_downloads table
```sql
SELECT * FROM user_game_downloads;
-- Sample: user_id=1, product_id=1, order_id=1, game_download_id=1
```

- [ ] Table structure correct
- [ ] Unique constraint on (user_id, product_id)
- [ ] Foreign keys to users, products, orders, game_downloads work
- [ ] is_active defaults to true

### download_logs table
```sql
SELECT COUNT(*) FROM download_logs;
-- Should be > 0 after testing
```

- [ ] Table structure correct
- [ ] Tracks user_id, product_id, game_download_id
- [ ] Records IP address, user_agent
- [ ] Status field (started/completed/failed/paused)
- [ ] Error messages captured

### download_links_history table
```sql
SELECT * FROM download_links_history;
-- Should show URL change history
```

- [ ] Records old_url, new_url
- [ ] Shows reason for change
- [ ] Shows changed_by admin user
- [ ] Timestamp accurate

## 🔄 Workflow Tests

### Add New Download Link
1. [ ] Access admin panel
2. [ ] Click "+ Thêm Link Tải"
3. [ ] Select Game: "ELDEN RING"
4. [ ] Enter Name: "ELDEN RING - Full Game"
5. [ ] Enter URL: "https://example.com/elden-ring.zip"
6. [ ] Enter Size: "60GB"
7. [ ] Select Method: "Direct"
8. [ ] Enter Version: "1.0"
9. [ ] Check "Kích hoạt ngay"
10. [ ] Click "Lưu"
11. [ ] Verify appears in table
12. [ ] Check database record created

### Edit Download Link
1. [ ] Click "✎ Sửa" button
2. [ ] Change URL to: "https://newserver.com/elden-ring.zip"
3. [ ] Click "Lưu"
4. [ ] Verify updated in table
5. [ ] Check download_links_history has entry
6. [ ] Verify old_url and new_url recorded

### Delete Download Link
1. [ ] Click "✕ Xóa" button
2. [ ] Confirm deletion
3. [ ] Verify removed from table
4. [ ] Check database (SELECT * FROM game_downloads WHERE id = X) returns empty

### View Statistics
1. [ ] Click "📊" button
2. [ ] Modal shows stats
3. [ ] Numbers are correct/reasonable
4. [ ] Can close modal

## 🔌 Integration Tests

### Grant Access to User (Manual SQL)
```sql
INSERT INTO user_game_downloads (user_id, product_id, order_id, game_download_id, is_active)
SELECT 2, 1, (SELECT MAX(id) FROM orders), gd.id, 1
FROM game_downloads gd
WHERE gd.product_id = 1 AND gd.is_active = 1
LIMIT 1;
```

- [ ] User can now access download
- [ ] Record created correctly
- [ ] expires_at is NULL (never expires)

### Log Download Attempt (Manual SQL)
```sql
INSERT INTO download_logs (user_id, product_id, game_download_id, download_url, ip_address, status)
VALUES (2, 1, 1, 'https://example.com/game.zip', '192.168.1.1', 'completed');

-- Check stats update
SELECT * FROM download_logs WHERE user_id = 2;
```

- [ ] Log created
- [ ] Stats reflect new download
- [ ] Status correctly recorded

## 📈 Performance Tests

### Load Testing
- [ ] Page loads in < 2 seconds with 100+ downloads
- [ ] Filters respond quickly
- [ ] Modal opens instantly
- [ ] Submit forms complete < 1 second

### Database Queries
- [ ] getAllDownloads() uses indexes
- [ ] Queries return data in < 100ms
- [ ] No N+1 queries (check with Query Analyzer)

## 🌐 Browser Tests

### Chrome/Edge
- [ ] Desktop view correct
- [ ] Mobile view responsive
- [ ] All buttons clickable
- [ ] Forms work properly

### Firefox/Safari
- [ ] Styling consistent
- [ ] Layout correct
- [ ] No JavaScript errors

### Mobile (iPhone/Android)
- [ ] Table scrollable horizontally
- [ ] Modals responsive
- [ ] Touch-friendly buttons
- [ ] Readable text size

## 📝 Documentation Tests

- [ ] `GAME_DOWNLOAD_README.md` exists and clear
- [ ] `DOWNLOAD_SYSTEM_GUIDE.md` comprehensive
- [ ] `GAME_DOWNLOAD_ARCHITECTURE.md` has diagrams
- [ ] All code comments present
- [ ] SQL examples work

## 🚀 Final Checklist

- [ ] All tests passed
- [ ] No console errors
- [ ] No database errors
- [ ] No broken links
- [ ] Admin can fully manage downloads
- [ ] User permissions enforced
- [ ] Data consistent across tables
- [ ] Backups taken before changes
- [ ] Ready for production

## 🐛 Known Issues & Workarounds

None identified yet. Report any issues found during testing.

## 📞 Testing Support

If you encounter errors:

1. Check browser console (F12) for JavaScript errors
2. Check server logs for PHP errors
3. Run database verification queries:
   ```sql
   SELECT COUNT(*) FROM game_downloads;
   SELECT COUNT(*) FROM user_game_downloads;
   SELECT COUNT(*) FROM download_logs;
   ```
4. Verify foreign keys:
   ```sql
   SHOW CREATE TABLE game_downloads\G
   SHOW CREATE TABLE user_game_downloads\G
   ```

---

**Test Document Version:** 1.0  
**Last Updated:** January 31, 2026  
**System:** SteamWeb Game Store
