<?php
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/security_utils.php';
    
    // Request origin verification
    $originCheck = SecurityUtils::verifyOrigin(true); // Strict mode enabled
    
    if (!$originCheck['allowed']) {
        // Log unauthorized access attempt
        SecurityUtils::logUnauthorizedAccess($originCheck);
        
        http_response_code(403);
        header("Content-Type: application/json");
        echo json_encode([
            'error' => 'Unauthorized access',
            'message' => 'This resource is only accessible from authorized domains.',
            'reason' => $originCheck['reason']
        ]);
        exit();
    }
    
    // Set authorized origin in CORS headers
    header("Access-Control-Allow-Origin: " . $originCheck['origin']);
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $host = $_ENV['DB_HOST'];
    $db_username = $_ENV['DB_USERNAME'];
    $db_password = $_ENV['DB_PASSWORD'];
    $db_name = $_ENV['DB_NAME'];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['uname'] ?? '');
        $password = $_POST['upassword'] ?? '';
        
        if (!empty($name) && !empty($password) && strlen($name) > 0 && strlen($password) > 0) {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users_2 WHERE username = :uname");
            $check_stmt->execute([':uname' => $name]);
            $user_exists = $check_stmt->fetchColumn();
            
            if ($user_exists > 0) {
                echo "Erreur : This username already exists.";
            } else {
                $mdp_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users_2 (username, hash_password) VALUES (:uname, :upassword)");
                $stmt->execute([
                    ':uname' => $name,
                    ':upassword' => $mdp_hash
                ]);

                echo "User created successfully.";
            }
        } else {
            $errors = [];
            if (empty($name) || strlen($name) == 0) {
                $errors[] = "Username is required";
            }
            if (empty($password) || strlen($password) == 0) {
                $errors[] = "Password is required";
            }
            http_response_code(400);
            echo "Error: " . implode(", ", $errors) . ".";
        }
    }