<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/BPOMScraperService.php';
require_once __DIR__ . '/../services/NotificationService.php';

class BPOMScraperCommand {
    private $scraper;
    private $notificationService;
    private $db;
    private $logId;
    private $startTime;
    private $stats;
    private $verbose;

    public function __construct($verbose = false) {
        $this->scraper = new BPOMScraperService();
        $this->notificationService = new NotificationService();
        $database = new Database();
        $this->db = $database->getConnection();
        $this->verbose = $verbose;
        $this->stats = [
            'total_pages' => 0,
            'total_products' => 0,
            'new_products' => 0,
            'updated_products' => 0,
            'failed_products' => 0
        ];
    }

    public function run() {
        try {
            $this->startTime = time();
            $this->initializeLog();
            $this->log("Starting BPOM scraping process");

            // Get settings
            $settings = $this->getSettings();
            $maxPages = $settings['max_pages'] ?? 100;

            // Scrape products
            $page = 1;
            $hasMore = true;

            while ($hasMore && $page <= $maxPages) {
                $this->log("Processing page $page");
                
                try {
                    $products = $this->scraper->scrapeProducts($page);
                    $this->stats['total_pages']++;
                    $this->stats['total_products'] += count($products);

                    if (empty($products)) {
                        $hasMore = false;
                    } else {
                        // Process product details
                        foreach ($products as $product) {
                            $this->processProduct($product);
                        }
                    }

                    $page++;
                    $this->updateLog();

                } catch (Exception $e) {
                    $this->log("Error processing page $page: " . $e->getMessage(), 'ERROR');
                    if ($page === 1) {
                        throw $e; // Critical error on first page
                    }
                    $hasMore = false;
                }
            }

            // Auto-categorize products
            if ($settings['auto_categorize'] === 'true') {
                $this->categorizeProducts();
            }

            // Match products
            $this->matchProducts($settings['match_threshold']);

            $this->completeLog();
            $this->sendNotification();
            $this->log("BPOM scraping completed successfully");

        } catch (Exception $e) {
            $this->failLog($e->getMessage());
            $this->log("BPOM scraping failed: " . $e->getMessage(), 'ERROR');
            $this->sendErrorNotification($e->getMessage());
            exit(1);
        }
    }

    private function processProduct($product) {
        try {
            // Check if product exists
            $stmt = $this->db->prepare("SELECT id FROM bpom_products WHERE bpom_id = ?");
            $stmt->execute([$product['bpom_id']]);
            $exists = $stmt->fetch();

            if ($exists) {
                $this->stats['updated_products']++;
            } else {
                $this->stats['new_products']++;
            }

            // Get detailed information
            $details = $this->scraper->scrapeProductDetails($product['bpom_id']);
            
            // Update product with details
            if ($details) {
                $this->updateProduct($product['bpom_id'], $details);
            }

        } catch (Exception $e) {
            $this->stats['failed_products']++;
            $this->log("Error processing product {$product['bpom_id']}: " . $e->getMessage(), 'ERROR');
        }
    }

    private function categorizeProducts() {
        $this->log("Auto-categorizing products");

        $query = "UPDATE products p
                 INNER JOIN bpom_product_matches bpm ON p.id = bpm.product_id
                 INNER JOIN bpom_products bp ON bpm.bpom_product_id = bp.id
                 INNER JOIN bpom_category_mappings bcm ON bp.category_id = bcm.bpom_category_id
                 SET p.category_id = bcm.local_category_id
                 WHERE bpm.match_confidence >= 90
                 AND p.category_id IS NULL";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $categorizedCount = $stmt->rowCount();
        $this->log("Auto-categorized $categorizedCount products");
    }

    private function matchProducts($threshold) {
        $this->log("Matching products with local database");

        // Get unmatched products
        $query = "SELECT id, name, manufacturer 
                 FROM products 
                 WHERE id NOT IN (SELECT product_id FROM bpom_product_matches)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $matchCount = 0;
        foreach ($products as $product) {
            $matches = $this->findMatches($product, $threshold);
            if (!empty($matches)) {
                $this->saveMatches($product['id'], $matches);
                $matchCount++;
            }
        }

        $this->log("Matched $matchCount new products");
    }

    private function findMatches($product, $threshold) {
        $query = "SELECT id, name, manufacturer,
                 (
                     MATCH(name, composition) AGAINST (? IN BOOLEAN MODE) * 100
                 ) as confidence
                 FROM bpom_products
                 WHERE MATCH(name, composition) AGAINST (? IN BOOLEAN MODE)
                 AND confidence >= ?
                 ORDER BY confidence DESC
                 LIMIT 5";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $product['name'],
            $product['name'],
            $threshold
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveMatches($productId, $matches) {
        $query = "INSERT INTO bpom_product_matches 
                (product_id, bpom_product_id, match_confidence)
                VALUES (?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        foreach ($matches as $match) {
            $stmt->execute([
                $productId,
                $match['id'],
                $match['confidence']
            ]);
        }
    }

    private function getSettings() {
        $settings = [];
        $query = "SELECT name, value FROM bpom_settings";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['name']] = $row['value'];
        }

        return $settings;
    }

    private function initializeLog() {
        $query = "INSERT INTO bpom_scraping_logs (start_time) VALUES (NOW())";
        $this->db->exec($query);
        $this->logId = $this->db->lastInsertId();
    }

    private function updateLog() {
        $query = "UPDATE bpom_scraping_logs SET
                total_pages = ?,
                total_products = ?,
                new_products = ?,
                updated_products = ?,
                failed_products = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $this->stats['total_pages'],
            $this->stats['total_products'],
            $this->stats['new_products'],
            $this->stats['updated_products'],
            $this->stats['failed_products'],
            $this->logId
        ]);
    }

    private function completeLog() {
        $duration = time() - $this->startTime;
        
        $query = "UPDATE bpom_scraping_logs SET
                status = 'completed',
                end_time = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->logId]);
    }

    private function failLog($error) {
        $query = "UPDATE bpom_scraping_logs SET
                status = 'failed',
                end_time = NOW(),
                error_message = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$error, $this->logId]);
    }

    private function sendNotification() {
        $duration = time() - $this->startTime;
        
        $message = "BPOM Scraping Completed\n\n" .
                  "Duration: " . gmdate("H:i:s", $duration) . "\n" .
                  "Total Pages: {$this->stats['total_pages']}\n" .
                  "Total Products: {$this->stats['total_products']}\n" .
                  "New Products: {$this->stats['new_products']}\n" .
                  "Updated Products: {$this->stats['updated_products']}\n" .
                  "Failed Products: {$this->stats['failed_products']}";

        $this->notificationService->sendAdminNotification(
            'bpom_scraping_complete',
            ['message' => $message]
        );
    }

    private function sendErrorNotification($error) {
        $this->notificationService->sendAdminNotification(
            'bpom_scraping_failed',
            ['message' => "BPOM Scraping Failed: $error"]
        );
    }

    private function log($message, $level = 'INFO') {
        $datetime = date('Y-m-d H:i:s');
        $logMessage = "[$datetime] [$level] $message";
        
        if ($this->verbose) {
            echo $logMessage . PHP_EOL;
        }
        
        error_log($logMessage);
    }
}

// Parse command line arguments
$options = getopt('', ['verbose::']);
$verbose = isset($options['verbose']);

// Run scraper
$scraper = new BPOMScraperCommand($verbose);
$scraper->run();
?>
