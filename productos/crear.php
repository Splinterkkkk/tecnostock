<?php
/**
 * Registro de un producto nuevo.
 * Si se indica stock inicial, se registra como una ENTRADA para no perder trazabilidad.
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/inventario.php';
requerir_login();

$pdo        = conectar();
$categorias = categorias_activas($pdo);
$errores    = [];
$datos      = [
    'codigo' => '', 'nombre' => '', 'descripcion' => '', 'precio' => '',
    'stock_actual' => '0', 'stock_minimo' => '0', 'id_categoria' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    [$datos, $errores] = validar_producto($pdo, $_POST);

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare(
                'INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria)
                 VALUES (:codigo, :nombre, :descripcion, :precio, 0, :minimo, 1, :categoria)'
            );
            $st->execute([
                ':codigo'      => $datos['codigo'],
                ':nombre'      => $datos['nombre'],
                ':descripcion' => $datos['descripcion'] === '' ? null : $datos['descripcion'],
                ':precio'      => $datos['precio'],
                ':minimo'      => (int) $datos['stock_minimo'],
                ':categoria'   => (int) $datos['id_categoria'],
            ]);
            $idProducto = (int) $pdo->lastInsertId();

            // El stock inicial entra como movimiento (el stock siempre se explica con movimientos).
            if ((int) $datos['stock_actual'] > 0) {
                aplicar_movimiento($pdo, $idProducto, 'ENTRADA', (int) $datos['stock_actual'],
                    'Stock inicial', usuario_actual()['id']);
            }

            $pdo->commit();
            flash('success', 'Producto «' . $datos['nombre'] . '» registrado correctamente.');
            redirigir('/productos/index.php');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errores['general'] = $ex->getMessage();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            if ((int) ($ex->errorInfo[1] ?? 0) === 1062) {          // código duplicado (restricción UNIQUE)
                $errores['codigo'] = 'Ya existe un producto con ese código.';
            } else {
                error_log('Crear producto: ' . $ex->getMessage());
                $errores['general'] = 'No se pudo registrar el producto. Inténtalo nuevamente.';
            }
        }
    }
}

$titulo      = 'Nuevo producto';
$menu_activo = 'productos';
$modo        = 'crear';
$accion      = BASE_URL . '/productos/crear.php';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Nuevo producto</h1>
<div class="card shadow-sm"><div class="card-body">
    <?php require __DIR__ . '/../includes/form_producto.php'; ?>
</div></div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
