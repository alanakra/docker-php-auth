<?php
    require_once __DIR__ . '/vendor/autoload.php';
    header("Access-Control-Allow-Origin: *");
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
                echo "Erreur : Ce nom d'utilisateur existe déjà.";
            } else {
                $mdp_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users_2 (username, hash_password) VALUES (:uname, :upassword)");
                $stmt->execute([
                    ':uname' => $name,
                    ':upassword' => $mdp_hash
                ]);

                echo "Utilisateur créé avec succès.";
            }
        } else {
            $errors = [];
            if (empty($name) || strlen($name) == 0) {
                $errors[] = "Le nom d'utilisateur est requis";
            }
            if (empty($password) || strlen($password) == 0) {
                $errors[] = "Le mot de passe est requis";
            }
            http_response_code(400);
            echo "Erreur : " . implode(", ", $errors) . ".";
        }
    }