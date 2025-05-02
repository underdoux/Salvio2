<?php

class Investor extends BaseModel {
    protected $table = 'investors';

    public function getAll($includeInactive = false) {
        $sql = "SELECT * FROM {$this->table}";
        if (!$includeInactive) {
            $sql .= " WHERE status = TRUE";
        }
        $sql .= " ORDER BY name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Calculate percentage based on total capital
            $totalCapital = $this->getTotalCapital();
            $newTotalCapital = $totalCapital + $data['capital_amount'];
            
            // Update existing investors' percentages
            if ($totalCapital > 0) {
                $sql = "UPDATE {$this->table} 
                        SET percentage = (capital_amount / ?) * 100
                        WHERE status = TRUE";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$newTotalCapital]);
            }

            // Calculate new investor's percentage
            $percentage = ($data['capital_amount'] / $newTotalCapital) * 100;

            // Insert new investor
            $sql = "INSERT INTO {$this->table} 
                    (name, capital_amount, percentage) 
                    VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['capital_amount'],
                $percentage
            ]);
            
            $investorId = $this->db->lastInsertId();

            // Record in capital history
            $sql = "INSERT INTO capital_history 
                    (investor_id, amount, type, date, notes) 
                    VALUES (?, ?, 'investment', CURDATE(), ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $investorId,
                $data['capital_amount'],
                $data['notes'] ?? 'Initial investment'
            ]);

            $this->db->commit();
            return $investorId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateCapital($id, $data) {
        try {
            $this->db->beginTransaction();

            $investor = $this->find($id);
            if (!$investor) {
                throw new Exception("Investor not found");
            }

            $oldCapital = $investor['capital_amount'];
            $newCapital = $oldCapital;

            // Process investment/withdrawal
            if ($data['type'] === 'investment') {
                $newCapital += $data['amount'];
            } else {
                if ($oldCapital < $data['amount']) {
                    throw new Exception("Withdrawal amount exceeds current capital");
                }
                $newCapital -= $data['amount'];
            }

            // Update investor's capital
            $sql = "UPDATE {$this->table} 
                    SET capital_amount = ? 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newCapital, $id]);

            // Record in capital history
            $sql = "INSERT INTO capital_history 
                    (investor_id, amount, type, date, notes) 
                    VALUES (?, ?, ?, CURDATE(), ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $id,
                $data['amount'],
                $data['type'],
                $data['notes'] ?? null
            ]);

            // Recalculate all percentages
            $this->recalculatePercentages();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getCapitalHistory($investorId) {
        $sql = "SELECT * FROM capital_history 
                WHERE investor_id = ? 
                ORDER BY date DESC, created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$investorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTotalCapital() {
        $sql = "SELECT COALESCE(SUM(capital_amount), 0) as total 
                FROM {$this->table} 
                WHERE status = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function recalculatePercentages() {
        $totalCapital = $this->getTotalCapital();
        if ($totalCapital > 0) {
            $sql = "UPDATE {$this->table} 
                    SET percentage = (capital_amount / ?) * 100
                    WHERE status = TRUE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$totalCapital]);
        }
    }
}
