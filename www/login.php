<?php
    require_once __DIR__ . '/vendor/autoload.php';
    use Firebase\JWT\JWT;
    
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json");
    
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
        http_response_code(500);
        echo json_encode(["error" => "Erreur de connexion à la base de données : " . $e->getMessage()]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['uname'] ?? '');
        $password = $_POST['upassword'] ?? '';
        
        if (!empty($name) && !empty($password) && strlen($name) > 0 && strlen($password) > 0) {
            $stmt = $pdo->prepare("SELECT id, username, hash_password FROM users_2 WHERE username = :uname");
            $stmt->execute([':uname' => $name]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['hash_password'])) {
                // Générer un token JWT
                $jwt_secret = $_ENV['JWT_SECRET'];
                
                $payload = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'iat' => time(),
                    'exp' => time() + (24 * 60 * 60) // 24 hoeurs
                ];
                
                $jwt = JWT::encode($payload, $jwt_secret, 'HS256');
                echo json_encode([
                    "success" => true,
                    "message" => "Successful login",
                    "user" => [
                        "id" => $user['id'],
                        "username" => $user['username']
                    ],
                    "token" => $jwt,
                    "expires_in" => 24 * 60 * 60, // 24 hours in seconds
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "error" => "uncorrect username or password"
                ]);
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
            echo json_encode([
                "success" => false,
                "error" => "Erreur : " . implode(", ", $errors) . "."
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "error" => "Unautorized method. Use POST."
        ]);
    }