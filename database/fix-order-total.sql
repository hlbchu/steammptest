-- Fix and auto-update order total_amount from order_items
USE `steamweb`;

-- Recalculate totals for existing orders
UPDATE orders o
SET o.total_amount = (
    SELECT COALESCE(SUM(oi.subtotal), SUM(oi.price * oi.quantity), 0)
    FROM order_items oi
    WHERE oi.order_id = o.id
)
WHERE EXISTS (SELECT 1 FROM order_items oi2 WHERE oi2.order_id = o.id);

-- Drop triggers if they exist
DROP TRIGGER IF EXISTS trg_order_items_after_insert;
DROP TRIGGER IF EXISTS trg_order_items_after_update;
DROP TRIGGER IF EXISTS trg_order_items_after_delete;

-- Create triggers to keep total_amount in sync
DELIMITER $$
CREATE TRIGGER trg_order_items_after_insert
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders
    SET total_amount = (
        SELECT COALESCE(SUM(subtotal), SUM(price * quantity), 0)
        FROM order_items
        WHERE order_id = NEW.order_id
    )
    WHERE id = NEW.order_id;
END$$

CREATE TRIGGER trg_order_items_after_update
AFTER UPDATE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders
    SET total_amount = (
        SELECT COALESCE(SUM(subtotal), SUM(price * quantity), 0)
        FROM order_items
        WHERE order_id = NEW.order_id
    )
    WHERE id = NEW.order_id;
END$$

CREATE TRIGGER trg_order_items_after_delete
AFTER DELETE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders
    SET total_amount = (
        SELECT COALESCE(SUM(subtotal), SUM(price * quantity), 0)
        FROM order_items
        WHERE order_id = OLD.order_id
    )
    WHERE id = OLD.order_id;
END$$
DELIMITER ;
