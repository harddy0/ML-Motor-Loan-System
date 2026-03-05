<?php
namespace App;

class AuthService {
    private $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {

            if (isset($user['status']) && $user['status'] === 'RESTRICTED') {
                return ['success' => false, 'error' => 'Account is restricted. Please contact the Administrator.'];
            }

            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['employe_id'];
            $_SESSION['employe_id'] = $user['employe_id'];
            $_SESSION['username']   = $user['username'];

            $middleInitial = !empty($user['middle_name']) ? ' ' . substr($user['middle_name'], 0, 1) . '. ' : ' ';
            $_SESSION['full_name']  = $user['first_name'] . $middleInitial . $user['last_name'];

            $_SESSION['user_type']  = $user['user_type'];

            // password_changed_at NOT NULL = reset is pending, force change
            $_SESSION['must_change_password'] = !empty($user['password_changed_at']);

            $update = $this->db->prepare("UPDATE Users SET last_login = NOW() WHERE employe_id = :id");
            $update->execute([':id' => $user['employe_id']]);

            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Invalid Username or Password.'];
    }

    public function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        return true;
    }

    /**
     * User changes their own password (forced or voluntary).
     * Clears password_changed_at back to NULL — flag lifted.
     */
    public function changeUserPassword($employeId, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $stmt = $this->db->prepare("
            UPDATE Users
            SET password_hash       = :hash,
                password_changed_at = NULL
            WHERE employe_id = :id
        ");
        return $stmt->execute([
            ':hash' => $hash,
            ':id'   => $employeId
        ]);
    }

    // ==========================================
    // ADMIN FUNCTIONS (USER MANAGEMENT)
    // ==========================================

    public function getAllUsers() {
        $stmt = $this->db->query("
            SELECT employe_id, username, first_name, middle_name, last_name,
                   user_type, status, last_login, password_changed_at
            FROM Users
            ORDER BY user_type ASC, last_name ASC, first_name ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function registerUser($employeId, $firstName, $middleName, $lastName, $username, $userType = 'USER', $status = 'ACTIVE') {
        try {
            // New accounts get the default password and are immediately flagged
            // to change it — password_changed_at = NOW()
            $hash = password_hash('Mlinc1234@', PASSWORD_ARGON2ID);

            $stmt = $this->db->prepare("
                INSERT INTO Users
                    (employe_id, first_name, middle_name, last_name, username,
                     password_hash, user_type, status, password_changed_at)
                VALUES
                    (:eid, :fname, :mname, :lname, :username,
                     :hash, :type, :status, NOW())
            ");

            $stmt->execute([
                ':eid'      => $employeId,
                ':fname'    => $firstName,
                ':mname'    => empty($middleName) ? null : $middleName,
                ':lname'    => $lastName,
                ':username' => $username,
                ':hash'     => $hash,
                ':type'     => $userType,
                ':status'   => $status
            ]);
            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'error' => 'Employee ID or Username already exists.'];
            }
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function updateUserStatusAndRole($employeId, $userType, $status) {
        $stmt = $this->db->prepare("UPDATE Users SET user_type = :type, status = :status WHERE employe_id = :id");
        return $stmt->execute([
            ':type'   => $userType,
            ':status' => $status,
            ':id'     => $employeId
        ]);
    }

    /**
     * Admin resets a user's password back to default.
     * Sets password_changed_at = NOW() — forces change on next login.
     */
    public function resetPassword($employeId) {
        $hash = password_hash('Mlinc1234@', PASSWORD_ARGON2ID);

        $stmt = $this->db->prepare("
            UPDATE Users
            SET password_hash       = :hash,
                password_changed_at = NOW()
            WHERE employe_id = :id
        ");

        if ($stmt->execute([':hash' => $hash, ':id' => $employeId])) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Failed to reset password.'];
    }
}