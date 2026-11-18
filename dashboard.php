<?php
/**
 * Pantalla de inicio: resumen del inventario y alerta de stock bajo.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/inventario.php';
requerir_login();

$pdo = conectar();

$totalActivos = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1')->fetchColumn();
$stockBajo    = productos_stock_bajo($pdo);

$st = $pdo->prepare('SELECT COUNT(*) FROM movimiento WHERE fecha_hora >= :hoy');
$st->execute([':hoy' => date('Y-m-d 00:00:00')]);
$movimientosHoy = (int) $st->fetchColumn();

$titulo      = 'Inicio';
$menu_activo = 'inicio';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Inicio</h1>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Productos activos</div>
            <div class="display-6"><?= $totalActivos ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100 <?= $stockBajo ? 'border-danger' : '' ?>"><div class="card-body">
            <div class="text-muted small">Productos con stock bajo</div>
            <div class="display-6 <?= $stockBajo ? 'text-danger' : '' ?>"><?= count($stockBajo) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Movimientos de hoy</div>
            <div class="display-6"><?= $movimientosHoy ?></div>
        </div></div>
    </div>
</div>

<?php if ($stockBajo): ?>
    <div class="alert alert-danger" role="alert">
        <strong>⚠ Alerta de stock bajo:</strong>
        <?= count($stockBajo) ?> producto(s) tienen menos unidades que su stock mínimo.
    </div>

    <div class="card shadow-sm mb-4">
        <div class="tabla-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th><th>Producto</th>
                        <th class="text-end">Stock actual</th><th class="text-end">Stock mínimo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($stockBajo as $p): ?>
                    <tr class="table-danger">
                        <td><?= e($p['codigo']) ?></td>
                        <td><?= e($p['nombre']) ?></td>
                        <td class="text-end stock-bajo"><?= (int) $p['stock_actual'] ?></td>
                        <td class="text-end"><?= (int) $p['stock_minimo'] ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-success"
                               href="<?= BASE_URL ?>/movimientos/registrar.php?id_producto=<?= (int) $p['id_producto'] ?>&tipo=ENTRADA">
                                Registrar entrada
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success" role="alert">✔ Todos los productos activos están sobre su stock mínimo.</div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= BASE_URL ?>/productos/crear.php">+ Nuevo producto</a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/productos/index.php">Ver productos</a>
    <a class="btn btn-outline-success" href="<?= BASE_URL ?>/movimientos/registrar.php">Registrar entrada / salida</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
