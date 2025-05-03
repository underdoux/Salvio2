<?php

require_once __DIR__ . '/../helpers/Logger.php';

class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        try {
            global $db;
            if (!$db) {
                Logger::log("Database connection not available in BaseModel constructor");
                throw new Exception("Database connection not available");
            }
            $this->db = $db;
            Logger::log("BaseModel initialized with database connection");
        } catch (Exception $e) {
            Logger::log("Error in BaseModel constructor: " . $e->getMessage());
            throw $e;
        }
    }

    public function find($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            Logger::log("Find query executed for {$this->table} with ID {$id}");
            return $result;
        } catch (Exception $e) {
            Logger::log("Error in find method: " . $e->getMessage());
            throw $e;
        }
    }

    public function all() {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Logger::log("All records retrieved from {$this->table}");
            return $results;
        } catch (Exception $e) {
            Logger::log("Error in all method: " . $e->getMessage());
            throw $e;
        }
    }

    public function create($data) {
        try {
            $fields = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            
            $id = $this->db->lastInsertId();
            Logger::log("New record created in {$this->table} with ID {$id}");
            return $id;
        } catch (Exception $e) {
            Logger::log("Error in create method: " . $e->getMessage());
            throw $e;
        }
    }

    public function update($id, $data) {
        try {
            $fields = implode('=?, ', array_keys($data)) . '=?';
            
            $sql = "UPDATE {$this->table} SET {$fields} WHERE {$this->primaryKey} = ?";
            $values = array_values($data);
            $values[] = $id;
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($values);
            Logger::log("Record updated in {$this->table} with ID {$id}");
            return $result;
        } catch (Exception $e) {
            Logger::log("Error in update method: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id]);
            Logger::log("Record deleted from {$this->table} with ID {$id}");
            return $result;
        } catch (Exception $e) {
            Logger::log("Error in delete method: " . $e->getMessage());
            throw $e;
        }
    }
}
