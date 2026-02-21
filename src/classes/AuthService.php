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

    /**
     * Authenticate a user & check their account status
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            
            // SECURITY SHIELD: Check if the account is restricted
            if (isset($user['status']) && $user['status'] === 'RESTRICTED') {
                return ['success' => false, 'error' => 'Account is restricted. Please contact the Administrator.'];
            }

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];

            // Timestamp the last login
            $update = $this->db->prepare("UPDATE Users SET last_login = NOW() WHERE user_id = :id");
            $update->execute([':id' => $user['user_id']]);
            
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

    // ==========================================
    // ADMIN FUNCTIONS (USER MANAGEMENT)
    // ==========================================

    public function getAllUsers() {
        $stmt = $this->db->query("
            SELECT user_id, username, full_name, user_type, status, last_login 
            FROM Users 
            ORDER BY user_type ASC, full_name ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function registerUser($fullName, $username, $password, $userType = 'USER', $status = 'ACTIVE') {
        try {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $this->db->prepare("
                INSERT INTO Users (full_name, username, password_hash, user_type, status) 
                VALUES (:name, :username, :hash, :type, :status)
            ");
            
            $stmt->execute([
                ':name' => $fullName,
                ':username' => $username,
                ':hash' => $hash,
                ':type' => $userType,
                ':status' => $status
            ]);
            return ['success' => true];
        } catch (\PDOException $e) {
            // Check for duplicate username (MySQL Error 1062)
            if ($e->getCode() == 23000) {
                return ['success' => false, 'error' => 'Username already exists.'];
            }
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function updateUserStatusAndRole($userId, $userType, $status) {
        $stmt = $this->db->prepare("UPDATE Users SET user_type = :type, status = :status WHERE user_id = :id");
        return $stmt->execute([
            ':type' => $userType, 
            ':status' => $status, 
            ':id' => $userId
        ]);
    }
}