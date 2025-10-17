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
    try {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo 'ERROR : Method not supported (' . $_SERVER['REQUEST_METHOD'] . ')';
            exit();
        }
        $userLanguage = $_POST['language'];
        $logNewUser = $_POST['logNewUser'];
        
        $host = $_ENV['DB_HOST'];
        $db_name = $_ENV['DB_NAME'];
        $db_username = $_ENV['DB_USERNAME'];
        $db_password = $_ENV['DB_PASSWORD'];
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        try {
            $pdo = new PDO($dsn, $db_username, $db_password, $options);
        } catch (PDOException $e) {
            echo json_encode(['Connection failed' => $e->getMessage()]);
            exit();
        }

        // Check for Entry Existence
        $stmt1 = $pdo->prepare('SELECT total_visits FROM visits WHERE language_name = :userLanguage');
        $stmt1->bindParam(':userLanguage', $userLanguage);
        $stmt1->execute();
        $existingTotalVisits = $stmt1->fetchColumn();

        $actions = '';

        if ($existingTotalVisits !== false) {
            // Entry exists, increment the total visits
            $newTotalVisits = $existingTotalVisits + 1;

            // Update the database with the new total visits
            $updateStmt = $pdo->prepare('UPDATE visits SET total_visits = :newTotalVisits WHERE language_name = :userLanguage');
            $updateStmt->bindParam(':newTotalVisits', $newTotalVisits);
            $updateStmt->bindParam(':userLanguage', $userLanguage);
            $updateStmt->execute();

            $actions .= 'update_total_visits';

            if ($logNewUser) {
                // Retrieve the current total unique_visitors
                $stmt2 = $pdo->prepare('SELECT unique_visitors FROM visits WHERE language_name = :userLanguage');
                $stmt2->bindParam(':userLanguage', $userLanguage);
                $stmt2->execute();
                $currentTotalUniqueVisitors = $stmt2->fetchColumn();

                // Increment the total visits
                $newTotalUniqueVisitors = $currentTotalUniqueVisitors + 1;

                // Update the database with the new total unique visitors
                $updateStmt = $pdo->prepare('UPDATE visits SET unique_visitors = :newTotalUniqueVisitors WHERE language_name = :userLanguage');
                $updateStmt->bindParam(':newTotalUniqueVisitors', $newTotalUniqueVisitors);
                $updateStmt->bindParam(':userLanguage', $userLanguage);
                $updateStmt->execute();
                $actions .= ', update_unique_visitors';
            }
        } else {
            // Entry does not exist, insert the entry with userLanguage and totalVisits
            $insertStmt = $pdo->prepare('INSERT INTO visits (language_name, total_visits, unique_visitors) VALUES (:userLanguage, 1, 1)');
            $insertStmt->bindParam(':userLanguage', $userLanguage);
            $insertStmt->execute();

            $actions .= 'insert_language_total_visits_unique_visitors';
        }

        echo json_encode([
            'actions' => $actions,
            'postData' => $userLanguage . ', LogNewUser: ' . $logNewUser,
        ]);
    } catch (Exception $ex) {
        $ex = $ex->getMessage();
        echo json_encode(['$ex' => $ex]);
    }
    exit();