<?php
// ============================================================
//  config.php  — Configuración de la base de datos
//  Edita solo este archivo si cambias credenciales
// ============================================================

define('DB_HOST', 'reseau.proxy.rlwy.net');
define('DB_PORT', '53308'); // importante: puerto
define('DB_USER', 'root');
define('DB_PASS', 'hdfhxfTeOFBhnbCHjpziTLkFbHPTheuo');
define('DB_NAME', 'railway');

// Ruta donde se guardan las fotos en la carpeta uploads del proyecto raíz
define('UPLOADS_DIR', dirname(__DIR__) . '/uploads/fotos/');
define('UPLOADS_URL', '/inventario/uploads/fotos/');

// Tamaño máximo de foto: 5 MB
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['ok' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function cors(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
