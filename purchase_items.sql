-- 1) Items (products) of each purchase
CREATE TABLE purchase_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  part_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  sale_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (purchase_id),
  INDEX (part_id)
);

-- 2) Your crud_class adds "deleted_at IS NULL" to every select, so every table
--    read with common_select needs a deleted_at column.
--    Check with: DESCRIBE purchases;  (and DESCRIBE customers;)
--    Run the line below ONLY for a table that does not have deleted_at yet:
-- ALTER TABLE purchases ADD COLUMN deleted_at DATETIME NULL;
-- ALTER TABLE customers ADD COLUMN deleted_at DATETIME NULL;
