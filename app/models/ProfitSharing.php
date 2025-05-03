<?php

require_once __DIR__ . '/Notification.php';

class ProfitSharing extends BaseModel {
    private $notification;

    public function __construct() {
        parent::__construct();
        $this->notification = new Notification();
    }

    // ... (keep existing methods) ...

    public function exportProfitReport($month) {
        $sql = "SELECT 
                    mp.*,
                    (mp.total_sales - mp.total_costs) as gross_profit,
                    ((mp.total_sales - mp.total_costs) / mp.total_sales * 100) as gross_margin,
                    (mp.net_profit / mp.total_sales * 100) as net_margin,
                    COUNT(pd.id) as total_distributions,
                    SUM(pd.amount) as total_distributed
                FROM monthly_profits mp
                LEFT JOIN profit_distributions pd ON mp.id = pd.profit_id
                WHERE mp.month = ?
                GROUP BY mp.id";

        $profit = $this->db->query($sql, [$month])->fetch();

        if (!$profit) {
            throw new Exception("Profit record not found");
        }

        // Get distributions
        $sql = "SELECT 
                    pd.*,
                    i.name as investor_name,
                    i.email as investor_email
                FROM profit_distributions pd
                JOIN investors i ON pd.investor_id = i.id
                WHERE pd.profit_id = ?";
        
        $distributions = $this->db->query($sql, [$profit['id']])->fetchAll();

        // Format data for CSV
        $data = [];

        // Add profit summary
        $data[] = ['Profit Summary - ' . date('F Y', strtotime($month))];
        $data[] = [''];
        $data[] = ['Total Sales', number_format($profit['total_sales'], 2)];
        $data[] = ['Total Costs', number_format($profit['total_costs'], 2)];
        $data[] = ['Total Commissions', number_format($profit['total_commissions'], 2)];
        $data[] = ['Total Expenses', number_format($profit['total_expenses'], 2)];
        $data[] = ['Gross Profit', number_format($profit['gross_profit'], 2)];
        $data[] = ['Net Profit', number_format($profit['net_profit'], 2)];
        $data[] = ['Gross Margin', number_format($profit['gross_margin'], 2) . '%'];
        $data[] = ['Net Margin', number_format($profit['net_margin'], 2) . '%'];
        $data[] = [''];

        // Add distributions
        $data[] = ['Distributions'];
        $data[] = ['Investor', 'Percentage', 'Amount', 'Status', 'Email'];
        foreach ($distributions as $dist) {
            $data[] = [
                $dist['investor_name'],
                $dist['percentage'] . '%',
                number_format($dist['amount'], 2),
                ucfirst($dist['status']),
                $dist['investor_email']
            ];
        }

        return $data;
    }

    public function exportDistributionHistory($startDate = null, $endDate = null) {
        $conditions = ["1=1"];
        $params = [];

        if ($startDate) {
            $conditions[] = "mp.month >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $conditions[] = "mp.month <= ?";
            $params[] = $endDate;
        }

        $whereClause = implode(" AND ", $conditions);

        $sql = "SELECT 
                    mp.month,
                    i.name as investor_name,
                    i.email as investor_email,
                    pd.percentage,
                    pd.amount,
                    pd.status,
                    mp.total_sales,
                    mp.net_profit
                FROM profit_distributions pd
                JOIN monthly_profits mp ON pd.profit_id = mp.id
                JOIN investors i ON pd.investor_id = i.id
                WHERE {$whereClause}
                ORDER BY mp.month DESC, i.name";

        $distributions = $this->db->query($sql, $params)->fetchAll();

        // Format data for CSV
        $data = [];
        $data[] = ['Distribution History Report'];
        $data[] = ['Period: ' . ($startDate ?? 'All time') . ' to ' . ($endDate ?? 'Present')];
        $data[] = [''];
        $data[] = ['Month', 'Investor', 'Email', 'Percentage', 'Amount', 'Status', 'Total Sales', 'Net Profit'];

        foreach ($distributions as $dist) {
            $data[] = [
                date('F Y', strtotime($dist['month'])),
                $dist['investor_name'],
                $dist['investor_email'],
                $dist['percentage'] . '%',
                number_format($dist['amount'], 2),
                ucfirst($dist['status']),
                number_format($dist['total_sales'], 2),
                number_format($dist['net_profit'], 2)
            ];
        }

        return $data;
    }

    public function exportInvestorReport($investorId, $year = null) {
        $year = $year ?? date('Y');

        $sql = "SELECT 
                    i.name as investor_name,
                    i.email as investor_email,
                    mp.month,
                    pd.percentage,
                    pd.amount,
                    pd.status,
                    mp.total_sales,
                    mp.net_profit
                FROM profit_distributions pd
                JOIN monthly_profits mp ON pd.profit_id = mp.id
                JOIN investors i ON pd.investor_id = i.id
                WHERE pd.investor_id = ? AND YEAR(mp.month) = ?
                ORDER BY mp.month DESC";

        $distributions = $this->db->query($sql, [$investorId, $year])->fetchAll();

        if (empty($distributions)) {
            throw new Exception("No distributions found for this investor");
        }

        // Calculate summary
        $totalAmount = array_sum(array_column($distributions, 'amount'));
        $avgPercentage = array_sum(array_column($distributions, 'percentage')) / count($distributions);

        // Format data for CSV
        $data = [];
        $data[] = ['Investor Distribution Report - ' . $distributions[0]['investor_name']];
        $data[] = ['Year: ' . $year];
        $data[] = ['Email: ' . $distributions[0]['investor_email']];
        $data[] = [''];
        $data[] = ['Summary'];
        $data[] = ['Total Distributions', number_format($totalAmount, 2)];
        $data[] = ['Average Percentage', number_format($avgPercentage, 2) . '%'];
        $data[] = [''];
        $data[] = ['Monthly Distributions'];
        $data[] = ['Month', 'Percentage', 'Amount', 'Status', 'Total Sales', 'Net Profit'];

        foreach ($distributions as $dist) {
            $data[] = [
                date('F Y', strtotime($dist['month'])),
                $dist['percentage'] . '%',
                number_format($dist['amount'], 2),
                ucfirst($dist['status']),
                number_format($dist['total_sales'], 2),
                number_format($dist['net_profit'], 2)
            ];
        }

        return $data;
    }
}
