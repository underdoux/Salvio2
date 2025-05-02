<?php

class User extends BaseModel {
    protected $table = 'users';

    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ? AND status = TRUE LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            // Remove password from session data
            unset($user['password']);
            return $user;
        }

        return false;
    }
}
