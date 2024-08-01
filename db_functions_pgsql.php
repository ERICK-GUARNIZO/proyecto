<?php
include 'db_config.php';

function connect_pgsql($db) {
    $dsn = "pgsql:host={$db['host']};dbname={$db['dbname']}";
    try {
        $pdo = new PDO($dsn, $db['username'], $db['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function getTables_pgsql($pdo) {
    $query = "SELECT table_name FROM information_schema.tables WHERE table_schema='public'";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getColumns_pgsql($pdo, $table) {
    $query = "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}'";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getData_pgsql($pdo, $table) {
    try {
        // Verificar si es una vista o una tabla y ajusta el llamado al procedimiento
        if ($table == 'vw_complete_info') {
            $stmt = $pdo->query("SELECT * FROM vw_complete_info");
        } else {
            $stmt = $pdo->query("SELECT * FROM sp_select_{$table}() ORDER BY id");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}


function deleteRecord_pgsql($pdo, $table, $id) {
    try {
        $stmt = $pdo->prepare("SELECT sp_delete_{$table}(:id)");
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        die("Delete failed: " . $e->getMessage());
    }
}

function insertRecord_pgsql($pdo, $table, $data) {
    $columns = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));
    $paramNames = implode(", ", array_map(function($key) { return ":$key"; }, array_keys($data)));
    try {
        $stmt = $pdo->prepare("SELECT sp_create_{$table}($paramNames)");
        $stmt->execute($data);
    } catch (PDOException $e) {
        die("Insert failed: " . $e->getMessage());
    }
}

function updateRecord_pgsql($pdo, $table, $id, $data) {
    $paramNames = array_keys($data);
    $placeholders = implode(", ", array_map(function($key) { return ":$key"; }, $paramNames));
    $data['id'] = $id;
    $placeholders = ":id, " . $placeholders;

    try {
        $stmt = $pdo->prepare("SELECT sp_update_{$table}($placeholders)");
        $stmt->execute($data);
    } catch (PDOException $e) {
        die("Update failed: " . $e->getMessage());
    }
}
?>
