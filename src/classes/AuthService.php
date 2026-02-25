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
            // Combine for UI consistency across other pages
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // SECURITY: Store the must_change_password flag
            $_SESSION['must_change_password'] = (bool)$user['must_change_password'];

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

    /**
     * Change user password and lift the restriction flag
     */
    public function changeUserPassword($userId, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $stmt = $this->db->prepare("
            UPDATE Users 
            SET password_hash = :hash, must_change_password = 0 
            WHERE user_id = :id
        ");
        return $stmt->execute([
            ':hash' => $hash,
            ':id' => $userId
        ]);
    }

    // ==========================================
    // ADMIN FUNCTIONS (USER MANAGEMENT)
    // ==========================================

    public function getAllUsers() {
        $stmt = $this->db->query("
            SELECT user_id, username, first_name, last_name, user_type, status, last_login 
            FROM Users 
            ORDER BY user_type ASC, last_name ASC, first_name ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function registerUser($firstName, $lastName, $username, $password, $userType = 'USER', $status = 'ACTIVE') {
        try {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $this->db->prepare("
                INSERT INTO Users (first_name, last_name, username, password_hash, user_type, status) 
                VALUES (:fname, :lname, :username, :hash, :type, :status)
            ");
            
            $stmt->execute([
                ':fname' => $firstName,
                ':lname' => $lastName,
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

    /**
     * Reset a user's password to default (First 4 chars of last name + current year)
     * and force them to change it on their next login.
     */
    public function resetPassword($username) {
        // 1. Find the user
        $stmt = $this->db->prepare("SELECT user_id, last_name FROM Users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'error' => 'Username not found in our records.'];
        }

        // 2. Generate Default Password
        // Get first 4 letters of last name, convert to lowercase. 
        // Pad with '0' if the last name is surprisingly short (e.g., "Sy")
        $lastNamePrefix = strtolower(substr($user['last_name'], 0, 4));
        $lastNamePrefix = str_pad($lastNamePrefix, 4, '0');
        $currentYear = date('Y');
        
        $defaultPassword = $lastNamePrefix . $currentYear;
        $hash = password_hash($defaultPassword, PASSWORD_ARGON2ID);

        // 3. Update database and lock them into the forced-change screen
        $updateStmt = $this->db->prepare("
            UPDATE Users 
            SET password_hash = :hash, must_change_password = 1 
            WHERE user_id = :id
        ");
        
        $updateStmt->execute([
            ':hash' => $hash,
            ':id' => $user['user_id']
        ]);

        return ['success' => true];
    }

}