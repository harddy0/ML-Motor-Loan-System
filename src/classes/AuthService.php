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

    public function login($email, $password) {
        // Temporary logic for testing
        if ($email === 'admin@test.com' && $password === '123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_email'] = $email;
            return true;
        }
        return false;
    }


    public function logout() {
    $_SESSION = []; // Clear data
    session_destroy(); // Kill session
    return true;
}
}