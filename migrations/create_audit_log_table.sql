CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create indexes
CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_audit_action ON audit_log(action);
CREATE INDEX idx_audit_created ON audit_log(created_at);

-- Add triggers for important tables

-- Products audit
DELIMITER //
CREATE TRIGGER products_after_insert 
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, details)
    VALUES (
        @current_user_id,
        'product_created',
        JSON_OBJECT(
            'product_id', NEW.id,
            'name', NEW.name,
            'category_id', NEW.category_id,
            'stock', NEW.stock,
            'price', NEW.price
        )
    );
END//

CREATE TRIGGER products_after_update
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, details)
    VALUES (
        @current_user_id,
        'product_updated',
        JSON_OBJECT(
            'product_id', NEW.id,
            'changes', JSON_OBJECT(
                'name', IF(NEW.name != OLD.name, JSON_ARRAY(OLD.name, NEW.name), NULL),
                'category_id', IF(NEW.category_id != OLD.category_id, JSON_ARRAY(OLD.category_id, NEW.category_id), NULL),
                'stock', IF(NEW.stock != OLD.stock, JSON_ARRAY(OLD.stock, NEW.stock), NULL),
                'price', IF(NEW.price != OLD.price, JSON_ARRAY(OLD.price, NEW.price), NULL)
            )
        )
    );
END//

CREATE TRIGGER products_after_delete
AFTER DELETE ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, details)
    VALUES (
        @current_user_id,
        'product_deleted',
        JSON_OBJECT(
            'product_id', OLD.id,
            'name', OLD.name
        )
    );
END//

-- Orders audit
CREATE TRIGGER orders_after_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, details)
    VALUES (
        @current_user_id,
        'order_created',
        JSON_OBJECT(
            'order_id', NEW.id,
            'user_id', NEW.user_id,
            'status', NEW.status,
            'total', NEW.total
        )
    );
END//

CREATE TRIGGER orders_after_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, details)
    VALUES (
        @current_user_id,
        'order_updated',
        JSON_OBJECT(
            'order_id', NEW.id,
            'changes', JSON_OBJECT(
                'status', IF(NEW.status != OLD.status, JSON_ARRAY(OLD.status, NEW.status), NULL),
                'total', IF(NEW.total != OLD.total, JSON_ARRAY(OLD.total, NEW.total), NULL)
            )
        )
    );
END//

-- Users audit
CREATE TRIGGER users_after_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, details)
    VALUES (
        @current_user_id,
        'user_created',
        JSON_OBJECT(
            'user_id', NEW.id,
            'username', NEW.username,
            'role_id', NEW.role_id
        )
    );
END//

CREATE TRIGGER users_after_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, details)
    VALUES (
        @current_user_id,
        'user_updated',
        JSON_OBJECT(
            'user_id', NEW.id,
            'changes', JSON_OBJECT(
                'username', IF(NEW.username != OLD.username, JSON_ARRAY(OLD.username, NEW.username), NULL),
                'role_id', IF(NEW.role_id != OLD.role_id, JSON_ARRAY(OLD.role_id, NEW.role_id), NULL)
            )
        )
    );
END//

DELIMITER ;

-- Create procedure to set current user
DELIMITER //
CREATE PROCEDURE set_current_user(IN user_id INT)
BEGIN
    SET @current_user_id = user_id;
END//
DELIMITER ;
