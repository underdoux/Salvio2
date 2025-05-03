<?php

class SecurityController extends BaseController {
    private $securityTester;
    private $auditLogger;

    public function __construct() {
        parent::__construct();
        $this->securityTester = SecurityTester::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }

    /**
     * Display security dashboard
     */
    public function dashboard() {
        $data = [
            'securityScore' => $this->calculateSecurityScore(),
            'activeThreats' => $this->getActiveThreats(),
            'lastScanDate' => $this->getLastScanDate(),
            'lastScanStatus' => $this->getLastScanStatus(),
            'testsPassedCount' => $this->getTestsPassedCount(),
            'totalTestsCount' => $this->getTotalTestsCount(),
            'penetrationTests' => $this->getLatestPenetrationTests(),
            'vulnerabilities' => $this->getLatestVulnerabilities(),
            'stressTestMetrics' => $this->getStressTestMetrics(),
            'stressTestLabels' => $this->getStressTestLabels(),
            'stressTestData' => $this->getStressTestData()
        ];

        $this->render('security/dashboard', $data);
    }

    /**
     * Run penetration test
     */
    public function penetrationTest() {
        try {
            $this->auditLogger->log('security', 'Starting penetration test');
            $results = $this->securityTester->runPenetrationTest();
            $this->auditLogger->log('security', 'Penetration test completed', ['results' => $results]);
            $_SESSION['message'] = 'Penetration test completed successfully.';
        } catch (Exception $e) {
            $this->auditLogger->log('security', 'Penetration test failed', ['error' => $e->getMessage()]);
            $_SESSION['error'] = 'Penetration test failed: ' . $e->getMessage();
        }
        $this->redirect('/security/dashboard');
    }

    /**
     * Run stress test
     */
    public function stressTest() {
        try {
            $this->auditLogger->log('security', 'Starting stress test');
            $results = $this->securityTester->runStressTest();
            $this->auditLogger->log('security', 'Stress test completed', ['results' => $results]);
            $_SESSION['message'] = 'Stress test completed successfully.';
        } catch (Exception $e) {
            $this->auditLogger->log('security', 'Stress test failed', ['error' => $e->getMessage()]);
            $_SESSION['error'] = 'Stress test failed: ' . $e->getMessage();
        }
        $this->redirect('/security/dashboard');
    }

    /**
     * Run vulnerability scan
     */
    public function vulnerabilityScan() {
        try {
            $this->auditLogger->log('security', 'Starting vulnerability scan');
            $results = $this->securityTester->runVulnerabilityScan();
            $this->auditLogger->log('security', 'Vulnerability scan completed', ['results' => $results]);
            $_SESSION['message'] = 'Vulnerability scan completed successfully.';
        } catch (Exception $e) {
            $this->auditLogger->log('security', 'Vulnerability scan failed', ['error' => $e->getMessage()]);
            $_SESSION['error'] = 'Vulnerability scan failed: ' . $e->getMessage();
        }
        $this->redirect('/security/dashboard');
    }

    /**
     * Run full security audit
     */
    public function fullAudit() {
        try {
            $this->auditLogger->log('security', 'Starting full security audit');
            $results = $this->securityTester->runFullAudit();
            $this->auditLogger->log('security', 'Full security audit completed', ['results' => $results]);
            $_SESSION['message'] = 'Full security audit completed successfully.';
        } catch (Exception $e) {
            $this->auditLogger->log('security', 'Full security audit failed', ['error' => $e->getMessage()]);
            $_SESSION['error'] = 'Full security audit failed: ' . $e->getMessage();
        }
        $this->redirect('/security/dashboard');
    }

    /**
     * Calculate overall security score
     */
    private function calculateSecurityScore() {
        $metrics = [
            'penetrationTestScore' => $this->securityTester->getPenetrationTestScore(),
            'vulnerabilityScore' => $this->securityTester->getVulnerabilityScore(),
            'stressTestScore' => $this->securityTester->getStressTestScore(),
            'configurationScore' => $this->securityTester->getConfigurationScore()
        ];

        $weights = [
            'penetrationTestScore' => 0.35,
            'vulnerabilityScore' => 0.35,
            'stressTestScore' => 0.15,
            'configurationScore' => 0.15
        ];

        $score = 0;
        foreach ($metrics as $key => $value) {
            $score += $value * $weights[$key];
        }

        return round($score);
    }

    /**
     * Get active security threats
     */
    private function getActiveThreats() {
        return $this->securityTester->getActiveThreats();
    }

    /**
     * Get last security scan date
     */
    private function getLastScanDate() {
        $lastScan = $this->securityTester->getLastScan();
        return $lastScan ? date('Y-m-d H:i:s', strtotime($lastScan['date'])) : 'Never';
    }

    /**
     * Get last security scan status
     */
    private function getLastScanStatus() {
        $lastScan = $this->securityTester->getLastScan();
        return $lastScan ? $lastScan['status'] : 'Not Available';
    }

    /**
     * Get number of passed security tests
     */
    private function getTestsPassedCount() {
        return $this->securityTester->getPassedTestsCount();
    }

    /**
     * Get total number of security tests
     */
    private function getTotalTestsCount() {
        return $this->securityTester->getTotalTestsCount();
    }

    /**
     * Get latest penetration test results
     */
    private function getLatestPenetrationTests() {
        return $this->securityTester->getLatestPenetrationTests();
    }

    /**
     * Get latest vulnerability scan results
     */
    private function getLatestVulnerabilities() {
        return $this->securityTester->getLatestVulnerabilities();
    }

    /**
     * Get stress test metrics
     */
    private function getStressTestMetrics() {
        return $this->securityTester->getStressTestMetrics();
    }

    /**
     * Get stress test chart labels
     */
    private function getStressTestLabels() {
        $metrics = $this->securityTester->getStressTestHistory();
        return array_map(function($metric) {
            return date('H:i:s', strtotime($metric['timestamp']));
        }, $metrics);
    }

    /**
     * Get stress test chart data
     */
    private function getStressTestData() {
        $metrics = $this->securityTester->getStressTestHistory();
        return array_map(function($metric) {
            return $metric['response_time'];
        }, $metrics);
    }
}
