<?php

require_once __DIR__ . '/../helpers/Logger.php';

class ProductsController extends BaseController {
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            header('Location: /Salvio2/public/auth');
            exit;
        }

        Logger::log("User '{$_SESSION['user']['username']}' accessed products list.");
        $products = $this->productModel->getAll();
        $categories = $this->productModel->getCategories();

        $this->render('products/index', [
            'products' => $products,
            'categories' => $categories,
            'user' => $_SESSION['user']
        ]);
    }

    public function create() {
        // Check if user is logged in and is admin
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            Logger::log("Unauthorized access attempt to product creation by user '{$_SESSION['user']['username']}'");
            header('Location: /Salvio2/public/auth');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => $_POST['name'],
                    'bpom_id' => $_POST['bpom_id'],
                    'category_id' => $_POST['category_id'],
                    'description' => $_POST['description'],
                    'purchase_price' => $_POST['purchase_price'],
                    'selling_price' => $_POST['selling_price'],
                    'stock_type' => $_POST['stock_type'],
                    'min_stock' => $_POST['min_stock'],
                    'status' => true
                ];

                $productId = $this->productModel->create($data);
                Logger::log("User '{$_SESSION['user']['username']}' created new product: {$data['name']} (ID: {$productId})");

                if ($_POST['stock_type'] === 'stocked' && isset($_POST['initial_stock'])) {
                    $this->productModel->updateStock($productId, $_POST['initial_stock'], 'initial');
                    Logger::log("Initial stock of {$_POST['initial_stock']} units set for product {$data['name']} (ID: {$productId})");
                }

                header('Location: /Salvio2/public/products');
                exit;

            } catch (Exception $e) {
                Logger::log("Error creating product by user '{$_SESSION['user']['username']}': {$e->getMessage()}");
                $_SESSION['error'] = $e->getMessage();
            }
        }

        $categories = $this->productModel->getCategories();
        $this->render('products/create', [
            'categories' => $categories,
            'user' => $_SESSION['user']
        ]);
    }

    public function edit($id) {
        // Check if user is logged in and is admin
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            Logger::log("Unauthorized access attempt to edit product ID {$id} by user '{$_SESSION['user']['username']}'");
            header('Location: /Salvio2/public/auth');
            exit;
        }

        $product = $this->productModel->getById($id);
        if (!$product) {
            Logger::log("User '{$_SESSION['user']['username']}' attempted to edit non-existent product ID: {$id}");
            header('Location: /Salvio2/public/products');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => $_POST['name'],
                    'bpom_id' => $_POST['bpom_id'],
                    'category_id' => $_POST['category_id'],
                    'description' => $_POST['description'],
                    'purchase_price' => $_POST['purchase_price'],
                    'selling_price' => $_POST['selling_price'],
                    'min_stock' => $_POST['min_stock']
                ];

                $this->productModel->update($id, $data);
                Logger::log("User '{$_SESSION['user']['username']}' updated product ID {$id}: {$data['name']}");

                if (isset($_POST['stock_adjustment'])) {
                    $this->productModel->updateStock($id, $_POST['stock_adjustment'], 'adjustment');
                    Logger::log("Stock adjusted by {$_POST['stock_adjustment']} units for product {$data['name']} (ID: {$id})");
                }

                header('Location: /Salvio2/public/products');
                exit;

            } catch (Exception $e) {
                Logger::log("Error updating product ID {$id} by user '{$_SESSION['user']['username']}': {$e->getMessage()}");
                $_SESSION['error'] = $e->getMessage();
            }
        }

        $categories = $this->productModel->getCategories();
        $this->render('products/edit', [
            'product' => $product,
            'categories' => $categories,
            'user' => $_SESSION['user']
        ]);
    }
}
