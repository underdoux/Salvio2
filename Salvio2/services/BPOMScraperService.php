<?php
require_once __DIR__ . '/../config/database.php';

class BPOMScraperService {
    private $baseUrl = 'https://cekbpom.pom.go.id/';
    private $searchEndpoint = 'index.php/home/produk/';
    private $detailEndpoint = 'index.php/home/produk/detail/';
    private $conn;
    private $lastRequest = 0;
    private $requestDelay = 2; // Delay between requests in seconds

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function scrapeProducts($page = 1, $limit = 100) {
        try {
            $this->log("Starting BPOM scraping for page $page");
            
            $products = [];
            $url = $this->baseUrl . $this->searchEndpoint . "all/$page/$limit";
            
            $html = $this->makeRequest($url);
            if (!$html) {
                throw new Exception("Failed to fetch BPOM page $page");
            }

            // Parse product list
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // Extract product rows
            $rows = $xpath->query("//table[@class='table']/tbody/tr");
            foreach ($rows as $row) {
                $product = $this->parseProductRow($row, $xpath);
                if ($product) {
                    $products[] = $product;
                    $this->saveProduct($product);
                }
            }

            $this->log("Successfully scraped " . count($products) . " products from page $page");
            return $products;

        } catch (Exception $e) {
            $this->log("Error scraping BPOM page $page: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function scrapeProductDetails($bpomId) {
        try {
            $this->log("Fetching details for BPOM ID: $bpomId");
            
            $url = $this->baseUrl . $this->detailEndpoint . $bpomId;
            $html = $this->makeRequest($url);
            
            if (!$html) {
                throw new Exception("Failed to fetch details for BPOM ID: $bpomId");
            }

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            $details = $this->parseProductDetails($xpath);
            $details['bpom_id'] = $bpomId;

            $this->updateProductDetails($details);
            
            $this->log("Successfully scraped details for BPOM ID: $bpomId");
            return $details;

        } catch (Exception $e) {
            $this->log("Error scraping product details for BPOM ID $bpomId: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    private function parseProductRow($row, $xpath) {
        try {
            $cells = $xpath->query(".//td", $row);
            if ($cells->length < 4) return null;

            $bpomId = trim($cells->item(0)->textContent);
            $name = trim($cells->item(1)->textContent);
            $manufacturer = trim($cells->item(2)->textContent);
            $status = trim($cells->item(3)->textContent);

            return [
                'bpom_id' => $bpomId,
                'name' => $name,
                'manufacturer' => $manufacturer,
                'registration_status' => $status,
                'scraped_at' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            $this->log("Error parsing product row: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }

    private function parseProductDetails($xpath) {
        $details = [];

        // Extract all detail fields
        $rows = $xpath->query("//table[@class='table']//tr");
        foreach ($rows as $row) {
            $cells = $xpath->query(".//td", $row);
            if ($cells->length >= 2) {
                $key = $this->normalizeKey(trim($cells->item(0)->textContent));
                $value = trim($cells->item(1)->textContent);
                $details[$key] = $value;
            }
        }

        // Extract category from classification
        if (isset($details['classification'])) {
            $details['category'] = $this->determineCategory($details['classification']);
        }

        return $details;
    }

    private function determineCategory($classification) {
        $categories = [
            'OBAT BEBAS' => 1,
            'OBAT BEBAS TERBATAS' => 2,
            'OBAT KERAS' => 3,
            'NARKOTIKA' => 4,
            'PSIKOTROPIKA' => 5
        ];

        foreach ($categories as $keyword => $categoryId) {
            if (stripos($classification, $keyword) !== false) {
                return $categoryId;
            }
        }

        return null; // Unknown category
    }

    private function saveProduct($product) {
        $query = "INSERT INTO bpom_products 
                (bpom_id, name, manufacturer, registration_status, scraped_at)
                VALUES (:bpom_id, :name, :manufacturer, :registration_status, :scraped_at)
                ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                manufacturer = VALUES(manufacturer),
                registration_status = VALUES(registration_status),
                scraped_at = VALUES(scraped_at)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($product);
    }

    private function updateProductDetails($details) {
        $query = "UPDATE bpom_products SET
                category_id = :category_id,
                composition = :composition,
                dosage_form = :dosage_form,
                packaging = :packaging,
                registration_number = :registration_number,
                registration_date = :registration_date,
                expiry_date = :expiry_date,
                updated_at = NOW()
                WHERE bpom_id = :bpom_id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            'category_id' => $details['category'] ?? null,
            'composition' => $details['composition'] ?? null,
            'dosage_form' => $details['dosage_form'] ?? null,
            'packaging' => $details['packaging'] ?? null,
            'registration_number' => $details['registration_number'] ?? null,
            'registration_date' => $details['registration_date'] ? date('Y-m-d', strtotime($details['registration_date'])) : null,
            'expiry_date' => $details['expiry_date'] ? date('Y-m-d', strtotime($details['expiry_date'])) : null,
            'bpom_id' => $details['bpom_id']
        ]);
    }

    private function makeRequest($url) {
        // Respect rate limiting
        $now = time();
        $timeSinceLastRequest = $now - $this->lastRequest;
        if ($timeSinceLastRequest < $this->requestDelay) {
            sleep($this->requestDelay - $timeSinceLastRequest);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->lastRequest = time();

        if ($httpCode !== 200) {
            throw new Exception("HTTP request failed with status $httpCode");
        }

        return $response;
    }

    private function normalizeKey($key) {
        // Remove special characters and convert to snake_case
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9\s]/', '', $key);
        $key = str_replace(' ', '_', $key);
        return $key;
    }

    private function log($message, $level = 'INFO') {
        $logFile = __DIR__ . '/../storage/logs/bpom_scraper.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
?>
