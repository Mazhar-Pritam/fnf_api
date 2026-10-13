-- =====================================================================
-- Car Spare Parts Inventory & Sales Management System
-- Database schema (MySQL / MariaDB, InnoDB, utf8mb4)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS car_spare_parts
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE car_spare_parts;

-- ---------------------------------------------------------------------
-- 1. users  (Admin, Manager/Staff)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name      VARCHAR(100) NOT NULL,
  email          VARCHAR(120) NOT NULL UNIQUE,
  phone          VARCHAR(20),
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('admin','manager') NOT NULL DEFAULT 'manager',
  status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. customers
-- ---------------------------------------------------------------------
CREATE TABLE customers (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  phone       VARCHAR(20),
  email       VARCHAR(120),
  address     VARCHAR(255),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customers_name (name),
  INDEX idx_customers_phone (phone)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. suppliers
-- ---------------------------------------------------------------------
CREATE TABLE suppliers (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  contact_person  VARCHAR(100),
  phone           VARCHAR(20),
  email           VARCHAR(120),
  address         VARCHAR(255),
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_suppliers_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. part_categories
-- ---------------------------------------------------------------------
CREATE TABLE part_categories (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL UNIQUE,
  description  VARCHAR(255)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. parts
--    current_stock is kept in sync by purchases/sales (see stock_movements)
-- ---------------------------------------------------------------------
CREATE TABLE parts (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  part_code       VARCHAR(50)  NOT NULL UNIQUE,          -- code / SKU
  name            VARCHAR(150) NOT NULL,
  category_id     INT UNSIGNED NOT NULL,
  brand           VARCHAR(100),
  purchase_price  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  selling_price   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  opening_stock   INT NOT NULL DEFAULT 0,
  current_stock   INT NOT NULL DEFAULT 0,
  minimum_stock   INT NOT NULL DEFAULT 0,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_parts_category FOREIGN KEY (category_id) REFERENCES part_categories(id),
  CONSTRAINT chk_parts_prices CHECK (purchase_price >= 0 AND selling_price >= 0),
  CONSTRAINT chk_parts_stock  CHECK (current_stock >= 0),
  INDEX idx_parts_name (name),
  INDEX idx_parts_category (category_id),
  INDEX idx_parts_low_stock (current_stock, minimum_stock)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. purchases  (header: one purchase from a supplier)
-- ---------------------------------------------------------------------
CREATE TABLE purchases (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_no     VARCHAR(30) NOT NULL UNIQUE,
  supplier_id     INT UNSIGNED NOT NULL,
  purchase_date   DATE NOT NULL,
  total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  paid_amount     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  due_amount      DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- total_amount - paid_amount
  note            VARCHAR(255),
  created_by      INT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  CONSTRAINT fk_purchases_user     FOREIGN KEY (created_by)  REFERENCES users(id),
  INDEX idx_purchases_date (purchase_date),
  INDEX idx_purchases_supplier (supplier_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. purchase_items  (multiple parts per purchase)
-- ---------------------------------------------------------------------
CREATE TABLE purchase_items (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_id   INT UNSIGNED NOT NULL,
  part_id       INT UNSIGNED NOT NULL,
  quantity      INT NOT NULL,
  unit_cost     DECIMAL(12,2) NOT NULL,                  -- purchase price at that time
  item_total    DECIMAL(12,2) NOT NULL,                  -- quantity * unit_cost
  CONSTRAINT fk_pitems_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  CONSTRAINT fk_pitems_part     FOREIGN KEY (part_id)     REFERENCES parts(id),
  CONSTRAINT chk_pitems_qty CHECK (quantity > 0),
  INDEX idx_pitems_purchase (purchase_id),
  INDEX idx_pitems_part (part_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. sales  (header + invoice)
--    customer_id is NULL for walk-in customers
-- ---------------------------------------------------------------------
CREATE TABLE sales (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no      VARCHAR(30) NOT NULL UNIQUE,
  customer_id     INT UNSIGNED NULL,
  sale_date       DATE NOT NULL,
  subtotal        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  grand_total     DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- subtotal - discount
  paid_amount     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  due_amount      DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- grand_total - paid_amount
  payment_status  ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  note            VARCHAR(255),
  created_by      INT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
  CONSTRAINT fk_sales_user     FOREIGN KEY (created_by)  REFERENCES users(id),
  CONSTRAINT chk_sales_discount CHECK (discount >= 0 AND discount <= subtotal),
  INDEX idx_sales_date (sale_date),
  INDEX idx_sales_customer (customer_id),
  INDEX idx_sales_status (payment_status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. sale_items  (part-wise quantities and prices on the invoice)
--    purchase_price is a snapshot so profit stays correct if prices change
-- ---------------------------------------------------------------------
CREATE TABLE sale_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id         INT UNSIGNED NOT NULL,
  part_id         INT UNSIGNED NOT NULL,
  quantity        INT NOT NULL,
  selling_price   DECIMAL(12,2) NOT NULL,
  purchase_price  DECIMAL(12,2) NOT NULL,                -- cost snapshot for profit
  item_total      DECIMAL(12,2) NOT NULL,                -- quantity * selling_price
  profit          DECIMAL(12,2) NOT NULL,                -- (selling_price - purchase_price) * quantity
  CONSTRAINT fk_sitems_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_sitems_part FOREIGN KEY (part_id) REFERENCES parts(id),
  CONSTRAINT chk_sitems_qty CHECK (quantity > 0),
  INDEX idx_sitems_sale (sale_id),
  INDEX idx_sitems_part (part_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. payments
--     One table for both customer payments (sale_id) and
--     supplier payments (purchase_id). Exactly one must be set.
-- ---------------------------------------------------------------------
CREATE TABLE payments (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_type    ENUM('received','paid') NOT NULL,      -- received = from customer, paid = to supplier
  sale_id         INT UNSIGNED NULL,
  purchase_id     INT UNSIGNED NULL,
  amount          DECIMAL(12,2) NOT NULL,
  method          ENUM('cash','card','mobile_banking') NOT NULL DEFAULT 'cash',
  reference_no    VARCHAR(60),                           -- card / transaction ID
  payment_date    DATE NOT NULL,
  note            VARCHAR(255),
  created_by      INT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_sale     FOREIGN KEY (sale_id)     REFERENCES sales(id),
  CONSTRAINT fk_payments_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  CONSTRAINT fk_payments_user     FOREIGN KEY (created_by)  REFERENCES users(id),
  CONSTRAINT chk_payments_amount CHECK (amount > 0),
  CONSTRAINT chk_payments_target CHECK (
    (sale_id IS NOT NULL AND purchase_id IS NULL) OR
    (sale_id IS NULL AND purchase_id IS NOT NULL)
  ),
  INDEX idx_payments_date (payment_date),
  INDEX idx_payments_sale (sale_id),
  INDEX idx_payments_purchase (purchase_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. stock_movements  (stock movement history)
--     quantity_change is + for purchases, - for sales
-- ---------------------------------------------------------------------
CREATE TABLE stock_movements (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  part_id          INT UNSIGNED NOT NULL,
  movement_type    ENUM('opening','purchase','sale','adjustment') NOT NULL,
  quantity_change  INT NOT NULL,
  stock_after      INT NOT NULL,
  purchase_id      INT UNSIGNED NULL,
  sale_id          INT UNSIGNED NULL,
  note             VARCHAR(255),
  created_by       INT UNSIGNED NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_moves_part     FOREIGN KEY (part_id)     REFERENCES parts(id),
  CONSTRAINT fk_moves_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  CONSTRAINT fk_moves_sale     FOREIGN KEY (sale_id)     REFERENCES sales(id),
  CONSTRAINT fk_moves_user     FOREIGN KEY (created_by)  REFERENCES users(id),
  INDEX idx_moves_part_date (part_id, created_at)
) ENGINE=InnoDB;

-- =====================================================================
-- Useful views for dashboard & reports
-- =====================================================================

-- Low-stock report / alerts: Current Stock <= Minimum Stock
CREATE OR REPLACE VIEW v_low_stock AS
SELECT id, part_code, name, current_stock, minimum_stock
FROM parts
WHERE status = 'active' AND current_stock <= minimum_stock;

-- Customer due report
CREATE OR REPLACE VIEW v_customer_dues AS
SELECT c.id AS customer_id, c.name, c.phone,
       SUM(s.due_amount) AS total_due
FROM customers c
JOIN sales s ON s.customer_id = c.id
GROUP BY c.id, c.name, c.phone
HAVING total_due > 0;

-- Supplier due report
CREATE OR REPLACE VIEW v_supplier_dues AS
SELECT sp.id AS supplier_id, sp.name, sp.phone,
       SUM(p.due_amount) AS total_due
FROM suppliers sp
JOIN purchases p ON p.supplier_id = sp.id
GROUP BY sp.id, sp.name, sp.phone
HAVING total_due > 0;

-- Current inventory value (at purchase price)
CREATE OR REPLACE VIEW v_inventory_value AS
SELECT SUM(current_stock * purchase_price) AS inventory_value
FROM parts
WHERE status = 'active';

-- Daily sales & profit
CREATE OR REPLACE VIEW v_daily_sales AS
SELECT s.sale_date,
       COUNT(DISTINCT s.id) AS invoices,
       SUM(si.item_total)   AS gross_sales,
       SUM(si.profit)       AS parts_profit
FROM sales s
JOIN sale_items si ON si.sale_id = s.id
GROUP BY s.sale_date;
