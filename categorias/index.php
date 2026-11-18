<?php
/**
 * Listado de categorías y alta de una categoría nueva.
 */
require_once __DIR__ . '/../includes/init.php';
requerir_login();

$pdo     = conectar();
$errores = [];
$datos   = ['nombre' => '', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    [$datos, $errores] = validar_categoria($pdo, $_POST);

    if (!$errores) {
        try {
            $st = $pdo->prepare('INSERT INTO categoria (nombre, descripcion, estado) VALUES (:nombre, :descripcion, 1)');
            $st->execute([
                ':nombre'      => $datos['nombre'],
                ':descripcion' => $datos['descripcion'] === '' ? null : $datos['descripcion'],
            ]);
            flash('success', 'Categoría «' . $datos['nombre'] . '» creada correctamente.');
            redirigir('/categorias/index.php');
        } catch (PDOException $ex) {
            if ((int) ($ex->errorInfo[1] ?? 0) === 1062) {
                $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
            } else {
                error_log('Crear categoría: ' . $ex->getMessage());
                $errores['general'] = 'No se pudo crear la categoría. Inténtalo nuevamente.';
            }
        }
    }
}

$categorias = $pdo->query(
    'SELECT c.id_categoria, c.nombre, c.descripcion, c.estado,
            (SELECT COUNT(*) FROM producto p WHERE p.id_categoria = c.id_categoria AND p.estado = 1) AS productos_activos
     FROM categoria c
     ORDER BY c.nombre'
)->fetchAll();

$titulo      = 'Categorías';
$menu_activo = 'categorias';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Categorías</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h5 mb-3">Nueva categoría</h2>

            <?php if (!empty($errores['general'])): ?>
                <div class="alert alert-danger"><?= e($errores['general']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/categorias/index.php" class="needs-validation" novalidate>
                <?= csrf_campo() ?>
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" minlength="2" maxlength="80" required
                           class="form-control <?= clase_error($errores, 'nombre') ?>"
                           value="<?= e($datos['nombre']) ?>"
                           data-regla="texto" data-etiqueta="El nombre">
                    <?= bloque_error($errores, 'nombre') ?>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <input type="text" id="descripcion" name="descripcion" maxlength="255"
                           class="form-control <?= clase_error($errores, 'descripcion') ?>"
                           value="<?= e($datos['descripcion']) ?>"
                           data-regla="texto" data-etiqueta="La descripción">
                    <?= bloque_error($errores, 'descripcion') ?>
                </div>
                <button type="submit" class="btn btn-primary w-100">Crear categoría</button>
            </form>
        </div></div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="tabla-scroll">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th><th>Descripción</th>
                            <th class="text-end">Productos activos</th><th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$categorias): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Aún no hay categorías.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($categorias as $c): $activa = (int) $c['estado'] === 1; ?>
                        <tr class="<?= $activa ? '' : 'fila-inactiva' ?>">
                            <td><?= e($c['nombre']) ?></td>
                            <td><?= e($c['descripcion']) ?></td>
                            <td class="text-end"><?= (int) $c['productos_activos'] ?></td>
                            <td>
                                <span class="badge text-bg-<?= $activa ? 'success' : 'secondary' ?>">
                                    <?= $activa ? 'Activa' : 'Inactiva' ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= BASE_URL ?>/categorias/editar.php?id=<?= (int) $c['id_categoria'] ?>">Editar</a>
                                <form method="post" action="<?= BASE_URL ?>/categorias/estado.php" class="d-inline"
                                      <?= $activa ? 'data-confirmar="¿Desactivar la categoría «' . e($c['nombre']) . '»?"' : '' ?>>
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $c['id_categoria'] ?>">
                                    <input type="hidden" name="accion" value="<?= $activa ? 'desactivar' : 'activar' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $activa ? 'danger' : 'secondary' ?>">
                                        <?= $activa ? 'Desactivar' : 'Reactivar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
