<?php
declare(strict_types=1);

$apply = in_array('--apply', $argv, true);
$cfg = require dirname(__DIR__) . '/config/database.php';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']);
$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

$columnsByTable = columnsByTable($pdo);
$updatedRows = 0;
$updatedFields = 0;

foreach ($columnsByTable as $table => $meta) {
    $pk = $meta['pk'];
    $columns = $meta['columns'];
    if ($pk === null || !$columns) {
        continue;
    }

    $where = implode(' OR ', array_map(fn (string $column): string => q($column) . " LIKE '%Ã%' OR " . q($column) . " LIKE '%Â%'", $columns));
    $sql = 'SELECT ' . q($pk) . ', ' . implode(', ', array_map('q', $columns)) . ' FROM ' . q($table) . ' WHERE ' . $where;
    foreach ($pdo->query($sql) as $row) {
        $changes = [];
        foreach ($columns as $column) {
            if ($row[$column] === null) {
                continue;
            }
            $fixed = fixMojibakeText((string) $row[$column]);
            if ($fixed !== (string) $row[$column]) {
                $changes[$column] = $fixed;
            }
        }

        if (!$changes) {
            continue;
        }

        $updatedRows++;
        $updatedFields += count($changes);
        if (!$apply) {
            continue;
        }

        $assignments = implode(', ', array_map(fn (string $column): string => q($column) . ' = :' . $column, array_keys($changes)));
        $stmt = $pdo->prepare('UPDATE ' . q($table) . ' SET ' . $assignments . ' WHERE ' . q($pk) . ' = :_pk');
        foreach ($changes as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->bindValue(':_pk', $row[$pk]);
        $stmt->execute();
    }
}

echo ($apply ? 'Aplicado' : 'Simulado') . ": {$updatedRows} linhas, {$updatedFields} campos.\n";
if (!$apply) {
    echo "Use --apply para gravar as correcoes.\n";
}

function columnsByTable(PDO $pdo): array
{
    $schema = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $pkStmt = $pdo->prepare(
        "SELECT TABLE_NAME, COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = ?
           AND CONSTRAINT_NAME = 'PRIMARY'
         ORDER BY TABLE_NAME, ORDINAL_POSITION"
    );
    $pkStmt->execute([$schema]);

    $primaryKeys = [];
    foreach ($pkStmt as $row) {
        $table = $row['TABLE_NAME'];
        $primaryKeys[$table] ??= [];
        $primaryKeys[$table][] = $row['COLUMN_NAME'];
    }

    $stmt = $pdo->prepare(
        "SELECT c.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE
         FROM information_schema.COLUMNS c
         WHERE c.TABLE_SCHEMA = ?
           AND c.DATA_TYPE IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum')
         ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION"
    );
    $stmt->execute([$schema]);

    $tables = [];
    foreach ($stmt as $row) {
        $table = $row['TABLE_NAME'];
        $pk = count($primaryKeys[$table] ?? []) === 1 ? $primaryKeys[$table][0] : null;
        $tables[$table] ??= ['pk' => $pk, 'columns' => []];
        $tables[$table]['columns'][] = $row['COLUMN_NAME'];
    }

    return $tables;
}

function q(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function fixMojibakeText(string $text): string
{
    if ($text === '' || (!str_contains($text, 'Ã') && !str_contains($text, 'Â'))) {
        return $text;
    }

    $current = $text;
    for ($i = 0; $i < 2; $i++) {
        $converted = @mb_convert_encoding($current, 'ISO-8859-1', 'UTF-8');
        if (!is_string($converted) || $converted === '' || !mb_check_encoding($converted, 'UTF-8')) {
            break;
        }
        if ($converted === $current) {
            break;
        }
        $current = $converted;
        if (!str_contains($current, 'Ã') && !str_contains($current, 'Â')) {
            break;
        }
    }
    return $current;
}
