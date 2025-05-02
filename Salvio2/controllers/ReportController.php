<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../services/ReportGenerator.php';

class ReportController extends Controller {
    private $reportGenerator;

    public function __construct() {
        parent::__construct();
        $this->reportGenerator = new ReportGenerator();
    }

    public function index() {
        $this->requirePermission('view_reports');
        
        $data = [
            'pageTitle' => 'Reports',
            'reportTypes' => [
                [
                    'id' => 'sales',
                    'name' => 'Sales Report',
                    'description' => 'Detailed analysis of sales performance, revenue, and trends',
                    'icon' => 'fa-chart-line'
                ],
                [
                    'id' => 'inventory',
                    'name' => 'Inventory Report',
                    'description' => 'Stock levels, movements, and product performance metrics',
                    'icon' => 'fa-box'
                ],
                [
                    'id' => 'commissions',
                    'name' => 'Commission Report',
                    'description' => 'Sales commissions, performance, and payment status',
                    'icon' => 'fa-percentage'
                ],
                [
                    'id' => 'profits',
                    'name' => 'Profit Report',
                    'description' => 'Profit analysis, distributions, and investor shares',
                    'icon' => 'fa-money-bill-wave'
                ]
            ]
        ];

        require_once __DIR__ . '/../views/report/index.php';
    }

    public function generate() {
        $this->requirePermission('generate_reports');

        try {
            $type = $_GET['type'] ?? null;
            if (!$type) {
                throw new Exception('Report type is required');
            }

            // Validate and sanitize parameters
            $params = $this->validateParams($_GET);

            // Generate report
            $result = $this->reportGenerator->generateReport($type, $params);

            // Stream the file to browser
            $this->streamReport($result['filepath'], $result['filename']);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function preview() {
        $this->requirePermission('generate_reports');

        try {
            $type = $_GET['type'] ?? null;
            if (!$type) {
                throw new Exception('Report type is required');
            }

            // Validate and sanitize parameters
            $params = $this->validateParams($_GET);

            // Generate report preview (summary only)
            $result = $this->reportGenerator->generateReport($type, array_merge($params, ['preview' => true]));

            // Return summary data
            $this->respondWithJson($result['summary']);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function schedule() {
        $this->requirePermission('schedule_reports');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate schedule data
            $this->validateSchedule($data);

            // Save schedule
            $schedule = $this->saveSchedule($data);

            $this->respondWithJson([
                'message' => 'Report schedule saved successfully',
                'schedule' => $schedule
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function getSchedules() {
        $this->requirePermission('view_report_schedules');

        try {
            $schedules = $this->getReportSchedules();
            $this->respondWithJson($schedules);
        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    private function validateParams($params) {
        $validated = [];

        // Date range
        if (!empty($params['start_date'])) {
            $validated['start_date'] = date('Y-m-d', strtotime($params['start_date']));
        }
        if (!empty($params['end_date'])) {
            $validated['end_date'] = date('Y-m-d', strtotime($params['end_date']));
        }

        // Status filters
        if (!empty($params['status'])) {
            $validated['status'] = filter_var($params['status'], FILTER_SANITIZE_STRING);
        }
        if (!empty($params['payment_status'])) {
            $validated['payment_status'] = filter_var($params['payment_status'], FILTER_SANITIZE_STRING);
        }

        // User/Category filters
        if (!empty($params['user_id'])) {
            $validated['user_id'] = filter_var($params['user_id'], FILTER_VALIDATE_INT);
        }
        if (!empty($params['category_id'])) {
            $validated['category_id'] = filter_var($params['category_id'], FILTER_VALIDATE_INT);
        }

        // Special filters
        if (isset($params['low_stock'])) {
            $validated['low_stock'] = filter_var($params['low_stock'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($params['by_order'])) {
            $validated['by_order'] = filter_var($params['by_order'], FILTER_VALIDATE_BOOLEAN);
        }

        return $validated;
    }

    private function validateSchedule($data) {
        if (empty($data['type'])) {
            throw new Exception('Report type is required');
        }

        if (empty($data['frequency'])) {
            throw new Exception('Schedule frequency is required');
        }

        if (!in_array($data['frequency'], ['daily', 'weekly', 'monthly'])) {
            throw new Exception('Invalid schedule frequency');
        }

        if (!empty($data['recipients'])) {
            foreach ($data['recipients'] as $recipient) {
                if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid recipient email: ' . $recipient);
                }
            }
        }

        return true;
    }

    private function saveSchedule($data) {
        $query = "INSERT INTO report_schedules 
                (type, frequency, params, recipients, created_by) 
                VALUES (:type, :frequency, :params, :recipients, :created_by)";

        $stmt = $this->db->prepare($query);
        
        $params = json_encode($data['params'] ?? []);
        $recipients = json_encode($data['recipients'] ?? []);
        
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':frequency', $data['frequency']);
        $stmt->bindParam(':params', $params);
        $stmt->bindParam(':recipients', $recipients);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);

        $stmt->execute();
        
        return $this->getScheduleById($this->db->lastInsertId());
    }

    private function getReportSchedules() {
        $query = "SELECT 
                    rs.*,
                    u.name as created_by_name
                FROM report_schedules rs
                LEFT JOIN users u ON rs.created_by = u.id
                ORDER BY rs.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getScheduleById($id) {
        $query = "SELECT 
                    rs.*,
                    u.name as created_by_name
                FROM report_schedules rs
                LEFT JOIN users u ON rs.created_by = u.id
                WHERE rs.id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function streamReport($filepath, $filename) {
        if (!file_exists($filepath)) {
            throw new Exception('Report file not found');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: max-age=0');

        readfile($filepath);
        unlink($filepath); // Clean up the temporary file
        exit;
    }
}
?>
