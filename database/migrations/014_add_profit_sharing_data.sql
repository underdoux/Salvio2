INSERT IGNORE INTO monthly_profits 
(month, total_sales, total_costs, total_commissions, total_expenses, net_profit, status) 
VALUES 
('2024-01-01', 100000.00, 60000.00, 5000.00, 10000.00, 25000.00, 'final'),
('2024-02-01', 120000.00, 70000.00, 6000.00, 12000.00, 32000.00, 'final'),
('2024-03-01', 110000.00, 65000.00, 5500.00, 11000.00, 28500.00, 'draft');

-- Insert sample distributions for existing investors
INSERT IGNORE INTO profit_distributions 
(profit_id, investor_id, percentage, amount, status)
SELECT 
    mp.id,
    i.id,
    60.00,
    mp.net_profit * 0.60,
    'paid'
FROM monthly_profits mp
CROSS JOIN (SELECT id FROM investors LIMIT 1) i
WHERE mp.month = '2024-01-01';

INSERT IGNORE INTO profit_distributions 
(profit_id, investor_id, percentage, amount, status)
SELECT 
    mp.id,
    i.id,
    40.00,
    mp.net_profit * 0.40,
    'paid'
FROM monthly_profits mp
CROSS JOIN (SELECT id FROM investors ORDER BY id DESC LIMIT 1) i
WHERE mp.month = '2024-01-01';
