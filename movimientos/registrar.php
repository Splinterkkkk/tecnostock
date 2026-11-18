<?php
/**
 * Registro de entradas y salidas de existencias.
 * El stock se actualiza automáticamente (regla 6) y una salida
 * nunca puede dejarlo bajo cero (regla 4).
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/inventario.php';
requerir_login();

$pdo = conectar();

// Solo productos activos admiten movimientos.
$productos = $pdo->query(
    'SELECT id_producto, codigo, nombre, stock_actual, stock_minimo
     FROM producto WHERE estado = 1 ORDER BY nombre'
)->fetchAll();

$errores = [];
$datos   = [
    'id_producto' => limpiar($_GET['id_producto'] ?? ''),
    'tipo'        => in_array($_GET['tipo'] ?? '', ['ENTRADA', 'SALIDA'], true) ? $_GET['tipo'] : '',
    'cantidad'    => '',
    'observacion' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    [$datos, $errores] = validar_movimiento($pdo, $_POST);

    if (!$errores) {
        try {
            $pdo->beginTransaction();
            aplicar_movimiento(
                $pdo,
                (int) $datos['id_producto'],
                $datos['tipo'],
                (int) $datos['cantidad'],
                $datos['observacion'],
                usuario_actual()['id']
            );
            $pdo->commit();

            // Datos actualizados para el mensaje y la alerta de stock bajo
            $st = $pdo->prepare('SELECT nombre, stock_actual, stock_minimo FROM producto WHERE id_producto = :id');
            $st->execute([':id' => (int) $datos['id_producto']]);
            $p = $st->fetch();

            flash('success', ($datos['tipo'] === 'ENTRADA' ? 'Entrada' : 'Salida') . ' de ' . (int) $datos['cantidad']
                . ' unidad(es) registrada para «' . $p['nombre'] . '». Stock actual: ' . (int) $p['stock_actual'] . '.');
            if ((int) $p['stock_actual'] < (int) $p['stock_minimo']) {
                flash('warning', '⚠ Stock bajo: «' . $p['nombre'] . '» tiene ' . (int) $p['stock_actual']
                    . ' unidades y su stock mínimo es ' . (int) $p['stock_minimo'] . '.');
            }
            redirigir('/movimientos/index.php');
        } catch (RuntimeException $ex) {            // regla de negocio incumplida
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errores['general'] = $ex->getMessage();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Registrar movimiento: ' . $ex->getMessage());
            $errores['general'] = 'No se pudo registrar el movimiento. Inténtalo nuevamente.';
        }
    }
}

$titulo      = 'Registrar movimiento';
$menu_activo = 'movimientos';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Registrar entrada / salida</h1>

<div class="card shadow-sm"><div class="card-body">

    <?php if (!empty($errores['general'])): ?>
        <div class="alert alert-danger"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <?php if (!$productos): ?>
        <div class="alert alert-info mb-0">
            No hay productos activos. <a href="<?= BASE_URL ?>/productos/crear.php">Registra un producto</a> primero.
        </div>
    <?php else: ?>
    <form method="post" action="<?= BASE_URL ?>/movimientos/registrar.php" class="needs-validation" novalidate>
        <?= csrf_campo() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="id_producto" class="form-label">Producto *</label>
                <select id="id_producto" name="id_producto" required data-etiqueta="El producto"
                        class="form-select <?= clase_error($errores, 'id_producto') ?>">
                    <option value="">Selecciona un producto…</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= (int) $p['id_producto'] ?>" data-stock="<?= (int) $p['stock_actual'] ?>"
                            <?= (string) $datos['id_producto'] === (string) $p['id_producto'] ? 'selected' : '' ?>>
                            <?= e($p['codigo'] . ' · ' . $p['nombre']) ?> (stock: <?= (int) $p['stock_actual'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= bloque_error($errores, 'id_producto') ?>
                <div id="stock-info" class="form-text"></div>
            </div>

            <div class="col-md-3">
                <label for="tipo" class="form-label">Tipo *</label>
                <select id="tipo" name="tipo" required data-etiqueta="El tipo de movimiento"
                        class="form-select <?= clase_error($errores, 'tipo') ?>">
                    <option value="">Selecciona…</option>
                    <option value="ENTRADA" <?= $datos['tipo'] === 'ENTRADA' ? 'selected' : '' ?>>Entrada (aumenta stock)</option>
                    <option value="SALIDA"  <?= $datos['tipo'] === 'SALIDA' ? 'selected' : '' ?>>Salida (disminuye stock)</option>
                </select>
                <?= bloque_error($errores, 'tipo') ?>
            </div>

            <div class="col-md-3">
                <label for="cantidad" class="form-label">Cantidad *</label>
                <input type="number" id="cantidad" name="cantidad" min="1" step="1" required
                       class="form-control <?= clase_error($errores, 'cantidad') ?>"
                       value="<?= e($datos['cantidad']) ?>"
                       data-regla="cantidad" data-etiqueta="La cantidad">
                <?= bloque_error($errores, 'cantidad') ?>
            </div>

            <div class="col-12">
                <label for="observacion" class="form-label">Observación</label>
                <input type="text" id="observacion" name="observacion" maxlength="255"
                       class="form-control <?= clase_error($errores, 'observacion') ?>"
                       value="<?= e($datos['observacion']) ?>"
                       placeholder="Ej.: compra a proveedor, venta a cliente"
                       data-regla="texto" data-etiqueta="La observación">
                <?= bloque_error($errores, 'observacion') ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success">Registrar movimiento</button>
            <a href="<?= BASE_URL ?>/movimientos/index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
    <?php endif; ?>
</div></div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
