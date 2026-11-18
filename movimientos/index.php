<?php
/**
 * Historial de movimientos (entradas y salidas), con filtros por producto y tipo.
 */
require_once __DIR__ . '/../includes/init.php';
requerir_login();

$pdo = conectar();

$idProducto = limpiar($_GET['id_producto'] ?? '');
$tipo       = limpiar($_GET['tipo'] ?? '');
if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
    $tipo = '';
}

$sql = 'SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.cantidad, m.observacion,
               p.codigo, p.nombre AS producto, u.nombre AS usuario
        FROM movimiento m
        INNER JOIN producto p ON p.id_producto = m.id_producto
        INNER JOIN usuario  u ON u.id_usuario  = m.id_usuario
        WHERE 1 = 1';
$parametros = [];

if ($idProducto !== '' && es_entero_no_negativo($idProducto)) {
    $sql .= ' AND m.id_producto = :producto';
    $parametros[':producto'] = (int) $idProducto;
}
if ($tipo !== '') {
    $sql .= ' AND m.tipo = :tipo';
    $parametros[':tipo'] = $tipo;
}
$sql .= ' ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 200';

$st = $pdo->prepare($sql);
$st->execute($parametros);
$movimientos = $st->fetchAll();

$productos = $pdo->query('SELECT id_producto, codigo, nombre FROM producto ORDER BY nombre')->fetchAll();

$titulo      = 'Movimientos';
$menu_activo = 'movimientos';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Movimientos de inventario</h1>
    <a class="btn btn-success" href="<?= BASE_URL ?>/movimientos/registrar.php">+ Registrar entrada / salida</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label for="id_producto" class="form-label mb-1">Producto</label>
            <select id="id_producto" name="id_producto" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($productos as $p): ?>
                    <option value="<?= (int) $p['id_producto'] ?>"
                        <?= (string) $idProducto === (string) $p['id_producto'] ? 'selected' : '' ?>>
                        <?= e($p['codigo'] . ' · ' . $p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="tipo" class="form-label mb-1">Tipo</label>
            <select id="tipo" name="tipo" class="form-select">
                <option value="">Todos</option>
                <option value="ENTRADA" <?= $tipo === 'ENTRADA' ? 'selected' : '' ?>>Entradas</option>
                <option value="SALIDA"  <?= $tipo === 'SALIDA' ? 'selected' : '' ?>>Salidas</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary flex-fill">Filtrar</button>
            <a href="<?= BASE_URL ?>/movimientos/index.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="tabla-scroll">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha y hora</th><th>Producto</th><th>Tipo</th>
                    <th class="text-end">Cantidad</th><th>Observación</th><th>Usuario</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$movimientos): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay movimientos para mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($movimientos as $m): $entrada = $m['tipo'] === 'ENTRADA'; ?>
                <tr>
                    <td class="text-nowrap"><?= e(fmt_fecha($m['fecha_hora'])) ?></td>
                    <td><?= e($m['codigo'] . ' · ' . $m['producto']) ?></td>
                    <td>
                        <span class="badge text-bg-<?= $entrada ? 'success' : 'warning' ?>">
                            <?= $entrada ? 'Entrada' : 'Salida' ?>
                        </span>
                    </td>
                    <td class="text-end"><?= $entrada ? '+' : '−' ?><?= (int) $m['cantidad'] ?></td>
                    <td><?= e($m['observacion']) ?></td>
                    <td><?= e($m['usuario']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">
    <?= count($movimientos) ?> movimiento(s) mostrado(s) (máximo 200, los más recientes primero).
</p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
