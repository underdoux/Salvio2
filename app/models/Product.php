<?php

class Product extends BaseModel {
    private $table = 'products';
    private $fillable = [
        'name', 'sku', 'bpom_id', 'category_id', 'description',
        'purchase_price', 'selling_price', 'stock_type', 'current_stock',
        'min_stock', 'is_active'
    ];

    public function __construct() {
        parent::__construct();
    }

    public function getAll($filters = [], $page = 1, $limit = 10) {
        try {
            $sql = "SELECT p.*, c.name as category_name, 
                   COALESCE(b.category_name, 'Uncategorized') as bpom_category 
                   FROM {$this->table} p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   LEFT JOIN bpom_references b ON p.bpom_id = b.bpom_id 
                   WHERE 1=1";
            
            $params = [];

            if (!empty($filters['search'])) {
                $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.bpom_id LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            if (isset($filters['category_id'])) {
                $sql .= " AND p.category_id = ?";
                $params[] = $filters['category_id'];
            }

            if (isset($filters['stock_type'])) {
                $sql .= " AND p.stock_type = ?";
                $params[] = $filters['stock_type'];
            }

            if (isset($filters['is_active'])) {
                $sql .= " AND p.is_active = ?";
                $params[] = $filters['is_active'];
            }

            // Add pagination
            $offset = ($page - 1) * $limit;
            $sql .= " ORDER BY p.name ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching products: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function getById($id) {
        try {
            $sql = "SELECT p.*, c.name as category_name,
                   b.category_name as bpom_category,
                   b.registration_date, b.expiry_date,
                   b.manufacturer, b.composition
                   FROM {$this->table} p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN bpom_references b ON p.bpom_id = b.bpom_id
                   WHERE p.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            Logger::log("Error fetching product {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Validate required fields
            $this->validateProduct($data);

            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSKU($data['name']);
            }

            // Prepare SQL
            $fields = array_intersect_key($data, array_flip($this->fillable));
            $columns = implode(', ', array_keys($fields));
            $values = implode(', ', array_fill(0, count($fields), '?'));
            
            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($fields));
            $productId = $this->db->lastInsertId();

            // Record initial stock if provided
            if (isset($data['initial_stock']) && $data['initial_stock'] > 0) {
                $this->recordStockMovement($productId, 'in', $data['initial_stock'], 'initial_stock');
            }

            // Record price history
            $this->recordPriceHistory($productId, $data['purchase_price'], $data['selling_price'], 'Initial price');

            $this->db->commit();
            return $productId;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error creating product: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function update($id, $data) {
        try {
            $this->db->beginTransaction();

            // Get current product data
            $currentProduct = $this->getById($id);
            if (!$currentProduct) {
                throw new Exception("Product not found");
            }

            // Validate required fields
            $this->validateProduct($data, true);

            // Prepare SQL
            $fields = array_intersect_key($data, array_flip($this->fillable));
            $updates = [];
            foreach ($fields as $key => $value) {
                $updates[] = "{$key} = ?";
            }
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([...array_values($fields), $id]);

            // Record price history if prices changed
            if (isset($data['purchase_price']) && isset($data['selling_price']) &&
                ($data['purchase_price'] != $currentProduct['purchase_price'] ||
                 $data['selling_price'] != $currentProduct['selling_price'])) {
                $this->recordPriceHistory(
                    $id,
                    $data['purchase_price'],
                    $data['selling_price'],
                    $data['price_change_reason'] ?? 'Price update'
                );
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error updating product {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function updateStock($id, $quantity, $type = 'adjustment', $reference = null) {
        try {
            $this->db->beginTransaction();

            // Get current stock
            $product = $this->getById($id);
            if (!$product) {
                throw new Exception("Product not found");
            }

            // Calculate new stock
            $newStock = $product['current_stock'] + $quantity;
            if ($newStock < 0) {
                throw new Exception("Insufficient stock");
            }

            // Update stock
            $sql = "UPDATE {$this->table} SET current_stock = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newStock, $id]);

            // Record movement
            $this->recordStockMovement($id, $quantity > 0 ? 'in' : 'out', abs($quantity), $type, $reference);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error updating stock for product {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    private function validateProduct($data, $isUpdate = false) {
        $required = ['name', 'purchase_price', 'selling_price', 'stock_type'];
        if (!$isUpdate) {
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }
        }

        if (isset($data['purchase_price']) && isset($data['selling_price'])) {
            if ($data['purchase_price'] >= $data['selling_price']) {
                throw new Exception("Selling price must be greater than purchase price");
            }
        }

        return true;
    }

    private function generateSKU($name) {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));
        $timestamp = date('ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        return "{$prefix}{$timestamp}{$random}";
    }

    private function recordStockMovement($productId, $type, $quantity, $referenceType, $referenceId = null) {
        $sql = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, created_by)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $productId,
            $type,
            $quantity,
            $referenceType,
            $referenceId,
            $_SESSION['user_id'] ?? null
        ]);
    }

    private function recordPriceHistory($productId, $purchasePrice, $sellingPrice, $reason) {
        $sql = "INSERT INTO product_price_history (product_id, purchase_price, selling_price, change_reason, created_by)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $productId,
            $purchasePrice,
            $sellingPrice,
            $reason,
            $_SESSION['user_id'] ?? null
        ]);
    }

    public function syncBPOMData($bpomId) {
        try {
            // This would typically call an external API or service
            // For now, we'll just update the reference in our database
            $sql = "UPDATE {$this->table} p
                   SET p.category_id = (
                       SELECT c.id FROM categories c
                       INNER JOIN bpom_references b ON b.category_name = c.name
                       WHERE b.bpom_id = ?
                       LIMIT 1
                   )
                   WHERE p.bpom_id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$bpomId, $bpomId]);
        } catch (Exception $e) {
            Logger::log("Error syncing BPOM data for ID {$bpomId}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
}
