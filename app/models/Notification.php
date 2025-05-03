<?php

class Notification extends BaseModel {
    public function createNotification($data) {
        $sql = "INSERT INTO notifications (
                    user_id,
                    type,
                    title,
                    message,
                    reference_type,
                    reference_id,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['user_id'],
            $data['type'],
            $data['title'],
            $data['message'],
            $data['reference_type'],
            $data['reference_id'],
            'unread'
        ];

        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }

    public function markAsRead($notificationId) {
        $sql = "UPDATE notifications 
                SET status = 'read', read_at = NOW() 
                WHERE id = ?";
        return $this->db->query($sql, [$notificationId]);
    }

    public function getUserNotifications($userId, $limit = 50) {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        return $this->db->query($sql, [$userId, $limit])->fetchAll();
    }

    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND status = 'unread'";
        $result = $this->db->query($sql, [$userId])->fetch();
        return $result['count'];
    }

    public function sendEmailNotification($userId, $subject, $message) {
        $sql = "SELECT email FROM users WHERE id = ?";
        $user = $this->db->query($sql, [$userId])->fetch();

        if ($user && $user['email']) {
            $headers = 'From: noreply@salvio.com' . "\r\n" .
                      'Reply-To: noreply@salvio.com' . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();

            mail($user['email'], $subject, $message, $headers);
        }
    }
}
