<?php

class ProductsController extends BaseController {
    protected $requiresAuth = true;
    private $productModel;
    private $categoryModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }

    public function index() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10;

            $filters = [
                'search' => $_GET['search'] ?? null,
                'category_id' => $_GET['category'] ?? null,
                'stock_type' => $_GET['stock_type'] ?? null,
                'is_active' => isset($_GET['is_active']) ? (bool)$_GET['is_active'] : true
            ];

            $products = $this->productModel->getAll($filters, $page, $limit);
            $categories = $this->categoryModel->getAll();

            return $this->render('products/index', [
                'title' => 'Manage Products',
                'products' => $products,
                'categories' => $categories,
                'filters' => $filters,
                'currentPage' => $page
            ]);
        } catch (Exception $e) {
            Logger::log("Error in products index: " . $e->getMessage(), 'ERROR');
            return $this->render('products/index', [
                'title' => 'Manage Products',
                'error' => 'An error occurred while loading products.'
            ]);
        }
    }

    public function create() {
        if ($this->isPost()) {
            try {
                $data = [
                    'name' => $this->getPost('name'),
                    'sku' => $this->getPost('sku'),
                    'bpom_id' => $this->getPost('bpom_id'),
                    'category_id' => $this->getPost('category_id'),
                    'description' => $this->getPost('description'),
                    'purchase_price' => $this->getPost('purchase_price'),
                    'selling_price' => $this->getPost('selling_price'),
                    'stock_type' => $this->getPost('stock_type'),
                    'min_stock' => $this->getPost('min_stock'),
                    'initial_stock' => $this->getPost('initial_stock'),
                    'is_active' => true
                ];

                // Handle file upload if image is provided
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $data['image'] = $this->handleImageUpload($_FILES['product_image']);
                }

                $productId = $this->productModel->create($data);

                if ($data['bpom_id']) {
                    $this->productModel->syncBPOMData($data['bpom_id']);
                }

                $this->redirect('/products/view/' . $productId);
            } catch (Exception $e) {
                Logger::log("Error creating product: " . $e->getMessage(), 'ERROR');
                return $this->render('products/create', [
                    'title' => 'Add New Product',
                    'categories' => $this->categoryModel->getAll(),
                    'error' => $e->getMessage(),
                    'data' => $data ?? []
                ]);
            }
        }

        return $this->render('products/create', [
            'title' => 'Add New Product',
            'categories' => $this->categoryModel->getAll()
        ]);
    }

    public function edit($id) {
        try {
            $product = $this->productModel->getById($id);
            if (!$product) {
                throw new Exception("Product not found");
            }

            if ($this->isPost()) {
                $data = [
                    'name' => $this->getPost('name'),
                    'category_id' => $this->getPost('category_id'),
                    'description' => $this->getPost('description'),
                    'purchase_price' => $this->getPost('purchase_price'),
                    'selling_price' => $this->getPost('selling_price'),
                    'stock_type' => $this->getPost('stock_type'),
                    'min_stock' => $this->getPost('min_stock'),
                    'is_active' => (bool)$this->getPost('is_active', true),
                    'price_change_reason' => $this->getPost('price_change_reason')
                ];

                // Handle file upload if new image is provided
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $data['image'] = $this->handleImageUpload($_FILES['product_image']);
                }

                $this->productModel->update($id, $data);
                $this->redirect('/products/view/' . $id);
            }

            return $this->render('products/edit', [
                'title' => 'Edit Product',
                'product' => $product,
                'categories' => $this->categoryModel->getAll()
            ]);
        } catch (Exception $e) {
            Logger::log("Error editing product {$id}: " . $e->getMessage(), 'ERROR');
            return $this->render('products/edit', [
                'title' => 'Edit Product',
                'error' => $e->getMessage(),
                'product' => $product ?? null,
                'categories' => $this->categoryModel->getAll()
            ]);
        }
    }

    public function view($id) {
        try {
            $product = $this->productModel->getById($id);
            if (!$product) {
                throw new Exception("Product not found");
            }

            return $this->render('products/view', [
                'title' => $product['name'],
                'product' => $product
            ]);
        } catch (Exception $e) {
            Logger::log("Error viewing product {$id}: " . $e->getMessage(), 'ERROR');
            $this->redirect('/products');
        }
    }

    public function updateStock($id) {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $quantity = (int)$this->getPost('quantity');
            $type = $this->getPost('type');
            $reference = $this->getPost('reference');

            $this->productModel->updateStock($id, $quantity, $type, $reference);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/products/view/' . $id);
        } catch (Exception $e) {
            Logger::log("Error updating stock for product {$id}: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/products/view/' . $id);
        }
    }

    private function handleImageUpload($file) {
        $uploadDir = __DIR__ . '/../../public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload image");
        }

        return 'uploads/products/' . $filename;
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
