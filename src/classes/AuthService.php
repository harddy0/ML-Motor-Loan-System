<?php
namespace App;

class AuthService {
    private $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    /**
     * Check if user is currently logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Authenticate a user based on the new 'Users' schema
     */
    public function login($username, $password) {
        // 1. Fetch user by username (matches 'username' column in SQL)
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        // 2. Verify Password using 'password_hash' column
        if ($user && password_verify($password, $user['password_hash'])) {
            // 3. Security: Regenerate Session ID
            session_regenerate_id(true);

            // 4. Set Session Variables based on new columns
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];

            // 5. Update last_login timestamp in DB
            $update = $this->db->prepare("UPDATE Users SET last_login = NOW() WHERE user_id = :id");
            $update->execute([':id' => $user['user_id']]);
            
            return true;
        }

        return false;
    }

    /**
     * Log the user out
     */
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
     * Utility to register a new user (Aligns with Users table)
     */
    public function registerUser($fullName, $username, $password, $userType = 'ADMIN') {
        // Hash using Argon2ID
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $stmt = $this->db->prepare("
            INSERT INTO Users (full_name, username, password_hash, user_type) 
            VALUES (:name, :username, :hash, :type)
        ");
        
        return $stmt->execute([
            ':name' => $fullName,
            ':username' => $username,
            ':hash' => $hash,
            ':type' => $userType
        ]);
    }
}