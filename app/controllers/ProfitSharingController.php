<?php

class ProfitSharingController extends BaseController {
    private $profitSharing;
    private $investor;

    public function __construct() {
        parent::__construct();
        $this->profitSharing = new ProfitSharing();
        $this->investor = new Investor();
    }

    public function index() {
        $year = $_GET['year'] ?? date('Y');
        $profits = $this->profitSharing->getMonthlyProfits(['year' => $year]);
        
        // Get available years for filter
        $years = range(date('Y'), 2023); // Starting from 2023 or adjust as needed
        
        $this->render('profit_sharing/index', [
            'title' => 'Profit Sharing - Salvio POS',
            'profits' => $profits,
            'years' => $years,
            'selectedYear' => $year
        ]);
    }

    public function calculate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/Salvio2/public/profit-sharing');
            return;
        }

        try {
            $period = $_POST['period'] ?? date('Y-m');
            
            // Calculate monthly profit
            $monthlyProfitId = $this->profitSharing->calculateMonthlyProfit($period);
            
            Logger::log("Monthly profit calculated for period {$period}");
            
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => "Profit calculation completed for {$period}"
            ];
            
            $this->redirect("/Salvio2/public/profit-sharing/view/{$monthlyProfitId}");

        } catch (Exception $e) {
            Logger::log("Error calculating profit: " . $e->getMessage(), 'error');
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => $e->getMessage()
            ];
            $this->redirect('/Salvio2/public/profit-sharing');
        }
    }

    public function view($id) {
        $profit = $this->profitSharing->find($id);
        
        if (!$profit) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Profit record not found'
            ];
            $this->redirect('/Salvio2/public/profit-sharing');
            return;
        }

        // Get distribution details if profit is finalized
        $distribution = null;
        if ($profit['status'] === 'final') {
            $distribution = $this->profitSharing->getDistributionDetails($id);
        }

        // Get active investors for profit distribution
        $investors = $this->investor->getAll();
        
        $this->render('profit_sharing/view', [
            'title' => 'Profit Details - Salvio POS',
            'profit' => $profit,
            'distribution' => $distribution,
            'investors' => $investors
        ]);
    }

    public function finalize($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("/Salvio2/public/profit-sharing/view/{$id}");
            return;
        }

        try {
            // Finalize the profit calculation
            $this->profitSharing->finalizeProfit($id);
            
            // Calculate and save profit distribution
            $this->profitSharing->distributeProfit($id);
            
            Logger::log("Monthly profit #{$id} finalized and distributed");
            
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Profit calculation finalized and distributed'
            ];

        } catch (Exception $e) {
            Logger::log("Error finalizing profit: " . $e->getMessage(), 'error');
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => $e->getMessage()
            ];
        }

        $this->redirect("/Salvio2/public/profit-sharing/view/{$id}");
    }

    public function report($id) {
        $profit = $this->profitSharing->find($id);
        
        if (!$profit || $profit['status'] !== 'final') {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Finalized profit record not found'
            ];
            $this->redirect('/Salvio2/public/profit-sharing');
            return;
        }

        $distribution = $this->profitSharing->getDistributionDetails($id);
        
        $this->render('profit_sharing/report', [
            'title' => 'Profit Distribution Report - Salvio POS',
            'profit' => $profit,
            'distribution' => $distribution
        ]);
    }
}
