-- Insert test customers
INSERT INTO customers (name, type, contact_person, phone, email, address) VALUES
('ABC Pharmacy', 'pharmacy', 'John Doe', '123-456-7890', 'john@abcpharmacy.com', '123 Main St'),
('City Clinic', 'clinic', 'Jane Smith', '098-765-4321', 'jane@cityclinic.com', '456 Oak Ave'),
('General Hospital', 'hospital', 'Bob Wilson', '555-123-4567', 'bob@genhospital.com', '789 Pine Rd');

-- Insert test products (if not exists)
INSERT IGNORE INTO products (name, bpom_id, category_id, purchase_price, selling_price, stock_type, min_stock) VALUES
('Paracetamol 500mg', 'BPOM001', 1, 5000, 7500, 'stocked', 100),
('Amoxicillin 500mg', 'BPOM002', 1, 8000, 12000, 'stocked', 50),
('Vitamin C 1000mg', 'BPOM003', 2, 15000, 25000, 'stocked', 75);

-- Insert initial stock
INSERT INTO stock (product_id, quantity, batch_number, expiry_date) 
SELECT id, 200, CONCAT('BATCH', id, '2024'), '2024-12-31' FROM products;

-- Insert test orders
INSERT INTO orders (customer_id, order_number, total_amount, status, payment_type, created_by) VALUES
(1, 'ORD-2024-001', 150000, 'completed', 'cash', 1),
(2, 'ORD-2024-002', 240000, 'in_progress', 'installment', 1),
(3, 'ORD-2024-003', 375000, 'new', 'cash', 1);

-- Insert order items
INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES
(1, 1, 10, 7500, 75000),
(1, 2, 5, 12000, 60000),
(2, 2, 20, 12000, 240000),
(3, 3, 15, 25000, 375000);

-- Insert payments for completed orders
INSERT INTO payments (order_id, amount, payment_date, payment_method, reference_number, created_by) VALUES
(1, 150000, CURDATE(), 'cash', 'PAY-2024-001', 1);

-- Insert commission rates
INSERT INTO commission_rates (type, reference_id, rate) VALUES
('global', NULL, 2.5),
('category', 1, 3.0),
('product', 3, 5.0);

-- Insert sales commissions
INSERT INTO sales_commissions (order_id, user_id, amount, status) VALUES
(1, 1, 3750, 'paid'),
(2, 1, 7200, 'pending'),
(3, 1, 18750, 'pending');
