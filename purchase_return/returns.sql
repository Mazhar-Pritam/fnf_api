-- Run this once in phpMyAdmin (SQL tab). It creates the four return tables.
-- It needs the purchases, purchase_items, sales and sale_items tables to exist already.

-- 1) Purchase returns (goods sent back to a supplier -> stock goes DOWN)
CREATE TABLE purchase_returns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  return_no VARCHAR(50) NOT NULL,
  purchase_id INT NOT NULL,
  supplier_id INT NOT NULL,
  return_date DATE NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX (purchase_id),
  INDEX (supplier_id)
);

CREATE TABLE purchase_return_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_return_id INT NOT NULL,
  part_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (purchase_return_id),
  INDEX (part_id)
);

-- 2) Sale returns (goods brought back by a customer -> stock goes UP)
CREATE TABLE sale_returns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  return_no VARCHAR(50) NOT NULL,
  sale_id INT NOT NULL,
  customer_id INT NOT NULL,
  return_date DATE NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX (sale_id),
  INDEX (customer_id)
);

CREATE TABLE sale_return_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_return_id INT NOT NULL,
  part_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (sale_return_id),
  INDEX (part_id)
);
