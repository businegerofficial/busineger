<?php
session_start();


class Auth {
    private static $instance = null; // Holds the single instance of the class
    private $pdo;

    // Private constructor to prevent direct instantiation
    private function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get the singleton instance of the Auth class
    public static function getInstance($pdo) {
        if (self::$instance === null) {
            self::$instance = new Auth($pdo);
        }
        return self::$instance;
    }

    // Register a new user
    public function register($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $email]);
            return true;
        } catch (PDOException $e) {
            return "Registration Error: " . $e->getMessage();
        }
    }

    // Login a user
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return "Login Error: " . $e->getMessage();
        }
    }

    // Check if a user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Get the current logged-in user's data
    public function getUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username']
            ];
        }
        return null;
    }

    // Logout the current user
    public function logout() {
        session_unset();
        session_destroy();
    }

    // Redirect if not logged in
    public function requireLogin($redirectTo = 'login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: $redirectTo");
            exit();
        }
    }
}
?>
