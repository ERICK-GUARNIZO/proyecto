<?php
include 'db_config.php';

$db_type = isset($_POST['db_type']) ? $_POST['db_type'] : (isset($_GET['db_type']) ? $_GET['db_type'] : '');

switch ($db_type) {
    case 'mysql':
        include 'db_functions_mysql.php';
        $pdo = connect_mysql($dbs[$db_type]);
        $getTables = 'getTables_mysql';
        $getColumns = 'getColumns_mysql';
        $getData = 'getData_mysql';
        $deleteRecord = 'deleteRecord_mysql';
        $insertRecord = 'insertRecord_mysql';
        $updateRecord = 'updateRecord_mysql';
        break;
    case 'pgsql':
        include 'db_functions_pgsql.php';
        $pdo = connect_pgsql($dbs[$db_type]);
        $getTables = 'getTables_pgsql';
        $getColumns = 'getColumns_pgsql';
        $getData = 'getData_pgsql';
        $deleteRecord = 'deleteRecord_pgsql';
        $insertRecord = 'insertRecord_pgsql';
        $updateRecord = 'updateRecord_pgsql';
        break;
    case 'sqlsrv':
    case 'sqlsrv2':
        include 'db_functions_sqlsrv.php';
        $pdo = connect_sqlsrv($dbs[$db_type]);
        $getTables = 'getTables_sqlsrv';
        $getColumns = 'getColumns_sqlsrv';
        $getData = 'getData_sqlsrv';
        $getView = 'getViewData_sqlsrv';
        $deleteRecord = 'deleteRecord_sqlsrv';
        $insertRecord = 'insertRecord_sqlsrv';
        $updateRecord = 'updateRecord_sqlsrv';
        break;
    default:
        $pdo = null;
        $getTables = $getColumns = $getData = $deleteRecord = $insertRecord = $updateRecord = function() {
            return [];
        };
}

$tables = $pdo ? $getTables($pdo) : [];

$selected_table = isset($_POST['table']) ? $_POST['table'] : (isset($_GET['table']) ? $_GET['table'] : '');
$data = $selected_table ? $getData($pdo, $selected_table) : [];

$showActions = true;
$visibleColumns = [];

if ($selected_table == 'vw_complete_info') {
    $showActions = false;
    $visibleColumns = ['provincia', 'canton', 'parroquia']; // Solo las columnas que quieres mostrar
}

if (isset($_POST['delete'])) {
    $deleteRecord($pdo, $selected_table, $_POST['delete']);
    header("Location: index.php?db_type={$db_type}&table={$selected_table}");
    exit;
}

if (isset($_POST['edit'])) {
    $id = $_POST['edit'];
    $record = null;
    $allData = $getData($pdo, $selected_table);
    foreach ($allData as $row) {
        if ($row['id'] == $id) {
            $record = $row;
            break;
        }
    }

    if ($record) {
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="db_type" value="' . $db_type . '">';
        echo '<input type="hidden" name="table" value="' . $selected_table . '">';
        echo '<input type="hidden" name="id" value="' . $id . '">';
        foreach ($record as $column => $value) {
            echo '<label for="' . $column . '">' . $column . '</label>';
            echo '<input type="text" name="' . $column . '" value="' . $value . '" class="form-control">';
        }
        echo '<button type="submit" name="update" class="btn btn-primary mt-2">Update</button>';
        echo '</form>';
    } else {
        echo "Record not found.";
    }
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $update_data = $_POST;
    unset($update_data['db_type'], $update_data['table'], $update_data['id'], $update_data['update']);
    
    $updateRecord($pdo, $selected_table, $id, $update_data);

    header("Location: index.php?db_type={$db_type}&table={$selected_table}");
    exit;
}

if (isset($_POST['add'])) {
    $columns = $getColumns($pdo, $selected_table);

    echo '<form method="post" action="">';
    echo '<input type="hidden" name="db_type" value="' . $db_type . '">';
    echo '<input type="hidden" name="table" value="' . $selected_table . '">';
    foreach ($columns as $column) {
        if ($column != 'id') {
            echo '<label for="' . $column . '">' . $column . '</label>';
            echo '<input type="text" name="' . $column . '" class="form-control">';
        }
    }
    echo '<button type="submit" name="insert" class="btn btn-success mt-2">Insert</button>';
    echo '</form>';
}

if (isset($_POST['insert'])) {
    $insert_data = $_POST;
    unset($insert_data['db_type'], $insert_data['table'], $insert_data['insert']);
    
    $insertRecord($pdo, $selected_table, $insert_data);

    header("Location: index.php?db_type={$db_type}&table={$selected_table}");
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Manager</title>
    <!-- Enlace a Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Personalización del estilo */
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }

        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            margin-top: 20px;
        }

        /* Estilo para el input de búsqueda */
        #searchInput {
            margin-bottom: 10px;
            padding: 8px;
            width: 100%;
            max-width: 300px;
            margin-right: auto;
            margin-left: auto;
            display: block;
        }

        /* Estilo personalizado para los botones */
        .btn {
            margin-right: 5px;
        }
    </style>
    <script>
    function debounce(func, delay) {
        let debounceTimer;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => func.apply(context, args), delay);
        };
    }

    // Función de filtrado de la tabla
    function filterTable() {
        let input = document.getElementById("searchInput");
        let filter = input.value.toLowerCase();
        let table = document.getElementById("dataTable");
        let tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            let td = tr[i].getElementsByTagName("td");
            let showRow = false;

            for (let j = 0; j < td.length; j++) {
                if (td[j] && td[j].innerText.toLowerCase().indexOf(filter) > -1) {
                    showRow = true;
                    break;
                }
            }

            tr[i].style.display = showRow ? "" : "none";
        }
    }

    // Envolver la función de filtrado con debounce
    const optimizedFilter = debounce(filterTable, 300);

    // Asignar la función optimizada al evento onkeyup
    window.onload = function() {
        document.getElementById("searchInput").onkeyup = optimizedFilter;
    };
    </script>
</head>
<body>
    <h1>Database Manager</h1>
    <h2>Manejo de 4 tipo de base de datos</h2>

    <form method="post" action="">
        <div class="form-group">
            <label for="db_type">Select Database:</label>
            <select name="db_type" id="db_type" class="form-control" onchange="this.form.submit()">
                <option value="" disabled <?= $db_type == '' ? 'selected' : '' ?>>SELECCIONAR BASE DE DATOS</option>
                <option value="mysql" <?= $db_type == 'mysql' ? 'selected' : '' ?>>MySQL</option>
                <option value="pgsql" <?= $db_type == 'pgsql' ? 'selected' : '' ?>>PostgreSQL</option>
                <option value="sqlsrv" <?= $db_type == 'sqlsrv' ? 'selected' : '' ?>>SQL Server</option>
                <option value="sqlsrv2" <?= $db_type == 'sqlsrv2' ? 'selected' : '' ?>>Oracle DB</option>
            </select>
        </div>
    </form>

    <form method="post" action="">
        <input type="hidden" name="db_type" value="<?= $db_type ?>">
        <div class="form-group">
            <label for="table">Select Table:</label>
            <select name="table" id="table" class="form-control" onchange="this.form.submit()">
                <option value="" disabled <?= $selected_table == '' ? 'selected' : '' ?>>SELECCIONE TABLA O VISTA</option>
                <?php foreach ($tables as $table): ?>
                    <option value="<?= $table ?>" <?= $selected_table == $table ? 'selected' : '' ?>><?= $table ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($selected_table): ?>
        <div class="text text-start">
            <?php if ($showActions): ?>
                <form method="post" action="">
                    <input type="hidden" name="db_type" value="<?= $db_type ?>">
                    <input type="hidden" name="table" value="<?= $selected_table ?>">
                    <button type="submit" name="add" class="btn btn-success">Add New Record</button>
                </form>
            <?php endif; ?>
        </div>

        <input type="text" id="searchInput" class="form-control" placeholder="Search for text..">
        
        <table id="dataTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <?php if (!empty($data)): ?>
                        <?php foreach (array_keys($data[0]) as $column): ?>
                            <?php if (empty($visibleColumns) || in_array($column, $visibleColumns)): ?>
                                <th><?= $column ?></th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($showActions): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($row as $column => $value): ?>
                            <?php if (empty($visibleColumns) || in_array($column, $visibleColumns)): ?>
                                <td><?= $value ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($showActions): ?>
                            <td>
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="db_type" value="<?= $db_type ?>">
                                    <input type="hidden" name="table" value="<?= $selected_table ?>">
                                    <button type="submit" name="edit" value="<?= $row['id'] ?>" class="btn btn-warning">Edit</button>
                                </form>
                                <form method="post" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    <input type="hidden" name="db_type" value="<?= $db_type ?>">
                                    <input type="hidden" name="table" value="<?= $selected_table ?>">
                                    <button type="submit" name="delete" value="<?= $row['id'] ?>" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <!-- Enlace a Bootstrap JS y Popper.js -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
