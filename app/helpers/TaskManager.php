<?php

class TaskManager {
    private static $instance = null;
    private $db;
    private $logger;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->logger = AuditLogger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Task Categories and Priorities
     */
    private $taskCategories = [
        'security' => [
            'priority' => 1,
            'tasks' => [
                'penetration_testing' => [
                    'title' => 'Implement Penetration Testing',
                    'dependencies' => ['security_tester'],
                    'status' => 'pending'
                ],
                'vulnerability_scanning' => [
                    'title' => 'Add Vulnerability Scanning',
                    'dependencies' => ['security_tester'],
                    'status' => 'pending'
                ],
                'stress_testing' => [
                    'title' => 'Implement Stress Testing',
                    'dependencies' => ['security_tester'],
                    'status' => 'pending'
                ]
            ]
        ],
        'performance' => [
            'priority' => 2,
            'tasks' => [
                'cache_compression' => [
                    'title' => 'Implement Cache Compression',
                    'dependencies' => ['cache_helper'],
                    'status' => 'pending'
                ],
                'cache_replication' => [
                    'title' => 'Add Cache Replication',
                    'dependencies' => ['cache_helper'],
                    'status' => 'pending'
                ],
                'cache_statistics' => [
                    'title' => 'Add Cache Statistics',
                    'dependencies' => ['cache_helper'],
                    'status' => 'pending'
                ]
            ]
        ],
        'usability' => [
            'priority' => 3,
            'tasks' => [
                'bulk_operations' => [
                    'title' => 'Implement Bulk Operations',
                    'dependencies' => ['settings_model'],
                    'status' => 'pending'
                ],
                'import_export' => [
                    'title' => 'Add Import/Export Functionality',
                    'dependencies' => ['settings_model'],
                    'status' => 'pending'
                ],
                'setting_templates' => [
                    'title' => 'Create Setting Templates',
                    'dependencies' => ['settings_model'],
                    'status' => 'pending'
                ]
            ]
        ],
        'monitoring' => [
            'priority' => 4,
            'tasks' => [
                'security_dashboard' => [
                    'title' => 'Create Security Dashboard',
                    'dependencies' => ['security_tester', 'audit_logger'],
                    'status' => 'pending'
                ],
                'analytics_dashboard' => [
                    'title' => 'Create Analytics Dashboard',
                    'dependencies' => ['audit_logger'],
                    'status' => 'pending'
                ],
                'monitoring_tools' => [
                    'title' => 'Create Monitoring Tools',
                    'dependencies' => ['audit_logger'],
                    'status' => 'pending'
                ]
            ]
        ],
        'maintenance' => [
            'priority' => 5,
            'tasks' => [
                'log_rotation' => [
                    'title' => 'Implement Log Rotation',
                    'dependencies' => ['audit_logger'],
                    'status' => 'pending'
                ],
                'log_archiving' => [
                    'title' => 'Implement Log Archiving',
                    'dependencies' => ['audit_logger'],
                    'status' => 'pending'
                ],
                'analysis_tools' => [
                    'title' => 'Create Analysis Tools',
                    'dependencies' => ['audit_logger'],
                    'status' => 'pending'
                ]
            ]
        ]
    ];

    /**
     * Get prioritized task list
     */
    public function getPrioritizedTasks() {
        $tasks = [];
        foreach ($this->taskCategories as $category => $data) {
            foreach ($data['tasks'] as $key => $task) {
                $tasks[] = [
                    'category' => $category,
                    'key' => $key,
                    'title' => $task['title'],
                    'priority' => $data['priority'],
                    'dependencies' => $task['dependencies'],
                    'status' => $task['status']
                ];
            }
        }

        usort($tasks, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $tasks;
    }

    /**
     * Check task dependencies
     */
    public function checkDependencies($taskKey) {
        foreach ($this->taskCategories as $category) {
            if (isset($category['tasks'][$taskKey])) {
                $task = $category['tasks'][$taskKey];
                $missing = [];
                
                foreach ($task['dependencies'] as $dep) {
                    if (!$this->isDependencyMet($dep)) {
                        $missing[] = $dep;
                    }
                }
                
                return [
                    'ready' => empty($missing),
                    'missing' => $missing
                ];
            }
        }
        
        return ['ready' => false, 'missing' => ['Task not found']];
    }

    /**
     * Check if dependency is met
     */
    private function isDependencyMet($dependency) {
        switch ($dependency) {
            case 'security_tester':
                return class_exists('SecurityTester');
            case 'cache_helper':
                return class_exists('Cache');
            case 'settings_model':
                return class_exists('Settings');
            case 'audit_logger':
                return class_exists('AuditLogger');
            default:
                return false;
        }
    }

    /**
     * Update task status
     */
    public function updateTaskStatus($taskKey, $status) {
        foreach ($this->taskCategories as &$category) {
            if (isset($category['tasks'][$taskKey])) {
                $category['tasks'][$taskKey]['status'] = $status;
                $this->logTaskUpdate($taskKey, $status);
                return true;
            }
        }
        return false;
    }

    /**
     * Log task update
     */
    private function logTaskUpdate($taskKey, $status) {
        $this->logger->logSecurityEvent('task_update', [
            'task_key' => $taskKey,
            'new_status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get task implementation plan
     */
    public function getImplementationPlan($taskKey) {
        foreach ($this->taskCategories as $category) {
            if (isset($category['tasks'][$taskKey])) {
                $task = $category['tasks'][$taskKey];
                return $this->generatePlan($task);
            }
        }
        return null;
    }

    /**
     * Generate implementation plan
     */
    private function generatePlan($task) {
        $plan = [
            'title' => $task['title'],
            'steps' => [],
            'requirements' => [],
            'testing' => [],
            'documentation' => []
        ];

        // Add implementation steps based on task type
        switch ($task['title']) {
            case 'Implement Penetration Testing':
                $plan['steps'] = [
                    'Create PenTester helper class',
                    'Implement vulnerability scanning',
                    'Add security report generation',
                    'Create automated test suite'
                ];
                break;
            case 'Implement Cache Compression':
                $plan['steps'] = [
                    'Add compression algorithms',
                    'Implement compression levels',
                    'Add automatic compression',
                    'Create compression monitoring'
                ];
                break;
            // Add more task plans as needed
        }

        return $plan;
    }
}
