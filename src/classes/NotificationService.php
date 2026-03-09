<?php
namespace App;

use PDO;

class NotificationService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getDashboardNotifications($employeId, $limit = 100) {
        $limit = (int)$limit; // ensure integer — PDO interpolation of string causes LIMIT '100' syntax error
        $cols = "n.notification_id, n.type, n.message, n.is_read, n.created_at,
                   l.loan_id, l.pn_number, l.loan_amount, l.term_months, l.semi_monthly_amt,
                   DATE_FORMAT(l.date_granted, '%b %d, %Y') as date_granted,
                   b.first_name, b.last_name,
                   u.first_name AS uploader_first, u.last_name AS uploader_last";

        $joins = "LEFT JOIN Loan l ON n.loan_id = l.loan_id
            LEFT JOIN Borrowers b ON l.employe_id = b.employe_id
            LEFT JOIN Users u ON n.triggered_by_employe_id = u.employe_id";

        // Step 1: Always fetch ALL unread PENDING_KPTN — sticky, must never be cut by limit
        $stmtPending = $this->db->prepare("
            SELECT $cols
            FROM Notifications n
            $joins
            WHERE n.recipient_employe_id = :id
              AND n.type = 'PENDING_KPTN'
              AND n.is_read = 0
            ORDER BY n.created_at DESC
        ");
        $stmtPending->bindValue(':id', $employeId, PDO::PARAM_INT);
        $stmtPending->execute();
        $pendingRows = $stmtPending->fetchAll(\PDO::FETCH_ASSOC);
        $pendingIds  = array_column($pendingRows, 'notification_id');

        // Step 2: Fetch remaining notifications (all types, read or unread) — exclude already-fetched pending
        $excludeSql = '';
        if (!empty($pendingIds)) {
            $placeholders = implode(',', array_fill(0, count($pendingIds), '?'));
            $excludeSql   = "AND n.notification_id NOT IN ($placeholders)";
        }

        $stmtRest = $this->db->prepare("
            SELECT $cols
            FROM Notifications n
            $joins
            WHERE n.recipient_employe_id = ?
              $excludeSql
            ORDER BY
              CASE WHEN n.type = 'PENDING_KPTN' AND n.is_read = 0 THEN 0 ELSE 1 END ASC,
              n.is_read ASC,
              n.created_at DESC
            LIMIT $limit
        ");

        $params = [$employeId];
        if (!empty($pendingIds)) {
            $params = array_merge($params, $pendingIds);
        }
        $stmtRest->execute($params);
        $restRows = $stmtRest->fetchAll(\PDO::FETCH_ASSOC);

        // Step 3: Merge — sticky PENDING_KPTN always first, then the rest
        $results = array_merge($pendingRows, $restRows);

        $unread      = [];
        $read        = [];
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