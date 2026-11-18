<?php
/**
 * Funciones de apoyo: salida segura, redirecciones, mensajes, CSRF,
 * formato y validaciones del servidor (se repiten las de JavaScript).
 */

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirige a una ruta interna del proyecto y detiene la ejecución. */
function redirigir(string $ruta)
{
    header('Location: ' . BASE_URL . $ruta);
    exit;
}

/* ------------------------------------------------------------------ */
/*  Mensajes para el usuario (se muestran una sola vez)               */
/* ------------------------------------------------------------------ */

/** Guarda un mensaje. $tipo: success, danger, warning o info. */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Devuelve los mensajes pendientes y los borra. */
function obtener_flashes(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

/* ------------------------------------------------------------------ */
/*  Protección CSRF (todo formulario POST lleva un token)             */
/* ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        http_response_code(400);
        exit('Solicitud no válida. Vuelve atrás, recarga la página e inténtalo de nuevo.');
    }
}

/* ------------------------------------------------------------------ */
/*  Ayudas para mostrar errores de formulario                         */
/* ------------------------------------------------------------------ */

function clase_error(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? 'is-invalid' : '';
}

/** Bloque de mensaje bajo el campo (JavaScript también lo usa). */
function bloque_error(array $errores, string $campo): string
{
    return '<div class="invalid-feedback">' . e($errores[$campo] ?? '') . '</div>';
}

/* ------------------------------------------------------------------ */
/*  Formato                                                           */
/* ------------------------------------------------------------------ */

function fmt_precio($precio): string
{
    $precio = (float) $precio;
    $decimales = (floor($precio) == $precio) ? 0 : 2;
    return '$' . number_format($precio, $decimales, ',', '.');
}

function fmt_fecha(string $fecha): string
{
    $tiempo = strtotime($fecha);
    return $tiempo ? date('d-m-Y H:i', $tiempo) : $fecha;
}

/* ------------------------------------------------------------------ */
/*  Validaciones del servidor                                         */
/* ------------------------------------------------------------------ */

/** Quita espacios al inicio y al final; null se convierte en texto vacío. */
function limpiar($valor): string
{
    return trim((string) ($valor ?? ''));
}

function es_entero_no_negativo(string $valor): bool
{
    return (bool) preg_match('/^\d{1,9}$/', $valor);
}

function es_decimal_no_negativo(string $valor): bool
{
    return (bool) preg_match('/^\d{1,8}([.,]\d{1,2})?$/', $valor);
}

/** Categorías activas, para llenar las listas desplegables. */
function categorias_activas(PDO $pdo): array
{
    return $pdo->query('SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre')->fetchAll();
}

/**
 * Valida los datos de un producto.
 *
 * @param int|null $idProducto      null = producto nuevo; con valor = edición (el código no cambia).
 * @param int|null $categoriaActual categoría que ya tenía el producto (puede estar inactiva).
 * @return array [datos limpios, errores por campo]
 */
function validar_producto(PDO $pdo, array $post, ?int $idProducto = null, ?int $categoriaActual = null): array
{
    $datos = [
        'codigo'       => limpiar($post['codigo'] ?? ''),
        'nombre'       => limpiar($post['nombre'] ?? ''),
        'descripcion'  => limpiar($post['descripcion'] ?? ''),
        'precio'       => limpiar($post['precio'] ?? ''),
        'stock_actual' => limpiar($post['stock_actual'] ?? ''),
        'stock_minimo' => limpiar($post['stock_minimo'] ?? ''),
        'id_categoria' => limpiar($post['id_categoria'] ?? ''),
    ];
    $errores = [];

    // Código: solo se valida al crear (regla 1: no puede repetirse).
    if ($idProducto === null) {
        if ($datos['codigo'] === '') {
            $errores['codigo'] = 'El código es obligatorio.';
        } elseif (!preg_match('/^[A-Za-z0-9_-]{2,30}$/', $datos['codigo'])) {
            $errores['codigo'] = 'Use de 2 a 30 caracteres: letras, números, guion o guion bajo.';
        } else {
            $st = $pdo->prepare('SELECT 1 FROM producto WHERE codigo = :codigo LIMIT 1');
            $st->execute([':codigo' => $datos['codigo']]);
            if ($st->fetchColumn()) {
                $errores['codigo'] = 'Ya existe un producto con ese código.';
            }
        }
    }

    // Nombre
    $largoNombre = mb_strlen($datos['nombre']);
    if ($datos['nombre'] === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif ($largoNombre < 2 || $largoNombre > 120) {
        $errores['nombre'] = 'El nombre debe tener entre 2 y 120 caracteres.';
    }

    // Descripción 
    if (mb_strlen($datos['descripcion']) > 255) {
        $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
    }

    // Precio 
    if ($datos['precio'] === '') {
        $errores['precio'] = 'El precio es obligatorio.';
    } elseif (!es_decimal_no_negativo($datos['precio'])) {
        $errores['precio'] = 'El precio debe ser un número mayor o igual a 0 (máximo 2 decimales).';
    } else {
        $datos['precio'] = str_replace(',', '.', $datos['precio']);
    }

    // Stock actual y mínimo 
    foreach (['stock_actual' => 'El stock actual', 'stock_minimo' => 'El stock mínimo'] as $campo => $etiqueta) {
        if ($datos[$campo] === '') {
            $errores[$campo] = $etiqueta . ' es obligatorio.';
        } elseif (!es_entero_no_negativo($datos[$campo])) {
            $errores[$campo] = $etiqueta . ' debe ser un número entero mayor o igual a 0.';
        }
    }

    // Categoría: debe existir y estar activa (o ser la que el producto ya tenía)
    if ($datos['id_categoria'] === '') {
        $errores['id_categoria'] = 'Selecciona una categoría.';
    } elseif (!es_entero_no_negativo($datos['id_categoria'])) {
        $errores['id_categoria'] = 'La categoría seleccionada no es válida.';
    } else {
        $st = $pdo->prepare('SELECT estado FROM categoria WHERE id_categoria = :id');
        $st->execute([':id' => (int) $datos['id_categoria']]);
        $estado = $st->fetchColumn();
        $esLaActual = $categoriaActual !== null && (int) $datos['id_categoria'] === $categoriaActual;
        if ($estado === false || ((int) $estado !== 1 && !$esLaActual)) {
            $errores['id_categoria'] = 'La categoría seleccionada no existe o está inactiva.';
        }
    }

    return [$datos, $errores];
}

/**
 * Valida los datos de una categoría.
 * @param int|null $idCategoria null = categoría nueva; con valor = edición.
 * @return array [datos limpios, errores por campo]
 */
function validar_categoria(PDO $pdo, array $post, ?int $idCategoria = null): array
{
    $datos = [
        'nombre'      => limpiar($post['nombre'] ?? ''),
        'descripcion' => limpiar($post['descripcion'] ?? ''),
    ];
    $errores = [];

    $largo = mb_strlen($datos['nombre']);
    if ($datos['nombre'] === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif ($largo < 2 || $largo > 80) {
        $errores['nombre'] = 'El nombre debe tener entre 2 y 80 caracteres.';
    } else {
        // El nombre no puede repetirse (restricción UNIQUE en la base de datos)
        $st = $pdo->prepare('SELECT 1 FROM categoria WHERE nombre = :nombre AND id_categoria <> :id LIMIT 1');
        $st->execute([':nombre' => $datos['nombre'], ':id' => $idCategoria ?? 0]);
        if ($st->fetchColumn()) {
            $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
        }
    }

    if (mb_strlen($datos['descripcion']) > 255) {
        $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
    }

    return [$datos, $errores];
}

/**
 * Valida el formulario de entrada/salida de existencias.
 * @return array [datos limpios, errores por campo]
 */
function validar_movimiento(PDO $pdo, array $post): array
{
    $datos = [
        'id_producto' => limpiar($post['id_producto'] ?? ''),
        'tipo'        => limpiar($post['tipo'] ?? ''),
        'cantidad'    => limpiar($post['cantidad'] ?? ''),
        'observacion' => limpiar($post['observacion'] ?? ''),
    ];
    $errores = [];
    $producto = null;

    // Producto: debe existir y estar activo
    if ($datos['id_producto'] === '' || !es_entero_no_negativo($datos['id_producto'])) {
        $errores['id_producto'] = 'Selecciona un producto.';
    } else {
        $st = $pdo->prepare('SELECT id_producto, nombre, stock_actual, estado FROM producto WHERE id_producto = :id');
        $st->execute([':id' => (int) $datos['id_producto']]);
        $producto = $st->fetch();
        if (!$producto) {
            $errores['id_producto'] = 'El producto seleccionado no existe.';
        } elseif ((int) $producto['estado'] !== 1) {
            $errores['id_producto'] = 'El producto está desactivado: no admite movimientos.';
        }
    }

    // Tipo
    if (!in_array($datos['tipo'], ['ENTRADA', 'SALIDA'], true)) {
        $errores['tipo'] = 'Selecciona el tipo de movimiento.';
    }

    // Cantidad 
    if ($datos['cantidad'] === '') {
        $errores['cantidad'] = 'La cantidad es obligatoria.';
    } elseif (!es_entero_no_negativo($datos['cantidad']) || (int) $datos['cantidad'] < 1) {
        $errores['cantidad'] = 'La cantidad debe ser un número entero mayor que 0.';
    } elseif ((int) $datos['cantidad'] > 1000000) {
        $errores['cantidad'] = 'La cantidad no puede superar 1.000.000 de unidades.';
    } elseif (
        $producto && !isset($errores['id_producto']) && $datos['tipo'] === 'SALIDA'
        && (int) $datos['cantidad'] > (int) $producto['stock_actual']
    ) {
        // Regla 4: una salida no puede dejar el stock bajo cero
        $errores['cantidad'] = 'Stock insuficiente: hay ' . (int) $producto['stock_actual']
            . ' unidades disponibles y se intentan sacar ' . (int) $datos['cantidad'] . '.';
    }

    // Observación 
    if (mb_strlen($datos['observacion']) > 255) {
        $errores['observacion'] = 'La observación no puede superar los 255 caracteres.';
    }

    return [$datos, $errores];
}
