<?php
include 'db_config.php';

function connect_sqlsrv($db) {
    // Asegúrate de que incluyes el usuario y la contraseña en el DSN.
    $dsn = "sqlsrv:Server={$db['host']};Database={$db['dbname']}";
    try {
        $pdo = new PDO($dsn, $db['username'], $db['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());  
    }
}
function getTables_sqlsrv($pdo) {
    $tablesQuery = "SELECT table_name FROM information_schema.tables WHERE table_type='BASE TABLE' AND table_name NOT IN ('sysdiagrams')"; // Excluir sysdiagrams; 
    $viewsQuery = "SELECT table_name FROM information_schema.views";
    $tablesStmt = $pdo->query($tablesQuery);
    $viewsStmt = $pdo->query($viewsQuery);
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    $views = $viewsStmt->fetchAll(PDO::FETCH_COLUMN);
    return array_merge($tables, $views);
}


function getColumns_sqlsrv($pdo, $table) {
    try {
        // Consulta para obtener los nombres de las columnas de la tabla especificada
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table");
        $stmt->execute([':table' => $table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}



function getData_sqlsrv($pdo, $table) {
    try {
        $stmt = $pdo->query("EXEC sp_select_{$table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

function getViews_sqlsrv($pdo) {
    $query = "SELECT table_name FROM information_schema.views";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}



function deleteRecord_sqlsrv($pdo, $table, $id) {
    try {
        $stmt = $pdo->prepare("EXEC sp_delete_{$table} :id");
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        die("Delete failed: " . $e->getMessage());
    }
}

function insertRecord_sqlsrv($pdo, $table, $data) {
    $columns = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));
    try {
        $stmt = $pdo->prepare("EXEC sp_create_{$table} $placeholders");
        $stmt->execute($data);
    } catch (PDOException $e) {
        die("Insert failed: " . $e->getMessage());
    }
}
function updateRecord_sqlsrv($pdo, $table, $id, $data) {
    // Construcción de la consulta de parámetros
    $params = [];
    foreach ($data as $column => $value) {
        $params[":$column"] = $value;
    }
    $params[':id'] = $id;

    // Construcción de la lista de parámetros del procedimiento almacenado
    $paramPlaceholders = implode(', ', array_map(function($key) {
        return "@{$key} = :{$key}";
    }, array_keys($data)));

    try {
        // Llamada al procedimiento almacenado
        $stmt = $pdo->prepare("EXEC sp_update_{$table} @id = :id, $paramPlaceholders");
        $stmt->execute($params);
    } catch (PDOException $e) {
        die("Update failed: " . $e->getMessage());
    }
}
?>
