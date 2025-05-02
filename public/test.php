<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>PHP Test Page</h1>';
echo '<h2>PHP Version: ' . phpversion() . '</h2>';

echo '<h2>Session Test:</h2>';
session_start();
$_SESSION['test'] = 'Session is working';
echo $_SESSION['test'];

echo '<h2>File System Test:</h2>';
$config_file = __DIR__ . '/../config/app.php';
echo 'Config file exists: ' . (file_exists($config_file) ? 'Yes' : 'No') . '<br>';
if (file_exists($config_file)) {
    echo 'Config file content:<br>';
    echo '<pre>';
    var_dump(include $config_file);
    echo '</pre>';
}

echo '<h2>Directory Structure:</h2>';
echo '<pre>';
var_dump([
    'Document Root' => $_SERVER['DOCUMENT_ROOT'],
    'Script Filename' => $_SERVER['SCRIPT_FILENAME'],
    'Current File' => __FILE__,
    'Current Dir' => __DIR__,
    'Parent Dir' => dirname(__DIR__),
    'Config Dir' => realpath(__DIR__ . '/../config')
]);
echo '</pre>';

echo '<h2>Include Path:</h2>';
echo get_include_path();
?>
