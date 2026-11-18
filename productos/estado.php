<?php
/**
 * Desactivar o reactivar un producto (regla 5: los productos no se eliminan).
 * Solo acepta POST con token CSRF.
 */
require_once __DIR__ . '/../includes/init.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('/productos/index.php');
}
verificar_csrf();

$pdo    = conectar();
$id     = (int) ($_POST['id'] ?? 0);
$accion = limpiar($_POST['accion'] ?? '');

if (!in_array($accion, ['desactivar', 'activar'], true)) {
    flash('danger', 'La acción solicitada no es válida.');
    redirigir('/productos/index.php');
}

try {
    $st = $pdo->prepare(
        'SELECT p.nombre, p.estado, c.estado AS estado_categoria
         FROM producto p INNER JOIN categoria c ON c.id_categoria = p.id_categoria
         WHERE p.id_producto = :id'
    );
    $st->execute([':id' => $id]);
    $producto = $st->fetch();

    if (!$producto) {
        flash('danger', 'El producto solicitado no existe.');
    } elseif ($accion === 'activar' && (int) $producto['estado_categoria'] !== 1) {
        flash('warning', 'No se puede reactivar «' . $producto['nombre'] . '» porque su categoría está inactiva. Reactiva primero la categoría.');
    } else {
        $nuevoEstado = $accion === 'activar' ? 1 : 0;
        $st = $pdo->prepare('UPDATE producto SET estado = :estado WHERE id_producto = :id');
        $st->execute([':estado' => $nuevoEstado, ':id' => $id]);
        flash('success', 'Producto «' . $producto['nombre'] . '» ' . ($nuevoEstado ? 'reactivado' : 'desactivado') . ' correctamente.');
    }
} catch (PDOException $ex) {
    error_log('Estado producto: ' . $ex->getMessage());
    flash('danger', 'No se pudo cambiar el estado del producto. Inténtalo nuevamente.');
}

redirigir('/productos/index.php');
