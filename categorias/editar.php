<?php
/**
 * Edición del nombre y la descripción de una categoría.
 */
require_once __DIR__ . '/../includes/init.php';
requerir_login();

$pdo = conectar();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM categoria WHERE id_categoria = :id');
$st->execute([':id' => $id]);
$categoria = $st->fetch();

if (!$categoria) {
    flash('danger', 'La categoría solicitada no existe.');
    redirigir('/categorias/index.php');
}

$errores = [];
$datos   = ['nombre' => $categoria['nombre'], 'descripcion' => (string) $categoria['descripcion']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    [$datos, $errores] = validar_categoria($pdo, $_POST, $id);

    if (!$errores) {
        try {
            $st = $pdo->prepare('UPDATE categoria SET nombre = :nombre, descripcion = :descripcion WHERE id_categoria = :id');
            $st->execute([
                ':nombre'      => $datos['nombre'],
                ':descripcion' => $datos['descripcion'] === '' ? null : $datos['descripcion'],
                ':id'          => $id,
            ]);
            flash('success', 'Categoría «' . $datos['nombre'] . '» actualizada correctamente.');
            redirigir('/categorias/index.php');
        } catch (PDOException $ex) {
            if ((int) ($ex->errorInfo[1] ?? 0) === 1062) {
                $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
            } else {
                error_log('Editar categoría: ' . $ex->getMessage());
                $errores['general'] = 'No se pudo actualizar la categoría. Inténtalo nuevamente.';
            }
        }
    }
}

$titulo      = 'Editar categoría';
$menu_activo = 'categorias';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Editar categoría</h1>
<div class="card shadow-sm"><div class="card-body">

    <?php if (!empty($errores['general'])): ?>
        <div class="alert alert-danger"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/categorias/editar.php?id=<?= $id ?>" class="needs-validation" novalidate>
        <?= csrf_campo() ?>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre *</label>
            <input type="text" id="nombre" name="nombre" minlength="2" maxlength="80" required
                   class="form-control <?= clase_error($errores, 'nombre') ?>"
                   value="<?= e($datos['nombre']) ?>" data-regla="texto" data-etiqueta="El nombre">
            <?= bloque_error($errores, 'nombre') ?>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <input type="text" id="descripcion" name="descripcion" maxlength="255"
                   class="form-control <?= clase_error($errores, 'descripcion') ?>"
                   value="<?= e($datos['descripcion']) ?>" data-regla="texto" data-etiqueta="La descripción">
            <?= bloque_error($errores, 'descripcion') ?>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="<?= BASE_URL ?>/categorias/index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div></div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
