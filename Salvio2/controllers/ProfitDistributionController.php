<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/ProfitDistribution.php';
require_once __DIR__ . '/../models/ProfitShare.php';
require_once __DIR__ . '/../models/Investor.php';
require_once __DIR__ . '/../services/NotificationService.php';

class ProfitDistributionController extends Controller {
    private $profitDistribution;
    private $profitShare;
    private $investor;
    private $notification;

    protected $requiredPermissions = [
        'calculatePeriodProfit' => 'manage_profits',
        'approveDistribution' => 'approve_distributions',
        'markShareAsPaid' => 'process_payments',
        'getProfitReport' => 'view_profit_reports',
        'getDistributionDetails' => 'view_distributions'
    ];

    public function __construct() {
        parent::__construct();
        $this->profitDistribution = new ProfitDistribution();
        $this->profitShare = new ProfitShare();
        $this->investor = new Investor();
        $this->notification = new NotificationService();
    }

    public function calculatePeriodProfit() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('manage_profits');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['start_date', 'end_date']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            // Calculate metrics for the period
            $metrics = $this->profitDistribution->calculatePeriodMetrics(
                $_POST['start_date'],
                $_POST['end_date']
            );

            // Create profit distribution record
            $this->profitDistribution->period_start = $_POST['start_date'];
            $this->profitDistribution->period_end = $_POST['end_date'];
            $this->profitDistribution->total_revenue = $metrics['revenue'];
            $this->profitDistribution->total_cost = $metrics['cost'];
            $this->profitDistribution->net_profit = $metrics['net_profit'];
            $this->profitDistribution->status = 'draft';

            if (!$this->profitDistribution->create()) {
                throw new Exception('Failed to create profit distribution record');
            }

            // Log calculation
            $this->logAction(
                'profit_calculated',
                'profit_distributions',
                $this->profitDistribution->id,
                null,
                $metrics
            );

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Profit calculation completed',
                'distribution_id' => $this->profitDistribution->id,
                'metrics' => [
                    'revenue' => $this->formatCurrency($metrics['revenue']),
                    'cost' => $this->formatCurrency($metrics['cost']),
                    'net_profit' => $this->formatCurrency($metrics['net_profit'])
                ]
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function approveDistribution() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('approve_distributions');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['distribution_id']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            // Get distribution details
            $distribution = $this->profitDistribution->readOne($_POST['distribution_id']);
            if (!$distribution) {
                throw new Exception('Distribution not found');
            }

            if ($distribution['status'] !== 'draft') {
                throw new Exception('Only draft distributions can be approved');
            }

            // Validate total investor percentages
            $totalPercentage = $this->investor->getTotalActivePercentage();
            if ($totalPercentage > 100) {
                throw new Exception('Total investor percentages exceed 100%');
            }

            // Update status to approved (this will trigger profit share creation)
            if (!$this->profitDistribution->updateStatus($_POST['distribution_id'], 'approved')) {
                throw new Exception('Failed to approve distribution');
            }

            // Log approval
            $this->logAction(
                'distribution_approved',
                'profit_distributions',
                $_POST['distribution_id'],
                ['status' => 'draft'],
                ['status' => 'approved']
            );

            // Get created profit shares
            $shares = $this->profitShare->readByDistribution($_POST['distribution_id']);
            $shareDetails = [];
            
            while ($share = $shares->fetch(PDO::FETCH_ASSOC)) {
                $shareDetails[] = [
                    'investor_name' => $share['investor_name'],
                    'percentage' => $share['percentage'] . '%',
                    'amount' => $this->formatCurrency($share['amount'])
                ];

                // Send notifications to investors
                if ($this->getSetting('notification', 'profit_share_notifications', 'true') === 'true') {
                    $this->notification->notifyUser(
                        null,
                        'profit_share',
                        "Your profit share of " . $this->formatCurrency($share['amount']) . " has been calculated",
                        ['email' => $share['investor_email']]
                    );
                }
            }

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Distribution approved successfully',
                'shares' => $shareDetails
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function markShareAsPaid() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('process_payments');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['share_id']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            if (!$this->profitShare->markAsPaid($_POST['share_id'])) {
                throw new Exception('Failed to mark share as paid');
            }

            // Get share details
            $share = $this->profitShare->readOne($_POST['share_id']);

            // Log payment
            $this->logAction(
                'share_paid',
                'profit_shares',
                $_POST['share_id'],
                ['status' => 'pending'],
                ['status' => 'paid', 'paid_date' => date('Y-m-d')]
            );

            // Send notification
            if ($this->getSetting('notification', 'profit_payment_notifications', 'true') === 'true') {
                $this->notification->notifyUser(
                    null,
                    'profit_payment',
                    "Your profit share of " . $this->formatCurrency($share['amount']) . " has been paid",
                    ['email' => $share['investor_email']]
                );
            }

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Share marked as paid successfully'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function getProfitReport() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_profit_reports');
        if ($permCheck !== true) return $permCheck;

        try {
            // Get profit trends
            $trends = $this->profitDistribution->getProfitTrends(12);
            $trendData = [];
            
            while ($row = $trends->fetch(PDO::FETCH_ASSOC)) {
                $trendData[] = [
                    'month' => $row['month'],
                    'revenue' => $this->formatCurrency($row['revenue']),
                    'cost' => $this->formatCurrency($row['cost']),
                    'profit' => $this->formatCurrency($row['profit'])
                ];
            }

            // Get investor shares for the period
            $dateRange = $this->getDateRange(30);
            if (!$dateRange) {
                return $this->jsonResponse(['error' => 'Invalid date range'], 400);
            }

            $shares = $this->investor->getInvestorShares(
                $dateRange['start_date'],
                $dateRange['end_date']
            );
            
            $shareData = [];
            while ($row = $shares->fetch(PDO::FETCH_ASSOC)) {
                $shareData[] = [
                    'investor_name' => $row['name'],
                    'percentage' => $row['percentage'] . '%',
                    'total_received' => $this->formatCurrency($row['total_received']),
                    'distributions_count' => $row['total_distributions']
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'trends' => $trendData,
                'shares' => $shareData,
                'period' => $dateRange
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getDistributionDetails() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_distributions');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['distribution_id'], 'GET');
        if ($paramCheck !== true) return $paramCheck;

        try {
            $details = $this->profitDistribution->getDistributionDetails($_GET['distribution_id']);
            $result = [
                'distribution' => null,
                'shares' => []
            ];

            while ($row = $details->fetch(PDO::FETCH_ASSOC)) {
                if (!$result['distribution']) {
                    $result['distribution'] = [
                        'id' => $row['id'],
                        'period_start' => $this->formatDate($row['period_start']),
                        'period_end' => $this->formatDate($row['period_end']),
                        'total_revenue' => $this->formatCurrency($row['total_revenue']),
                        'total_cost' => $this->formatCurrency($row['total_cost']),
                        'net_profit' => $this->formatCurrency($row['net_profit']),
                        'status' => $row['status']
                    ];
                }

                $result['shares'][] = [
                    'investor_name' => $row['investor_name'],
                    'percentage' => $row['investor_percentage'] . '%',
                    'amount' => $this->formatCurrency($row['share_amount']),
                    'status' => $row['share_status'],
                    'paid_date' => $row['paid_date'] ? $this->formatDate($row['paid_date']) : null
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'details' => $result
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
?>
