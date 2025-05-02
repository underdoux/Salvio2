-- Insert expense categories
INSERT INTO expense_categories (name, description) VALUES
('Rent', 'Office and warehouse rent'),
('Utilities', 'Electricity, water, internet'),
('Salaries', 'Employee salaries'),
('Transportation', 'Delivery and transportation costs'),
('Office Supplies', 'General office supplies');

-- Insert product categories
INSERT INTO categories (name, commission_rate) VALUES
('Antibiotics', 2.50),
('Pain Relief', 2.00),
('Vitamins', 3.00),
('First Aid', 2.00),
('Chronic Disease', 2.50);

-- Insert sample customers
INSERT INTO customers (name, type, contact_person, phone, email, address) VALUES
('Pharmacy Plus', 'pharmacy', 'John Doe', '081234567890', 'contact@pharmacyplus.com', 'Jl. Pharmacy No. 123'),
('City Clinic', 'clinic', 'Jane Smith', '081234567891', 'info@cityclinic.com', 'Jl. Health No. 456'),
('General Hospital', 'hospital', 'Dr. Wilson', '081234567892', 'procurement@generalhospital.com', 'Jl. Hospital No. 789');

-- Insert notification templates
INSERT INTO notification_templates (type, name, subject, content, variables) VALUES
('new_order', 'New Order Notification', 'New Order #{order_number}', 'Dear {recipient_name},\n\nA new order #{order_number} has been placed by {customer_name}.\n\nTotal Amount: {total_amount}\n\nBest regards,\nSalvio POS', '["order_number", "recipient_name", "customer_name", "total_amount"]'),
('order_status', 'Order Status Update', 'Order #{order_number} Status Update', 'Dear {recipient_name},\n\nYour order #{order_number} status has been updated to {status}.\n\nBest regards,\nSalvio POS', '["order_number", "recipient_name", "status"]'),
('low_stock', 'Low Stock Alert', 'Low Stock Alert - {product_name}', 'Dear {recipient_name},\n\nProduct {product_name} is running low on stock. Current quantity: {current_stock}\n\nPlease restock soon.\n\nBest regards,\nSalvio POS', '["product_name", "recipient_name", "current_stock"]');

-- Insert commission rates
INSERT INTO commission_rates (type, rate) VALUES
('global', 2.00);  -- Default global commission rate

-- Insert sample products
INSERT INTO products (name, bpom_id, category_id, description, purchase_price, selling_price, stock_type, min_stock) VALUES
('Amoxicillin 500mg', 'BPOM001', 1, 'Antibiotic capsules 500mg', 25000.00, 35000.00, 'stocked', 100),
('Paracetamol 500mg', 'BPOM002', 2, 'Pain relief tablets 500mg', 15000.00, 22000.00, 'stocked', 200),
('Vitamin C 1000mg', 'BPOM003', 3, 'Vitamin C tablets 1000mg', 35000.00, 48000.00, 'stocked', 150),
('Bandage Roll', 'BPOM004', 4, 'Sterile bandage roll 10cm x 5m', 12000.00, 18000.00, 'stocked', 50),
('Metformin 500mg', 'BPOM005', 5, 'Diabetes medication 500mg', 45000.00, 62000.00, 'stocked', 100);

-- Insert initial stock
INSERT INTO stock (product_id, quantity, batch_number, expiry_date) VALUES
(1, 500, 'BATCH001', '2024-12-31'),
(2, 1000, 'BATCH002', '2024-12-31'),
(3, 750, 'BATCH003', '2024-12-31'),
(4, 200, 'BATCH004', '2024-12-31'),
(5, 300, 'BATCH005', '2024-12-31');
