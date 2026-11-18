<?php
/**
 * Desactivar o reactivar una categoría.
 * No se puede desactivar una categoría que aún tiene productos activos.
 */
require_once __DIR__ . '/../includes/init.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('/categorias/index.php');
}
verificar_csrf();

$pdo    = conectar();
$id     = (int) ($_POST['id'] ?? 0);
$accion = limpiar($_POST['accion'] ?? '');

if (!in_array($accion, ['desactivar', 'activar'], true)) {
    flash('danger', 'La acción solicitada no es válida.');
    redirigir('/categorias/index.php');
}

try {
    $st = $pdo->prepare('SELECT nombre FROM categoria WHERE id_categoria = :id');
    $st->execute([':id' => $id]);
    $categoria = $st->fetch();

    if (!$categoria) {
        flash('danger', 'La categoría solicitada no existe.');
    } else {
        $st = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE id_categoria = :id AND estado = 1');
        $st->execute([':id' => $id]);
        $activos = (int) $st->fetchColumn();

        if ($accion === 'desactivar' && $activos > 0) {
            flash('warning', 'No se puede desactivar «' . $categoria['nombre'] . '»: tiene ' . $activos
                . ' producto(s) activo(s). Desactívalos o cámbialos de categoría primero.');
        } else {
            $nuevoEstado = $accion === 'activar' ? 1 : 0;
            $st = $pdo->prepare('UPDATE categoria SET estado = :estado WHERE id_categoria = :id');
            $st->execute([':estado' => $nuevoEstado, ':id' => $id]);
            flash('success', 'Categoría «' . $categoria['nombre'] . '» ' . ($nuevoEstado ? 'reactivada' : 'desactivada') . ' correctamente.');
        }
    }
} catch (PDOException $ex) {
    error_log('Estado categoría: ' . $ex->getMessage());
    flash('danger', 'No se pudo cambiar el estado de la categoría. Inténtalo nuevamente.');
}

redirigir('/categorias/index.php');
