<?php
/**
 * Formulario de producto compartido por productos/crear.php y productos/editar.php.
 * Variables esperadas: $modo ('crear' | 'editar'), $accion (URL), $datos, $errores, $categorias
 */
$editando = ($modo === 'editar');
?>
<form method="post" action="<?= e($accion) ?>" class="needs-validation" novalidate>
    <?= csrf_campo() ?>

    <?php if (!empty($errores['general'])): ?>
        <div class="alert alert-danger"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-4">
            <label for="codigo" class="form-label">Código *</label>
            <input type="text" id="codigo" name="codigo" maxlength="30"
                   class="form-control <?= clase_error($errores, 'codigo') ?>"
                   value="<?= e($datos['codigo']) ?>" required <?= $editando ? 'readonly' : '' ?>
                   data-regla="codigo" data-etiqueta="El código">
            <?= bloque_error($errores, 'codigo') ?>
            <?php if ($editando): ?>
                <div class="form-text">El código no se puede modificar.</div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <label for="nombre" class="form-label">Nombre *</label>
            <input type="text" id="nombre" name="nombre" minlength="2" maxlength="120"
                   class="form-control <?= clase_error($errores, 'nombre') ?>"
                   value="<?= e($datos['nombre']) ?>" required
                   data-regla="texto" data-etiqueta="El nombre">
            <?= bloque_error($errores, 'nombre') ?>
        </div>

        <div class="col-md-6">
            <label for="id_categoria" class="form-label">Categoría *</label>
            <select id="id_categoria" name="id_categoria" required data-etiqueta="La categoría"
                    class="form-select <?= clase_error($errores, 'id_categoria') ?>">
                <option value="">Selecciona una categoría…</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id_categoria'] ?>"
                        <?= (string) $datos['id_categoria'] === (string) $c['id_categoria'] ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= bloque_error($errores, 'id_categoria') ?>
        </div>

        <div class="col-md-6">
            <label for="precio" class="form-label">Precio ($) *</label>
            <input type="number" id="precio" name="precio" min="0" step="0.01"
                   class="form-control <?= clase_error($errores, 'precio') ?>"
                   value="<?= e($datos['precio']) ?>" required
                   data-regla="decimal" data-etiqueta="El precio">
            <?= bloque_error($errores, 'precio') ?>
        </div>

        <div class="col-12">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="2" maxlength="255"
                      class="form-control <?= clase_error($errores, 'descripcion') ?>"
                      data-regla="texto" data-etiqueta="La descripción"><?= e($datos['descripcion']) ?></textarea>
            <?= bloque_error($errores, 'descripcion') ?>
        </div>

        <div class="col-md-6">
            <label for="stock_actual" class="form-label">Stock actual *</label>
            <input type="number" id="stock_actual" name="stock_actual" min="0" step="1"
                   class="form-control <?= clase_error($errores, 'stock_actual') ?>"
                   value="<?= e($datos['stock_actual']) ?>" required
                   data-regla="entero" data-etiqueta="El stock actual">
            <?= bloque_error($errores, 'stock_actual') ?>
            <div class="form-text">
                <?= $editando
                    ? 'Si cambias esta cantidad, el sistema registra un movimiento de ajuste automáticamente.'
                    : 'Si es mayor que 0, se registra una entrada de “Stock inicial”.' ?>
            </div>
        </div>

        <div class="col-md-6">
            <label for="stock_minimo" class="form-label">Stock mínimo *</label>
            <input type="number" id="stock_minimo" name="stock_minimo" min="0" step="1"
                   class="form-control <?= clase_error($errores, 'stock_minimo') ?>"
                   value="<?= e($datos['stock_minimo']) ?>" required
                   data-regla="entero" data-etiqueta="El stock mínimo">
            <?= bloque_error($errores, 'stock_minimo') ?>
            <div class="form-text">Se muestra una alerta cuando el stock actual sea menor que este valor.</div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><?= $editando ? 'Guardar cambios' : 'Registrar producto' ?></button>
        <a href="<?= BASE_URL ?>/productos/index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
