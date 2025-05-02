<?php
class BPOMService {
    private $bpomData = [];

    public function __construct() {
        // Load BPOM data from a local JSON or CSV file
        $this->loadBPOMData();
    }

    private function loadBPOMData() {
        $filePath = __DIR__ . '/../data/bpom_data.json';
        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            $this->bpomData = json_decode($json, true);
        } else {
            $this->bpomData = [];
        }
    }

    public function matchProductCategory($productName) {
        // Simple matching logic: find BPOM entry with product name containing the search term
        foreach ($this->bpomData as $entry) {
            if (stripos($productName, $entry['product_name']) !== false) {
                return $entry['category_code'];
            }
        }
        return null;
    }

    public function importBPOMDataFromCSV($csvFilePath) {
        if (!file_exists($csvFilePath)) {
            return false;
        }
        $handle = fopen($csvFilePath, 'r');
        $header = fgetcsv($handle);
        $data = [];
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($header, $row);
        }
        fclose($handle);
        $jsonFilePath = __DIR__ . '/../data/bpom_data.json';
        file_put_contents($jsonFilePath, json_encode($data));
        $this->bpomData = $data;
        return true;
    }
}
?>
