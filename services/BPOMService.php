<?php
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/AuditLog.php';

class BPOMService {
    private $bpomData = [];
    private $cacheFile;
    private $cacheExpiry = 86400; // 24 hours in seconds
    private $baseUrl = 'https://cekbpom.pom.go.id/';

    public function __construct() {
        $this->cacheFile = __DIR__ . '/../data/bpom_cache.json';
        $this->loadBPOMData();
    }

    private function loadBPOMData() {
        if (file_exists($this->cacheFile)) {
            $cacheContent = file_get_contents($this->cacheFile);
            $cache = json_decode($cacheContent, true);
            
            // Check if cache is still valid
            if ($cache && isset($cache['timestamp']) && 
                (time() - $cache['timestamp'] < $this->cacheExpiry)) {
                $this->bpomData = $cache['data'];
                return;
            }
        }
        
        // Cache doesn't exist or is expired, load from local JSON
        $localFile = __DIR__ . '/../data/bpom_data.json';
        if (file_exists($localFile)) {
            $json = file_get_contents($localFile);
            $this->bpomData = json_decode($json, true) ?? [];
            $this->updateCache();
        }
    }

    private function updateCache() {
        $cacheData = [
            'timestamp' => time(),
            'data' => $this->bpomData
        ];
        
        file_put_contents($this->cacheFile, json_encode($cacheData));
    }

    public function matchProductCategory($productName) {
        // First try exact match
        foreach ($this->bpomData as $entry) {
            if (strtolower($entry['product_name']) === strtolower($productName)) {
                return $entry['category_code'];
            }
        }

        // Then try partial match
        foreach ($this->bpomData as $entry) {
            if (stripos($productName, $entry['product_name']) !== false ||
                stripos($entry['product_name'], $productName) !== false) {
                return $entry['category_code'];
            }
        }

        // If no match found and auto-scraping is enabled, try scraping
        $setting = new Setting();
        if ($setting->get('bpom', 'auto_scrape_enabled', 'false') === 'true') {
            return $this->scrapeProductData($productName);
        }

        return null;
    }

    public function scrapeProductData($productName) {
        try {
            // Initialize cURL
            $ch = curl_init();
            
            // Set search URL
            $searchUrl = $this->baseUrl . 'index.php/home/produk/q/' . urlencode($productName);
            
            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $searchUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            // Execute search request
            $response = curl_exec($ch);
            
            if ($response === false) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }

            // Parse the response HTML
            $productData = $this->parseSearchResults($response);
            
            if ($productData) {
                // Add to local data
                $this->bpomData[] = $productData;
                $this->updateLocalData();
                
                // Log the scraping
                $auditLog = new AuditLog();
                $auditLog->log(
                    null,
                    'bpom_scrape',
                    'bpom_data',
                    null,
                    null,
                    $productData
                );
                
                return $productData['category_code'];
            }

            curl_close($ch);
            return null;

        } catch (Exception $e) {
            // Log error
            error_log("BPOM Scraping Error: " . $e->getMessage());
            return null;
        }
    }

    private function parseSearchResults($html) {
        // Basic HTML parsing using regex
        // In a production environment, consider using a proper HTML parser like DOMDocument
        
        // Extract product information
        preg_match('/<td[^>]*>Nomor Registrasi<\/td>\s*<td[^>]*>([^<]+)<\/td>/', $html, $regMatch);
        preg_match('/<td[^>]*>Nama Produk<\/td>\s*<td[^>]*>([^<]+)<\/td>/', $html, $nameMatch);
        preg_match('/<td[^>]*>Kategori<\/td>\s*<td[^>]*>([^<]+)<\/td>/', $html, $categoryMatch);
        
        if ($regMatch && $nameMatch && $categoryMatch) {
            return [
                'registration_no' => trim($regMatch[1]),
                'product_name' => trim($nameMatch[1]),
                'category_name' => trim($categoryMatch[1]),
                'category_code' => $this->mapCategoryToCode(trim($categoryMatch[1])),
                'scraped_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return null;
    }

    private function mapCategoryToCode($categoryName) {
        // This should be replaced with actual BPOM category codes
        $categoryMap = [
            'OBAT' => 'MED',
            'SUPLEMEN' => 'SUP',
            'KOSMETIK' => 'COS',
            'PANGAN' => 'FOOD'
            // Add more mappings as needed
        ];
        
        foreach ($categoryMap as $key => $code) {
            if (stripos($categoryName, $key) !== false) {
                return $code;
            }
        }
        
        return 'OTH'; // Other/Unknown
    }

    private function updateLocalData() {
        $localFile = __DIR__ . '/../data/bpom_data.json';
        file_put_contents($localFile, json_encode($this->bpomData));
        $this->updateCache();
    }

    public function importBPOMDataFromCSV($csvFilePath) {
        if (!file_exists($csvFilePath)) {
            return false;
        }

        try {
            $handle = fopen($csvFilePath, 'r');
            $header = fgetcsv($handle);
            $data = [];
            $count = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $rowData = array_combine($header, $row);
                
                // Validate and clean data
                if (isset($rowData['product_name']) && isset($rowData['category_code'])) {
                    $data[] = [
                        'product_name' => trim($rowData['product_name']),
                        'category_code' => trim($rowData['category_code']),
                        'registration_no' => trim($rowData['registration_no'] ?? ''),
                        'category_name' => trim($rowData['category_name'] ?? ''),
                        'imported_at' => date('Y-m-d H:i:s')
                    ];
                    $count++;
                }
            }
            
            fclose($handle);

            // Merge with existing data, avoiding duplicates
            $this->bpomData = array_merge(
                $this->bpomData,
                array_filter($data, function($item) {
                    return !$this->isDuplicate($item);
                })
            );

            $this->updateLocalData();

            // Log the import
            $auditLog = new AuditLog();
            $auditLog->log(
                $_SESSION['user_id'] ?? null,
                'bpom_import',
                'bpom_data',
                null,
                null,
                ['imported_count' => $count]
            );

            return $count;

        } catch (Exception $e) {
            error_log("BPOM Import Error: " . $e->getMessage());
            return false;
        }
    }

    private function isDuplicate($item) {
        foreach ($this->bpomData as $existing) {
            if ($existing['product_name'] === $item['product_name'] ||
                ($existing['registration_no'] && 
                 $existing['registration_no'] === $item['registration_no'])) {
                return true;
            }
        }
        return false;
    }

    public function searchProducts($term) {
        $results = [];
        foreach ($this->bpomData as $item) {
            if (stripos($item['product_name'], $term) !== false ||
                stripos($item['registration_no'], $term) !== false) {
                $results[] = $item;
            }
        }
        return $results;
    }
}
?>
