<?php
/**
 * Listado de productos con búsqueda por nombre, código o categoría.
 * Los productos con stock bajo se resaltan en rojo.
 */
require_once __DIR__ . '/../includes/init.php';
requerir_login();

$pdo = conectar();

$q      = limpiar($_GET['q'] ?? '');
$estado = limpiar($_GET['estado'] ?? 'todos');
if (!in_array($estado, ['todos', 'activos', 'desactivados'], true)) {
    $estado = 'todos';
}

$sql = 'SELECT p.id_producto, p.codigo, p.nombre, p.precio, p.stock_actual, p.stock_minimo, p.estado,
               c.nombre AS categoria
        FROM producto p
        INNER JOIN categoria c ON c.id_categoria = p.id_categoria
        WHERE 1 = 1';
$parametros = [];

if ($q !== '') {
    // Se escapan % y _ para que se busquen como texto normal.
    $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
    $sql .= " AND (p.nombre LIKE :q1 ESCAPE '!' OR p.codigo LIKE :q2 ESCAPE '!' OR c.nombre LIKE :q3 ESCAPE '!')";
    $parametros[':q1'] = $like;
    $parametros[':q2'] = $like;
    $parametros[':q3'] = $like;
}
if ($estado === 'activos') {
    $sql .= ' AND p.estado = 1';
} elseif ($estado === 'desactivados') {
    $sql .= ' AND p.estado = 0';
}
$sql .= ' ORDER BY p.nombre';

$st = $pdo->prepare($sql);
$st->execute($parametros);
$productos = $st->fetchAll();

$titulo      = 'Productos';
$menu_activo = 'productos';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Productos</h1>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/productos/crear.php">+ Nuevo producto</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label for="q" class="form-label mb-1">Buscar</label>
            <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>"
                   placeholder="Nombre, código o categoría">
        </div>
        <div class="col-md-3">
            <label for="estado" class="form-label mb-1">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="todos"        <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="activos"      <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                <option value="desactivados" <?= $estado === 'desactivados' ? 'selected' : '' ?>>Desactivados</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary flex-fill">Buscar</button>
            <a href="<?= BASE_URL ?>/productos/index.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="tabla-scroll">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th class="text-end">Precio</th>
                    <th class="text-end">Stock</th>
                    <th class="text-end">Mínimo</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$productos): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron productos.</td></tr>
            <?php endif; ?>

            <?php foreach ($productos as $p):
                $activo    = (int) $p['estado'] === 1;
                $stockBajo = $activo && (int) $p['stock_actual'] < (int) $p['stock_minimo'];
                $clase     = !$activo ? 'fila-inactiva' : ($stockBajo ? 'table-danger' : '');
            ?>
                <tr class="<?= $clase ?>">
                    <td><?= e($p['codigo']) ?></td>
                    <td><?= e($p['nombre']) ?></td>
                    <td><?= e($p['categoria']) ?></td>
                    <td class="text-end"><?= e(fmt_precio($p['precio'])) ?></td>
                    <td class="text-end <?= $stockBajo ? 'stock-bajo' : '' ?>"><?= (int) $p['stock_actual'] ?></td>
                    <td class="text-end"><?= (int) $p['stock_minimo'] ?></td>
                    <td>
                        <?php if (!$activo): ?>
                            <span class="badge text-bg-secondary">Desactivado</span>
                        <?php elseif ($stockBajo): ?>
                            <span class="badge text-bg-danger">⚠ Stock bajo</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Activo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($activo): ?>
                            <a class="btn btn-sm btn-outline-success"
                               href="<?= BASE_URL ?>/movimientos/registrar.php?id_producto=<?= (int) $p['id_producto'] ?>">Movimiento</a>
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= BASE_URL ?>/productos/editar.php?id=<?= (int) $p['id_producto'] ?>">Editar</a>
                            <form method="post" action="<?= BASE_URL ?>/productos/estado.php" class="d-inline"
                                  data-confirmar="¿Desactivar el producto «<?= e($p['nombre']) ?>»? Ya no admitirá movimientos.">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
                                <input type="hidden" name="accion" value="desactivar">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Desactivar</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= BASE_URL ?>/productos/estado.php" class="d-inline">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
                                <input type="hidden" name="accion" value="activar">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Reactivar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2"><?= count($productos) ?> producto(s) mostrado(s).</p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
