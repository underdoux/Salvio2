<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../services/InsightService.php';

class InsightController extends Controller {
    private $insightService;

    public function __construct() {
        parent::__construct();
        $this->insightService = new InsightService();
    }

    public function index() {
        $this->requirePermission('view_insights');
        
        $data = [
            'pageTitle' => 'Business Insights',
            'insights' => $this->insightService->getDashboardInsights()
        ];

        require_once __DIR__ . '/../views/insight/index.php';
    }

    public function getSalesInsights() {
        $this->requirePermission('view_insights');

        try {
            $params = $this->validateParams($_GET);
            $insights = $this->insightService->getSalesInsights($params);
            $this->respondWithJson($insights);
        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function getProductInsights() {
        $this->requirePermission('view_insights');

        try {
            $params = $this->validateParams($_GET);
            $insights = $this->insightService->getProductInsights($params);
            $this->respondWithJson($insights);
        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function getCustomerInsights() {
        $this->requirePermission('view_insights');

        try {
            $params = $this->validateParams($_GET);
            $insights = $this->insightService->getCustomerInsights($params);
            $this->respondWithJson($insights);
        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function getPerformanceMetrics() {
        $this->requirePermission('view_insights');

        try {
            $params = $this->validateParams($_GET);
            $metrics = $this->insightService->getPerformanceMetrics($params);
            $this->respondWithJson($metrics);
        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function exportInsights() {
        $this->requirePermission('export_insights');

        try {
            $type = $_GET['type'] ?? null;
            if (!$type) {
                throw new Exception('Insight type is required');
            }

            $params = $this->validateParams($_GET);
            $result = $this->insightService->exportInsights($type, $params);

            // Stream the file to browser
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Content-Length: ' . filesize($result['filepath']));
            header('Cache-Control: max-age=0');

            readfile($result['filepath']);
            unlink($result['filepath']); // Clean up the temporary file
            exit;

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function saveInsightSettings() {
        $this->requirePermission('manage_insight_settings');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $settings = $this->validateSettings($data);
            $this->updateSettings($settings);

            $this->respondWithJson([
                'message' => 'Insight settings updated successfully',
                'settings' => $settings
            ]);

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

        // Comparison period
        if (!empty($params['compare_to'])) {
            $validated['compare_to'] = filter_var($params['compare_to'], FILTER_SANITIZE_STRING);
        }

        // Filters
        if (!empty($params['category_id'])) {
            $validated['category_id'] = filter_var($params['category_id'], FILTER_VALIDATE_INT);
        }
        if (!empty($params['user_id'])) {
            $validated['user_id'] = filter_var($params['user_id'], FILTER_VALIDATE_INT);
        }
        if (isset($params['include_inactive'])) {
            $validated['include_inactive'] = filter_var($params['include_inactive'], FILTER_VALIDATE_BOOLEAN);
        }

        // Grouping
        if (!empty($params['group_by'])) {
            $validated['group_by'] = filter_var($params['group_by'], FILTER_SANITIZE_STRING);
        }

        return $validated;
    }

    private function validateSettings($data) {
        $settings = [];

        // Validate refresh interval
        if (isset($data['refresh_interval'])) {
            $interval = filter_var($data['refresh_interval'], FILTER_VALIDATE_INT);
            if ($interval < 300 || $interval > 3600) { // Between 5 minutes and 1 hour
                throw new Exception('Invalid refresh interval');
            }
            $settings['refresh_interval'] = $interval;
        }

        // Validate default date range
        if (!empty($data['default_date_range'])) {
            if (!in_array($data['default_date_range'], ['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'last_month'])) {
                throw new Exception('Invalid default date range');
            }
            $settings['default_date_range'] = $data['default_date_range'];
        }

        // Validate KPI settings
        if (isset($data['kpi_targets'])) {
            $settings['kpi_targets'] = [];
            foreach ($data['kpi_targets'] as $kpi => $target) {
                $value = filter_var($target, FILTER_VALIDATE_FLOAT);
                if ($value === false || $value < 0) {
                    throw new Exception("Invalid target value for $kpi");
                }
                $settings['kpi_targets'][$kpi] = $value;
            }
        }

        // Validate alert thresholds
        if (isset($data['alert_thresholds'])) {
            $settings['alert_thresholds'] = [];
            foreach ($data['alert_thresholds'] as $metric => $threshold) {
                $value = filter_var($threshold, FILTER_VALIDATE_FLOAT);
                if ($value === false) {
                    throw new Exception("Invalid threshold value for $metric");
                }
                $settings['alert_thresholds'][$metric] = $value;
            }
        }

        return $settings;
    }

    private function updateSettings($settings) {
        foreach ($settings as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            $query = "INSERT INTO settings (category, name, value) 
                     VALUES ('insights', :name, :value)
                     ON DUPLICATE KEY UPDATE value = :value";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $key);
            $stmt->bindParam(':value', $value);
            $stmt->execute();
        }
    }
}
?>
