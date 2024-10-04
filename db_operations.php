<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Configuración de la base de datos
$config = json_decode(file_get_contents('config.json'), true);

function executeSingleQuery($host, $user, $pass, $dbname, $query) {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        return ["error" => "Error conectando a $dbname: " . $conn->connect_error];
    }
    
    try {
        $result = $conn->query($query);
        if ($result === TRUE) {
            return [
                "database" => $dbname,
                "query" => $query,
                "result" => "Query ejecutado con éxito. Filas afectadas: " . $conn->affected_rows
            ];
        } elseif ($result instanceof mysqli_result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            return [
                "database" => $dbname,
                "query" => $query,
                "result" => [
                    "message" => "Query ejecutado con éxito. Filas devueltas: " . count($rows),
                    "rows" => $rows
                ]
            ];
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        return [
            "database" => $dbname,
            "query" => $query,
            "result" => ["error" => "Error en Query: " . $e->getMessage()]
        ];
    } finally {
        $conn->close();
    }
}

function getDatabases($host, $user, $pass) {
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        return ["Error: " . $conn->connect_error];
    }
    
    $result = $conn->query("SHOW DATABASES");
    $databases = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if($row["Database"] != "information_schema" && $row["Database"] != "mysql" && $row["Database"] != "performance_schema" && $row["Database"] != "sys")
            {
                $databases[] = $row["Database"];
            }
        }
    }
    $conn->close();
    return $databases;
}

function saveResultToSQLite($result, $dbname = 'query_results.sqlite') {
    try {
        $db = new SQLite3($dbname);
        
        // Habilitar excepciones
        $db->enableExceptions(true);
        
        // Crear tabla si no existe
        $db->exec('CREATE TABLE IF NOT EXISTS query_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp TEXT,
            result TEXT
        )');
        
        $stmt = $db->prepare('INSERT INTO query_results (timestamp, result) VALUES (:timestamp, :result)');
        $stmt->bindValue(':timestamp', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':result', json_encode($result), SQLITE3_TEXT);
        $stmt->execute();
        
        $db->close();
        return $dbname;
    } catch (Exception $e) {
        error_log("Error al guardar en SQLite: " . $e->getMessage());
        return false;
    }
}

function getLogs($page, $perPage) {
    $dbname = 'query_results.sqlite';
    $db = new SQLite3($dbname);
    
    $offset = ($page - 1) * $perPage;
    
    $totalRows = $db->querySingle("SELECT COUNT(*) FROM query_results");
    $totalPages = ceil($totalRows / $perPage);

    $result = $db->query("SELECT * FROM query_results ORDER BY id DESC LIMIT $perPage OFFSET $offset");
    
    $logs = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $logs[] = $row;
    }
    
    $db->close();
    
    return [
        'logs' => $logs,
        'totalPages' => $totalPages
    ];
}

function saveQuery($name, $content) {
    $dbname = 'query_results.sqlite';
    $db = new SQLite3($dbname);
    
    $db->exec('CREATE TABLE IF NOT EXISTS saved_queries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE,
        content TEXT
    )');
    
    $stmt = $db->prepare('INSERT OR REPLACE INTO saved_queries (name, content) VALUES (:name, :content)');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $db->close();
    return $result !== false;
}

function getSavedQueries() {
    $dbname = 'query_results.sqlite';
    $db = new SQLite3($dbname);
    
    $result = $db->query('SELECT name, content FROM saved_queries ORDER BY name');
    
    $queries = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $queries[$row['name']] = $row['content'];
    }
    
    $db->close();
    return $queries;
}

// Manejar las solicitudes AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET") {
    header('Content-Type: application/json');
    $action = $_REQUEST['action'] ?? '';
    
    error_log("Acción recibida: " . $action);
    error_log("Método de solicitud: " . $_SERVER["REQUEST_METHOD"]);
    error_log("Datos GET: " . print_r($_GET, true));
    error_log("Datos POST: " . print_r($_POST, true));
    
    switch ($action) {
        case 'getConnections':
            echo json_encode(array_keys($config));
            break;
        
        case 'getDatabases':
            $selectedConnection = $_POST['connection'];
            $databases = getDatabases(
                $config[$selectedConnection]['host'],
                $config[$selectedConnection]['user'],
                $config[$selectedConnection]['pass']
            );
            echo json_encode($databases);
            break;
        
        case 'executeSingleQuery':
            $selectedConnection = $_POST['connection'];
            $selectedDatabase = $_POST['database'];
            $query = $_POST['query'];
            
            $result = executeSingleQuery(
                $config[$selectedConnection]['host'],
                $config[$selectedConnection]['user'],
                $config[$selectedConnection]['pass'],
                $selectedDatabase,
                $query
            );
            
            $filename = saveResultToSQLite($result);
            if ($filename === false) {
                echo json_encode(['error' => 'No se pudo guardar el resultado en SQLite', 'result' => $result]);
            } else {
                echo json_encode(['result' => $result, 'logFile' => $filename]);
            }
            break;

        case 'getLogs':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $perPage = isset($_GET['perPage']) ? intval($_GET['perPage']) : 10;
            $logs = getLogs($page, $perPage);
            echo json_encode($logs);
            break;

        case 'saveQuery':
            $name = $_POST['name'];
            $content = $_POST['content'];
            $success = saveQuery($name, $content);
            echo json_encode(['success' => $success]);
            break;
        
        case 'getSavedQueries':
            echo json_encode(getSavedQueries());
            break;

        default:
            echo json_encode(["error" => "Acción no reconocida"]);
            break;
    }
    exit;
}