-- Insert initial system settings
INSERT INTO settings (`key`, `value`, `type`, `description`) VALUES
-- Currency Settings (existing)
('currency', '{"code":"IDR","symbol":"Rp","decimal_separator":",","thousand_separator":".","decimal_places":0}', 'json', 'Currency formatting settings'),
('max_discount_percent', '50', 'int', 'Maximum allowed discount percentage'),
('max_discount_amount', '10000000', 'int', 'Maximum allowed discount amount in IDR'),

-- Product Management Settings
('product.sku_format', 'MED-{CATEGORY}-{NUMBER}', 'string', 'Format for generating product SKUs'),
('product.require_bpom', 'true', 'bool', 'Require BPOM registration for products'),
('product.expiry_warning_days', '90', 'int', 'Days before expiry to show warnings'),
('product.categories', '["Antibiotics","Analgesics","Vitamins","Supplements","Medical Supplies"]', 'json', 'Default product categories'),
('product.storage_conditions', '["Room Temperature","Refrigerated","Frozen"]', 'json', 'Product storage condition options'),

-- Inventory Control Settings
('inventory.low_stock_threshold', '20', 'int', 'Low stock warning threshold'),
('inventory.critical_stock_threshold', '10', 'int', 'Critical stock warning threshold'),
('inventory.auto_order_threshold', '15', 'int', 'Threshold for automatic reorder suggestions'),
('inventory.batch_tracking', 'true', 'bool', 'Enable batch number tracking'),
('inventory.expiry_tracking', 'true', 'bool', 'Enable expiry date tracking'),
('inventory.location_tracking', 'true', 'bool', 'Enable storage location tracking'),
('inventory.count_schedule', '{"frequency":"monthly","day":1,"notification_days":[7,3,1]}', 'json', 'Stock count schedule settings'),

-- Sales Analytics Settings
('analytics.target_margin', '25', 'float', 'Target profit margin percentage'),
('analytics.sales_forecast_months', '3', 'int', 'Number of months for sales forecasting'),
('analytics.trend_analysis_period', '6', 'int', 'Months of data for trend analysis'),
('analytics.customer_segments', '["Regular","VIP","Wholesale","Retail"]', 'json', 'Customer segmentation categories'),
('analytics.performance_metrics', '["revenue","margin","turnover","customer_satisfaction"]', 'json', 'Key performance metrics to track'),

-- Customer Management Settings
('customer.credit_limit_default', '50000000', 'int', 'Default credit limit for new customers'),
('customer.payment_terms_default', '30', 'int', 'Default payment terms in days'),
('customer.require_license', 'true', 'bool', 'Require pharmacy/clinic license for registration'),
('customer.license_expiry_warning', '30', 'int', 'Days before license expiry to notify'),
('customer.rating_system', '{"enabled":true,"factors":["payment_history","order_volume","relationship_length"]}', 'json', 'Customer rating system configuration'),

-- Supplier Management Settings
('supplier.evaluation_period', '90', 'int', 'Days between supplier evaluations'),
('supplier.performance_metrics', '{"delivery_time":30,"quality":40,"price":30}', 'json', 'Supplier evaluation criteria weights'),
('supplier.minimum_order_value', '5000000', 'int', 'Minimum order value for suppliers'),
('supplier.lead_time_warning', '7', 'int', 'Days before warning about supplier lead time'),
('supplier.quality_threshold', '95', 'float', 'Minimum acceptable quality percentage'),

-- Document Generation Settings
('document.invoice_format', 'INV/{YYYY}/{MM}/{NUMBER}', 'string', 'Invoice number format'),
('document.po_format', 'PO/{YYYY}/{MM}/{NUMBER}', 'string', 'Purchase order number format'),
('document.receipt_format', 'RCP/{YYYY}/{MM}/{NUMBER}', 'string', 'Receipt number format'),
('document.header_info', '{"company_name":"Pharmacy Name","address":"Company Address","phone":"Phone Number","email":"Email"}', 'json', 'Document header information'),
('document.footer_text', 'Thank you for your business. All medicines must be stored properly.', 'string', 'Default document footer text'),
('document.terms_conditions', '{"return_policy":"7 days","payment_terms":"30 days","storage_instructions":"Store in cool, dry place"}', 'json', 'Default terms and conditions'),

-- Quality Control Settings
('quality.temperature_range', '{"min":20,"max":25,"unit":"C"}', 'json', 'Storage temperature range'),
('quality.humidity_range', '{"min":40,"max":60,"unit":"%"}', 'json', 'Storage humidity range'),
('quality.inspection_checklist', '["packaging_integrity","expiry_date","storage_conditions","documentation"]', 'json', 'Quality inspection checklist'),
('quality.quarantine_period', '3', 'int', 'Default quarantine period in days'),
('quality.batch_testing', 'true', 'bool', 'Require batch testing before release'),

-- Compliance Settings
('compliance.required_licenses', '["pharmacy_license","narcotic_license","business_permit"]', 'json', 'Required business licenses'),
('compliance.document_retention', '{"invoices":"7y","prescriptions":"3y","inventory":"5y"}', 'json', 'Document retention periods'),
('compliance.controlled_substances', '{"require_double_verification":true,"max_order_quantity":100}', 'json', 'Controlled substance handling rules'),
('compliance.audit_frequency', '90', 'int', 'Days between internal audits'),
('compliance.signature_requirements', '["invoices","controlled_substances","returns"]', 'json', 'Documents requiring signatures');
