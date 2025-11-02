<?php
    // TODO: Include CORS Restriction b4 flight
    require_once __DIR__ . '/vendor/autoload.php';
    use Firebase\JWT\JWT;
    
    // CORS Configuration for cross domain dev w Client URL list
    $allowedOrigins = [
        'https://demo-register-client.local:5173',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:3000',
        'http://127.0.0.1:3000'
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: https://demo-register-client.local:5173"); // Fallback during dev
    }
    
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
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
                $jwt_secret = $_ENV['JWT_SECRET'];
                
                $payload = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'iat' => time(),
                    'exp' => time() + (24 * 60 * 60) // 24 hoeurs
                ];
                
                $jwt = JWT::encode($payload, $jwt_secret, 'HS256');
                
                // Secured cookie configuration with Partitioned attribute for cross-site cookies
                // Note: Partitioned is required for cross-site cookies (SameSite=None) in modern browsers
                // The cookie is accessible in JavaScript via document.cookie (HttpOnly not set)
                $expires = time() + (24 * 60 * 60);
                
                // For cross-site cookies (different domains), we need:
                // - Secure (required with SameSite=None)
                // - SameSite=None (for cross-site)
                // - Partitioned (required by modern browsers for SameSite=None cross-site cookies)
                // Note: Domain is NOT set - browsers handle cross-site cookies automatically
                // The cookie will be available to the client domain that made the request
                $cookieHeader = sprintf(
                    'auth_token=%s; Expires=%s; Path=/; Secure; SameSite=None; Partitioned',
                    $jwt,
                    gmdate('D, d M Y H:i:s \G\M\T', $expires)
                );
                header("Set-Cookie: $cookieHeader");
                
                echo json_encode([
                    "success" => true,
                    "message" => "Successful login",
                    "user" => [
                        "id" => $user['id'],
                        "username" => $user['username']
                    ],
                    "jwt" => $jwt,
                    "cookie_header" => $cookieHeader,
                    "expires_in" => 24 * 60 * 60, // 24 hours in seconds
                    "cookie_set" => true
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "error" => "incorrect username or password"
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