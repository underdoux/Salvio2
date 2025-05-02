<?php
require_once __DIR__ . '/../config/database.php';

class ProfitShare {
    private $conn;
    private $table_name = "profit_shares";

    public $id;
    public $distribution_id;
    public $investor_id;
    public $amount;
    public $paid_date;
    public $status;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (distribution_id, investor_id, amount, status) 
            VALUES (:distribution_id, :investor_id, :amount, :status)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':distribution_id', $this->distribution_id);
        $stmt->bindParam(':investor_id', $this->investor_id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readByDistribution($distribution_id) {
        $query = "SELECT ps.*, i.name as investor_name, i.percentage 
                 FROM " . $this->table_name . " ps
                 JOIN investors i ON ps.investor_id = i.id
                 WHERE ps.distribution_id = :distribution_id
                 ORDER BY i.percentage DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':distribution_id', $distribution_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function readByInvestor($investor_id) {
        $query = "SELECT ps.*, pd.period_start, pd.period_end, pd.net_profit 
                 FROM " . $this->table_name . " ps
                 JOIN profit_distributions pd ON ps.distribution_id = pd.id
                 WHERE ps.investor_id = :investor_id
                 ORDER BY pd.period_end DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':investor_id', $investor_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function markAsPaid($id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = 'paid', 
                     paid_date = CURRENT_DATE
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // Check if all shares for this distribution are paid
            $this->checkDistributionCompletion($this->distribution_id);
            return true;
        }
        return false;
    }

    private function checkDistributionCompletion($distribution_id) {
        // Check if all shares for this distribution are paid
        $query = "SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid
                 FROM " . $this->table_name . "
                 WHERE distribution_id = :distribution_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':distribution_id', $distribution_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If all shares are paid, update distribution status
        if ($row['total'] == $row['paid']) {
            $query = "UPDATE profit_distributions 
                     SET status = 'distributed'
                     WHERE id = :distribution_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':distribution_id', $distribution_id);
            $stmt->execute();
        }
    }

    public function getInvestorSummary($investor_id, $year = null) {
        $query = "SELECT 
                    YEAR(pd.period_end) as year,
                    COUNT(ps.id) as total_distributions,
                    SUM(ps.amount) as total_amount,
                    SUM(CASE WHEN ps.status = 'paid' THEN ps.amount ELSE 0 END) as total_paid,
                    MIN(ps.paid_date) as first_payment,
                    MAX(ps.paid_date) as last_payment
                 FROM " . $this->table_name . " ps
                 JOIN profit_distributions pd ON ps.distribution_id = pd.id
                 WHERE ps.investor_id = :investor_id";
        
        if ($year) {
            $query .= " AND YEAR(pd.period_end) = :year";
        }
        
        $query .= " GROUP BY YEAR(pd.period_end)
                   ORDER BY year DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':investor_id', $investor_id);
        if ($year) {
            $stmt->bindParam(':year', $year);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function getPendingPayments() {
        $query = "SELECT ps.*, 
                    i.name as investor_name,
                    pd.period_start,
                    pd.period_end,
                    pd.net_profit
                 FROM " . $this->table_name . " ps
                 JOIN investors i ON ps.investor_id = i.id
                 JOIN profit_distributions pd ON ps.distribution_id = pd.id
                 WHERE ps.status = 'pending'
                 ORDER BY pd.period_end ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function getPaymentHistory($start_date = null, $end_date = null) {
        $query = "SELECT ps.*,
                    i.name as investor_name,
                    i.percentage,
                    pd.period_start,
                    pd.period_end,
                    pd.net_profit
                 FROM " . $this->table_name . " ps
                 JOIN investors i ON ps.investor_id = i.id
                 JOIN profit_distributions pd ON ps.distribution_id = pd.id
                 WHERE ps.status = 'paid'";
        
        if ($start_date) {
            $query .= " AND ps.paid_date >= :start_date";
        }
        if ($end_date) {
            $query .= " AND ps.paid_date <= :end_date";
        }
        
        $query .= " ORDER BY ps.paid_date DESC";
        
        $stmt = $this->conn->prepare($query);
        if ($start_date) {
            $stmt->bindParam(':start_date', $start_date);
        }
        if ($end_date) {
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt;
    }
}
?>
