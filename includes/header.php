<?php
/**
 */
$titulo       = $titulo ?? APP_NOMBRE;
$menu_activo  = $menu_activo ?? '';
$mostrar_menu = $mostrar_menu ?? true;
$usuario      = usuario_actual();
function item_menu(string $clave, string $activo, string $ruta, string $texto): string
{
    $clase = ($clave === $activo) ? 'nav-link active' : 'nav-link';
    return '<li class="nav-item"><a class="' . $clase . '" href="' . BASE_URL . $ruta . '">' . e($texto) . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(APP_NOMBRE) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php if ($mostrar_menu && $usuario): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard.php">📦 <?= e(APP_NOMBRE) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto">
                <?= item_menu('inicio', $menu_activo, '/dashboard.php', 'Inicio') ?>
                <?= item_menu('productos', $menu_activo, '/productos/index.php', 'Productos') ?>
                <?= item_menu('categorias', $menu_activo, '/categorias/index.php', 'Categorías') ?>
                <?= item_menu('movimientos', $menu_activo, '/movimientos/index.php', 'Movimientos') ?>
            </ul>
            <form method="post" action="<?= BASE_URL ?>/logout.php" class="d-flex align-items-center gap-2">
                <?= csrf_campo() ?>
                <span class="navbar-text text-light small">👤 <?= e($usuario['nombre']) ?></span>
                <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
            </form>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="container py-4">
<?php foreach (obtener_flashes() as $mensaje): ?>
    <div class="alert alert-<?= e($mensaje['tipo']) ?> alert-dismissible fade show" role="alert">
        <?= e($mensaje['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endforeach; ?>
