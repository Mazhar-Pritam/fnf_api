-- Run this ONLY if the purchase_items table already exists (it has no sale_price column yet).
-- If you create purchase_items fresh with purchase_items.sql, you do NOT need this.
ALTER TABLE purchase_items
  ADD COLUMN sale_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER unit_price;
