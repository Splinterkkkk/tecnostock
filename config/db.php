<?php
/**
 * Conexión a MySQL usando PDO.
 */
require_once __DIR__ . '/config.php';

const DB_HOST    = 'localhost';
const DB_NAME    = 'tecnostock';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

/**
 * Devuelve la conexión PDO 
 */
function conectar(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         
            PDO::ATTR_EMULATE_PREPARES   => false,                    
        ]);
    } catch (PDOException $e) {
        error_log('Error de conexión a la base de datos: ' . $e->getMessage());
        http_response_code(500);
        exit('No se pudo conectar a la base de datos. Verifica que MySQL esté iniciado en XAMPP.');
    }

    return $pdo;
}
