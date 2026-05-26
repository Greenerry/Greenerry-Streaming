<?php
function ensure_email_verification_table(mysqli $conn): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    $sql = "CREATE TABLE IF NOT EXISTS verificacao_email (
        idVerificacaoEmail INT AUTO_INCREMENT PRIMARY KEY,
        idCliente INT NOT NULL,
        hash_token VARCHAR(255) NOT NULL UNIQUE,
        expira_em DATETIME NOT NULL,
        usado_em DATETIME NULL,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_verificacao_email_cliente (idCliente),
        CONSTRAINT fk_verificacao_email_cliente
            FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
            ON DELETE CASCADE
    )";

    $ready = mysqli_query($conn, $sql) !== false;
    return $ready;
}

function ensure_password_reset_table(mysqli $conn): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    $sql = "CREATE TABLE IF NOT EXISTS recuperacao_password (
        idRecuperacaoPassword INT AUTO_INCREMENT PRIMARY KEY,
        idCliente INT NOT NULL,
        hash_token VARCHAR(255) NOT NULL UNIQUE,
        expira_em DATETIME NOT NULL,
        usado_em DATETIME NULL,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_recuperacao_password_cliente (idCliente),
        CONSTRAINT fk_recuperacao_password_cliente
            FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
            ON DELETE CASCADE
    )";

    $ready = mysqli_query($conn, $sql) !== false;
    return $ready;
}

function create_email_verification(mysqli $conn, int $userId): ?string
{
    if (!ensure_email_verification_table($conn)) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    db_prepared($conn, "DELETE FROM verificacao_email WHERE idCliente = ? AND usado_em IS NULL", 'i', [$userId]);

    $saved = db_prepared(
        $conn,
        "INSERT INTO verificacao_email (idCliente, hash_token, expira_em)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))",
        'is',
        [$userId, $hash]
    );

    return $saved ? $token : null;
}

function create_password_reset(mysqli $conn, int $userId): ?string
{
    if (!ensure_password_reset_table($conn)) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    db_prepared($conn, "DELETE FROM recuperacao_password WHERE idCliente = ? AND usado_em IS NULL", 'i', [$userId]);

    $saved = db_prepared(
        $conn,
        "INSERT INTO recuperacao_password (idCliente, hash_token, expira_em)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))",
        'is',
        [$userId, $hash]
    );

    return $saved ? $token : null;
}

function password_reset_user(mysqli $conn, string $token): ?array
{
    $token = trim($token);
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token) || !ensure_password_reset_table($conn)) {
        return null;
    }

    $hash = hash('sha256', $token);
    return db_one_prepared(
        $conn,
        "SELECT rp.idRecuperacaoPassword, rp.idCliente, rp.expira_em, rp.usado_em,
                rp.expira_em < NOW() AS expirado,
                c.nome, c.email, c.estado
         FROM recuperacao_password rp
         JOIN cliente c ON c.idCliente = rp.idCliente
         WHERE rp.hash_token = ?
         LIMIT 1",
        's',
        [$hash]
    );
}

function complete_password_reset(mysqli $conn, string $token, string $newPassword): string
{
    $row = password_reset_user($conn, $token);
    if (!$row) {
        return 'invalid';
    }

    if (!empty($row['usado_em']) || (int)($row['expirado'] ?? 0) === 1) {
        return 'expired';
    }

    if ((string)$row['estado'] !== 'ativo') {
        return 'inactive';
    }

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $userId = (int)$row['idCliente'];
    $resetId = (int)$row['idRecuperacaoPassword'];

    mysqli_begin_transaction($conn);
    try {
        if (!db_prepared($conn, "UPDATE cliente SET palavra_passe = ? WHERE idCliente = ?", 'si', [$passwordHash, $userId])) {
            throw new RuntimeException('password update failed');
        }
        if (!db_prepared($conn, "UPDATE recuperacao_password SET usado_em = NOW() WHERE idRecuperacaoPassword = ?", 'i', [$resetId])) {
            throw new RuntimeException('reset token update failed');
        }
        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return 'invalid';
    }

    send_password_changed_email($row);
    return 'ok';
}

function verify_email_token(mysqli $conn, string $token): string
{
    $token = trim($token);
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
        return 'invalid';
    }

    if (!ensure_email_verification_table($conn)) {
        return 'invalid';
    }

    $hash = hash('sha256', $token);
    $row = db_one_prepared(
        $conn,
        "SELECT ve.idVerificacaoEmail, ve.idCliente, ve.usado_em, ve.expira_em,
                ve.expira_em < NOW() AS expirado,
                c.estado
         FROM verificacao_email ve
         JOIN cliente c ON c.idCliente = ve.idCliente
         WHERE ve.hash_token = ?
         LIMIT 1",
        's',
        [$hash]
    );

    if (!$row) {
        return 'invalid';
    }

    if (!empty($row['usado_em']) || (int)($row['expirado'] ?? 0) === 1) {
        return 'expired';
    }

    $userId = (int)$row['idCliente'];
    $verificationId = (int)$row['idVerificacaoEmail'];

    if ((string)$row['estado'] === 'inativo') {
        db_prepared($conn, "UPDATE cliente SET estado = 'ativo' WHERE idCliente = ?", 'i', [$userId]);
    }
    db_prepared($conn, "UPDATE verificacao_email SET usado_em = NOW() WHERE idVerificacaoEmail = ?", 'i', [$verificationId]);

    $user = db_one_prepared($conn, "SELECT * FROM cliente WHERE idCliente = ? LIMIT 1", 'i', [$userId]);
    if ($user) {
        send_welcome_email($user);
    }

    return 'ok';
}
