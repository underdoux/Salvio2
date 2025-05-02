<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Commission.php';
require_once __DIR__ . '/../models/CommissionRule.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../services/NotificationService.php';

class CommissionController extends Controller {
    private $commission;
    private $commissionRule;
    private $order;
    private $notification;

    protected $requiredPermissions = [
        'calculateOrderCommission' => 'manage_commissions',
        'updateCommissionRule' => 'manage_commission_rules',
        'approveCommission' => 'approve_commissions',
        'getCommissionReport' => 'view_commission_reports',
        'getUserCommissions' => 'view_commissions'
    ];

    public function __construct() {
        parent::__construct();
        $this->commission = new Commission();
        $this->commissionRule = new CommissionRule();
        $this->order = new Order();
        $this->notification = new NotificationService();
    }

    public function calculateOrderCommission() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('manage_commissions');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['order_id']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            $order_id = $_POST['order_id'];
            $order = $this->order->readOne($order_id);

            if (!$order) {
                throw new Exception('Order not found');
            }

            // Calculate commission
            $commission_amount = $this->commission->calculateOrderCommissions($order_id);

            // Create commission record
            $this->commission->user_id = $order['user_id'];
            $this->commission->order_id = $order_id;
            $this->commission->amount = $commission_amount;
            $this->commission->status = 'pending';

            // Get applicable commission rule
            $rule = $this->commissionRule->getApplicableRule(null, null);
            $this->commission->rule_id = $rule['id'];

            if (!$this->commission->create()) {
                throw new Exception('Failed to create commission record');
            }

            // Log commission calculation
            $this->logAction(
                'commission_calculated',
                'commissions',
                $this->commission->id,
                null,
                [
                    'order_id' => $order_id,
                    'amount' => $commission_amount,
                    'rule_id' => $rule['id']
                ]
            );

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Commission calculated successfully',
                'commission' => [
                    'id' => $this->commission->id,
                    'amount' => $this->formatCurrency($commission_amount),
                    'status' => 'pending'
                ]
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function updateCommissionRule() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('manage_commission_rules');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['type', 'rate']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            $this->commissionRule->type = $_POST['type'];
            $this->commissionRule->reference_id = $_POST['reference_id'] ?? null;
            $this->commissionRule->rate = $_POST['rate'];
            $this->commissionRule->min_amount = $_POST['min_amount'] ?? null;
            $this->commissionRule->max_amount = $_POST['max_amount'] ?? null;

            if (isset($_POST['id'])) {
                $this->commissionRule->id = $_POST['id'];
                $success = $this->commissionRule->update();
            } else {
                $success = $this->commissionRule->create();
            }

            if (!$success) {
                throw new Exception('Failed to update commission rule');
            }

            // Log rule update
            $this->logAction(
                isset($_POST['id']) ? 'rule_updated' : 'rule_created',
                'commission_rules',
                $this->commissionRule->id,
                null,
                [
                    'type' => $this->commissionRule->type,
                    'rate' => $this->commissionRule->rate,
                    'min_amount' => $this->commissionRule->min_amount,
                    'max_amount' => $this->commissionRule->max_amount
                ]
            );

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Commission rule updated successfully',
                'rule_id' => $this->commissionRule->id
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function approveCommission() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('approve_commissions');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['commission_id']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            if (!$this->commission->updateStatus($_POST['commission_id'], 'approved')) {
                throw new Exception('Failed to approve commission');
            }

            // Get commission details
            $commission = $this->commission->readOne($_POST['commission_id']);

            // Log approval
            $this->logAction(
                'commission_approved',
                'commissions',
                $_POST['commission_id'],
                ['status' => 'pending'],
                ['status' => 'approved']
            );

            // Send notification
            if ($this->getSetting('notification', 'commission_notifications', 'true') === 'true') {
                $this->notification->notifyUser(
                    $commission['user_id'],
                    'commission',
                    "Your commission of " . $this->formatCurrency($commission['amount']) . " has been approved"
                );
            }

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Commission approved successfully'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function getCommissionReport() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_commission_reports');
        if ($permCheck !== true) return $permCheck;

        try {
            $dateRange = $this->getDateRange();
            if (!$dateRange) {
                return $this->jsonResponse(['error' => 'Invalid date range'], 400);
            }

            $report = $this->commission->getCommissionReport(
                $dateRange['start_date'],
                $dateRange['end_date']
            );

            $result = [];
            while ($row = $report->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'username' => $row['username'],
                    'total_orders' => $row['total_orders'],
                    'total_commission' => $this->formatCurrency($row['total_commission']),
                    'status' => $row['status'],
                    'first_commission' => $this->formatDate($row['first_commission']),
                    'last_commission' => $this->formatDate($row['last_commission'])
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'report' => $result,
                'period' => $dateRange
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getUserCommissions() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_commissions');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['user_id'], 'GET');
        if ($paramCheck !== true) return $paramCheck;

        try {
            $commissions = $this->commission->readByUser(
                $_GET['user_id'],
                $_GET['status'] ?? null
            );

            $result = [];
            while ($row = $commissions->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'id' => $row['id'],
                    'order_total' => $this->formatCurrency($row['order_total']),
                    'commission_amount' => $this->formatCurrency($row['amount']),
                    'commission_rate' => $row['commission_rate'] . '%',
                    'status' => $row['status'],
                    'created_at' => $this->formatDate($row['created_at'])
                ];
            }

            $dateRange = $this->getDateRange();
            $totalCommissions = $this->commission->getUserTotalCommissions(
                $_GET['user_id'],
                $dateRange['start_date'],
                $dateRange['end_date']
            );

            return $this->jsonResponse([
                'success' => true,
                'commissions' => $result,
                'summary' => [
                    'total_amount' => $this->formatCurrency($totalCommissions),
                    'period' => $dateRange
                ]
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
?>
