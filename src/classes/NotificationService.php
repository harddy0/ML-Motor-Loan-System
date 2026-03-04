<?php
namespace App;

use PDO;

class NotificationService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getDashboardNotifications($employeId, $limit = 50) {
        // Updated to JOIN the Users table to fetch who triggered the notification
        $stmt = $this->db->prepare("
            SELECT n.notification_id, n.type, n.message, n.is_read, n.created_at,
                   l.loan_id, l.pn_number, l.loan_amount, l.term_months, l.semi_monthly_amt, DATE_FORMAT(l.date_granted, '%b %d, %Y') as date_granted,
                   b.first_name, b.last_name,
                   u.first_name AS uploader_first, u.last_name AS uploader_last
            FROM Notifications n
            LEFT JOIN Loan l ON n.loan_id = l.loan_id
            LEFT JOIN Borrowers b ON l.employe_id = b.employe_id
            LEFT JOIN Users u ON n.triggered_by_employe_id = u.employe_id
            WHERE n.recipient_employe_id = :id
            ORDER BY n.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':id', $employeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $unread = [];
        $read = [];
        $unreadCount = 0;
        
        foreach ($results as $row) {
            if ($row['is_read']) {
                $read[] = $row;
            } else {
                $unread[] = $row;
                $unreadCount++;
            }
        }
        
        return ['unread' => $unread, 'read' => $read, 'unread_count' => $unreadCount];
    }

    public function markAsRead($notificationId, $employeId) {
        // 1. Check the type to prevent manual dismissal of PENDING_KPTN
        $checkStmt = $this->db->prepare("SELECT type FROM Notifications WHERE notification_id = :nid");
        $checkStmt->execute([':nid' => $notificationId]);
        
        if ($checkStmt->fetchColumn() === 'PENDING_KPTN') {
            throw new \Exception("This notification is sticky and can only be dismissed by attaching the KPTN receipt.");
        }

        // 2. Proceed with normal dismissal for other types
        $stmt = $this->db->prepare("
            UPDATE Notifications 
            SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
            WHERE notification_id = :nid AND recipient_employe_id = :eid
        ");
        return $stmt->execute([
            ':nid' => $notificationId,
            ':eid' => $employeId
        ]);
    }

    // Auto-resolves the sticky notification when the receipt is uploaded
   public function resolvePendingKptnNotification($loanId) {
    // Instead of DELETE, we UPDATE to mark it as read
    $stmt = $this->db->prepare("
        UPDATE Notifications 
        SET is_read = TRUE, 
            read_at = CURRENT_TIMESTAMP 
        WHERE loan_id = :loan_id 
        AND type = 'PENDING_KPTN'
    ");
    return $stmt->execute([':loan_id' => $loanId]);
}
}