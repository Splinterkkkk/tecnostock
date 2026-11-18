<?php
/**
 * Manejo de la sesión del usuario (inicio de sesión).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path'     => '/',
        'httponly' => true,    
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Devuelve los datos del usuario que inició sesión, o null si no hay sesión. */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** Protege una página: si no hay sesión, envía al inicio de sesión. */
function requerir_login(): void
{
    if (usuario_actual() === null) {
        redirigir('/index.php');
    }
}
