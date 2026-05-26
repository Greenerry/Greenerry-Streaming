<?php
function site_setting(string $key, string $default = ''): string
{
    global $conn;

    static $settings = null;
    if ($key === '__reload__') {
        $settings = null;
        return '';
    }

    if ($settings === null) {
        $settings = [];
        $table = mysqli_query($conn, "SHOW TABLES LIKE 'configuracao_site'");
        if ($table && mysqli_num_rows($table) > 0) {
            $rows = db_all($conn, "SELECT chave_configuracao, valor_configuracao FROM configuracao_site");
            foreach ($rows as $row) {
                $settings[(string)$row['chave_configuracao']] = (string)$row['valor_configuracao'];
            }
        }
    }

    return $settings[$key] ?? $default;
}

function reload_site_settings(): void
{
    site_setting('__reload__');
}
