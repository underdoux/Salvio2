<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../services/ApiService.php';
require_once __DIR__ . '/../services/SettingsService.php';

class ApiController extends Controller {
    private $apiService;
    private $settingsService;

    public function __construct() {
        parent::__construct();
        $this->apiService = new ApiService($this->currentUser);
        $this->settingsService = new SettingsService($this->currentUser);
    }

    public function index() {
        $this->requirePermission('manage_api_keys');

        $data = [
            'pageTitle' => 'API Management',
            'apiKeys' => $this->apiService->listApiKeys(),
            'webhooks' => $this->apiService->listWebhooks(),
            'settings' => $this->settingsService->getAll('api')
        ];

        require_once __DIR__ . '/../views/api/index.php';
    }

    public function generateKey() {
        $this->requirePermission('manage_api_keys');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['name'])) {
                throw new Exception('Invalid request data');
            }

            $expiresAt = null;
            if (!empty($data['expires_days'])) {
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$data['expires_days']} days"));
            }

            $result = $this->apiService->generateApiKey(
                $data['name'],
                $data['scopes'] ?? [],
                $expiresAt
            );

            $this->respondWithJson([
                'message' => 'API key generated successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function revokeKey() {
        $this->requirePermission('manage_api_keys');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['key_id'])) {
                throw new Exception('Invalid request data');
            }

            if ($this->apiService->revokeApiKey($data['key_id'])) {
                $this->respondWithJson([
                    'message' => 'API key revoked successfully'
                ]);
            } else {
                throw new Exception('Failed to revoke API key');
            }

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function createWebhook() {
        $this->requirePermission('manage_webhooks');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['name']) || !isset($data['url']) || !isset($data['events'])) {
                throw new Exception('Invalid request data');
            }

            $result = $this->apiService->createWebhook($data);

            $this->respondWithJson([
                'message' => 'Webhook created successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function getLogs() {
        $this->requirePermission('view_api_logs');

        try {
            $filters = [
                'api_key_id' => $_GET['key_id'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
                'response_code' => $_GET['response_code'] ?? null,
                'limit' => min((int)($_GET['limit'] ?? 100), 1000)
            ];

            $logs = $this->apiService->getApiLogs($filters);
            $this->respondWithJson($logs);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function getErrorLogs() {
        $this->requirePermission('view_api_logs');

        try {
            $filters = [
                'api_key_id' => $_GET['key_id'] ?? null,
                'error_code' => $_GET['error_code'] ?? null,
                'limit' => min((int)($_GET['limit'] ?? 100), 1000)
            ];

            $logs = $this->apiService->getErrorLogs($filters);
            $this->respondWithJson($logs);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function documentation() {
        $this->requirePermission('view_api_docs');

        $query = "SELECT * FROM api_documentation 
                 WHERE deprecated = 0 
                 ORDER BY endpoint, method";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $endpoints = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'pageTitle' => 'API Documentation',
            'endpoints' => $endpoints
        ];

        require_once __DIR__ . '/../views/api/documentation.php';
    }

    public function updateDocumentation() {
        $this->requirePermission('edit_api_docs');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['endpoint_id'])) {
                throw new Exception('Invalid request data');
            }

            $query = "UPDATE api_documentation SET
                    summary = ?,
                    description = ?,
                    parameters = ?,
                    request_body = ?,
                    responses = ?,
                    scopes = ?,
                    deprecated = ?,
                    updated_at = NOW()
                    WHERE id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['summary'],
                $data['description'],
                json_encode($data['parameters']),
                json_encode($data['request_body']),
                json_encode($data['responses']),
                json_encode($data['scopes']),
                $data['deprecated'] ? 1 : 0,
                $data['endpoint_id']
            ]);

            $this->respondWithJson([
                'message' => 'Documentation updated successfully'
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function testWebhook() {
        $this->requirePermission('manage_webhooks');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['webhook_id'])) {
                throw new Exception('Invalid request data');
            }

            // Get webhook details
            $query = "SELECT * FROM api_webhooks WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$data['webhook_id'], $this->currentUser->id]);
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$webhook) {
                throw new Exception('Webhook not found');
            }

            // Send test event
            $this->apiService->triggerWebhook('test', [
                'message' => 'This is a test webhook event',
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $this->respondWithJson([
                'message' => 'Test webhook triggered successfully'
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function exportLogs() {
        $this->requirePermission('view_api_logs');

        try {
            $filters = [
                'api_key_id' => $_GET['key_id'] ?? null,
                'start_date' => $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')),
                'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
                'limit' => 10000
            ];

            $logs = $this->apiService->getApiLogs($filters);
            
            // Generate CSV
            $filename = 'api_logs_' . date('Y-m-d_His') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Timestamp',
                'API Key',
                'User',
                'Endpoint',
                'Method',
                'Response Code',
                'Response Time',
                'IP Address'
            ]);

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['created_at'],
                    $log['key_name'],
                    $log['username'],
                    $log['endpoint'],
                    $log['method'],
                    $log['response_code'],
                    $log['response_time'],
                    $log['ip_address']
                ]);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }
}
?>
