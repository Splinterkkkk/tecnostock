<?php
/**
 * Edición de un producto: precio, descripción, categoría y cantidades.
 * Si cambia el stock actual, se registra un movimiento de ajuste automático.
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/inventario.php';
requerir_login();

$pdo = conectar();

// Producto a editar
$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM producto WHERE id_producto = :id');
$st->execute([':id' => $id]);
$producto = $st->fetch();

if (!$producto) {
    flash('danger', 'El producto solicitado no existe.');
    redirigir('/productos/index.php');
}
if ((int) $producto['estado'] !== 1) {
    flash('warning', 'El producto está desactivado. Reactívalo para poder editarlo.');
    redirigir('/productos/index.php');
}

$categorias = categorias_activas($pdo);
// Si la categoría del producto está inactiva, se agrega a la lista para no perderla.
$idsLista = array_column($categorias, 'id_categoria');
if (!in_array($producto['id_categoria'], $idsLista)) {
    $st = $pdo->prepare('SELECT id_categoria, nombre FROM categoria WHERE id_categoria = :id');
    $st->execute([':id' => $producto['id_categoria']]);
    $categorias[] = $st->fetch();
}

$errores = [];
$datos   = [
    'codigo'       => $producto['codigo'],
    'nombre'       => $producto['nombre'],
    'descripcion'  => (string) $producto['descripcion'],
    'precio'       => $producto['precio'],
    'stock_actual' => $producto['stock_actual'],
    'stock_minimo' => $producto['stock_minimo'],
    'id_categoria' => $producto['id_categoria'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    [$datos, $errores] = validar_producto($pdo, $_POST, $id, (int) $producto['id_categoria']);
    $datos['codigo'] = $producto['codigo'];     // el código no se modifica

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare(
                'UPDATE producto
                 SET nombre = :nombre, descripcion = :descripcion, precio = :precio,
                     stock_minimo = :minimo, id_categoria = :categoria
                 WHERE id_producto = :id'
            );
            $st->execute([
                ':nombre'      => $datos['nombre'],
                ':descripcion' => $datos['descripcion'] === '' ? null : $datos['descripcion'],
                ':precio'      => $datos['precio'],
                ':minimo'      => (int) $datos['stock_minimo'],
                ':categoria'   => (int) $datos['id_categoria'],
                ':id'          => $id,
            ]);

            // Si cambió el stock, la diferencia se registra como movimiento de ajuste.
            $st = $pdo->prepare('SELECT stock_actual FROM producto WHERE id_producto = :id');
            $st->execute([':id' => $id]);
            $diferencia = (int) $datos['stock_actual'] - (int) $st->fetchColumn();

            if ($diferencia !== 0) {
                aplicar_movimiento(
                    $pdo, $id, $diferencia > 0 ? 'ENTRADA' : 'SALIDA', abs($diferencia),
                    'Ajuste de stock desde la edición del producto', usuario_actual()['id']
                );
            }

            $pdo->commit();
            flash('success', 'Producto «' . $datos['nombre'] . '» actualizado correctamente.');
            redirigir('/productos/index.php');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errores['general'] = $ex->getMessage();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Editar producto: ' . $ex->getMessage());
            $errores['general'] = 'No se pudo actualizar el producto. Inténtalo nuevamente.';
        }
    }
}

$titulo      = 'Editar producto';
$menu_activo = 'productos';
$modo        = 'editar';
$accion      = BASE_URL . '/productos/editar.php?id=' . $id;
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Editar producto</h1>
<div class="card shadow-sm"><div class="card-body">
    <?php require __DIR__ . '/../includes/form_producto.php'; ?>
</div></div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
