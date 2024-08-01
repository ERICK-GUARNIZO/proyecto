<?php
include 'db_config.php';

function connect_mysql($db) {
    $dsn = "mysql:host={$db['host']};dbname={$db['dbname']}";
    try {
        $pdo = new PDO($dsn, $db['username'], $db['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function getTables_mysql($pdo) {
    $query = "SHOW TABLES";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getColumns_mysql($pdo, $table) {
    $query = "DESCRIBE {$table}";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getData_mysql($pdo, $table) {
    try {
        $stmt = $pdo->query("CALL sp_select_{$table}()");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

function deleteRecord_mysql($pdo, $table, $id) {
    try {
        $stmt = $pdo->prepare("CALL sp_delete_{$table}(:id)");
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        die("Delete failed: " . $e->getMessage());
    }
}

function insertRecord_mysql($pdo, $table, $data) {
    $columns = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));
    try {
        $stmt = $pdo->prepare("CALL sp_create_{$table}($placeholders)");
        $stmt->execute($data);
    } catch (PDOException $e) {
        die("Insert failed: " . $e->getMessage());
    }
}

function updateRecord_mysql($pdo, $table, $id, $data) {
    // Aquí construimos los parámetros para el procedimiento almacenado
    $params = [];
    foreach ($data as $column => $value) {
        $params[":p_{$column}"] = $value;
    }
    $params[':p_id'] = $id;

    // Preparar y ejecutar el procedimiento almacenado
    $set_clause = "";
    foreach ($data as $column => $value) {
        $set_clause .= ":p_{$column}, ";
    }
    $set_clause = rtrim($set_clause, ', ');

    try {
        $stmt = $pdo->prepare("CALL sp_update_{$table}(:p_id, $set_clause)");
        $stmt->execute($params);
    } catch (PDOException $e) {
        die("Update failed: " . $e->getMessage());
    }
}
?>
