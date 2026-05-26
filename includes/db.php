<?php

// Small database helper functions used across the project.
// They keep page files shorter and make prepared queries easier to reuse.

function db_escape(mysqli $conn, ?string $value): string
{
    return mysqli_real_escape_string($conn, (string)$value);
}

function db_one(mysqli $conn, string $sql): ?array
{
    // Runs a SELECT query where only the first row is needed.
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row ?: null;
}

function db_all(mysqli $conn, string $sql): array
{
    // Runs a SELECT query and returns every row as an array.
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    return $rows;
}

function db_prepared(mysqli $conn, string $sql, string $types = '', array $params = []): mysqli_stmt|false
{
    // Prepared statements are safer when values come from forms or URLs.
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    if ($types !== '' && $params) {
        $bindParams = [$types];
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        mysqli_stmt_bind_param($stmt, ...$bindParams);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    return $stmt;
}

function db_one_prepared(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = db_prepared($conn, $sql, $types, $params);
    if (!$stmt) {
        return null;
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function db_all_prepared(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = db_prepared($conn, $sql, $types, $params);
    if (!$stmt) {
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $rows;
}
