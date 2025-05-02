<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';

class ProductController {
    private $productModel;
    private $categoryModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }

    public function list() {
        $products = $this->productModel->readAll();
        $categories = $this->categoryModel->readAll();
        require __DIR__ . '/../views/product/list.php';
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->productModel->name = $_POST['name'] ?? '';
            $this->productModel->category_id = $_POST['category_id'] ?? null;
            $this->productModel->stock = $_POST['stock'] ?? 0;
            $this->productModel->by_order = isset($_POST['by_order']) ? true : false;
            $this->productModel->price = $_POST['price'] ?? 0.0;

            if ($this->productModel->create()) {
                header("Location: /products");
                exit();
            } else {
                $error = "Failed to add product.";
                require __DIR__ . '/../views/product/add.php';
            }
        } else {
            $categories = $this->categoryModel->readAll();
            require __DIR__ . '/../views/product/add.php';
        }
    }

    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->productModel->id = $id;
            $this->productModel->name = $_POST['name'] ?? '';
            $this->productModel->category_id = $_POST['category_id'] ?? null;
            $this->productModel->stock = $_POST['stock'] ?? 0;
            $this->productModel->by_order = isset($_POST['by_order']) ? true : false;
            $this->productModel->price = $_POST['price'] ?? 0.0;

            if ($this->productModel->update()) {
                header("Location: /products");
                exit();
            } else {
                $error = "Failed to update product.";
                $product = $this->productModel->readOne($id);
                $categories = $this->categoryModel->readAll();
                require __DIR__ . '/../views/product/edit.php';
            }
        } else {
            $product = $this->productModel->readOne($id);
            $categories = $this->categoryModel->readAll();
            require __DIR__ . '/../views/product/edit.php';
        }
    }

    public function delete($id) {
        if ($this->productModel->delete($id)) {
            header("Location: /products");
            exit();
        } else {
            echo "Failed to delete product.";
        }
    }
}
?>
