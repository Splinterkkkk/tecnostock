<?php
/**
 * Inicio de sesión.
 * Verifica el correo y la clave contra la tabla usuario (clave con hash).
 */
require_once __DIR__ . '/includes/init.php';

// Si ya hay sesión, no tiene sentido mostrar el formulario.
if (usuario_actual() !== null) {
    redirigir('/dashboard.php');
}

$errores = [];
$correo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $correo = limpiar($_POST['correo'] ?? '');
    $clave  = (string) ($_POST['clave'] ?? '');

    // Validación del servidor (se repite la de JavaScript)
    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Ingresa un correo válido.';
    }
    if ($clave === '') {
        $errores['clave'] = 'Ingresa tu clave.';
    }

    if (!$errores) {
        try {
            $st = conectar()->prepare(
                'SELECT id_usuario, nombre, correo, clave, estado FROM usuario WHERE correo = :correo LIMIT 1'
            );
            $st->execute([':correo' => $correo]);
            $usuario = $st->fetch();

            if ($usuario && (int) $usuario['estado'] === 1 && password_verify($clave, $usuario['clave'])) {
                session_regenerate_id(true);   // evita fijación de sesión
                $_SESSION['usuario'] = [
                    'id'     => (int) $usuario['id_usuario'],
                    'nombre' => $usuario['nombre'],
                    'correo' => $usuario['correo'],
                ];
                redirigir('/dashboard.php');
            }

            // Mensaje genérico: no revela si el correo existe.
            $errores['general'] = 'Correo o clave incorrectos, o el usuario está inactivo.';
        } catch (PDOException $ex) {
            error_log('Login: ' . $ex->getMessage());
            $errores['general'] = 'Ocurrió un error en el sistema. Inténtalo nuevamente.';
        }
    }
}

$titulo       = 'Iniciar sesión';
$mostrar_menu = false;
require __DIR__ . '/includes/header.php';
?>

<div class="login-caja">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-1">📦 <?= e(APP_NOMBRE) ?></h1>
            <p class="text-center text-muted mb-4">Ingresa para administrar el inventario</p>

            <?php if (!empty($errores['general'])): ?>
                <div class="alert alert-danger"><?= e($errores['general']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/index.php" class="needs-validation" novalidate>
                <?= csrf_campo() ?>

                <div class="mb-3">
                    <label for="correo" class="form-label">Correo</label>
                    <input type="email" id="correo" name="correo" class="form-control <?= clase_error($errores, 'correo') ?>"
                           value="<?= e($correo) ?>" required autofocus autocomplete="username"
                           data-regla="email" data-etiqueta="El correo">
                    <?= bloque_error($errores, 'correo') ?>
                </div>

                <div class="mb-4">
                    <label for="clave" class="form-label">Clave</label>
                    <input type="password" id="clave" name="clave" class="form-control <?= clase_error($errores, 'clave') ?>"
                           required autocomplete="current-password"
                           data-regla="texto" data-etiqueta="La clave">
                    <?= bloque_error($errores, 'clave') ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
