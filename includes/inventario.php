<?php
/**
 * Lógica de inventario: registrar movimientos y actualizar el stock.
 * Es el único lugar del sistema que modifica stock_actual.
 */

/**
 * Registra una ENTRADA o SALIDA y actualiza el stock del producto.
 * Debe llamarse dentro de una transacción.
 * para que el stock y el movimiento se guarden juntos o no se guarde nada.
 * 
 * @throws RuntimeException si se incumple una regla de negocio.
*/function aplicar_movimiento(PDO $pdo, int $idProducto, string $tipo, int $cantidad, ?string $observacion, int $idUsuario): void
{
    if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
        throw new RuntimeException('El tipo de movimiento no es válido.');
    }
    if ($cantidad < 1) {
        throw new RuntimeException('La cantidad debe ser mayor que 0.');
    }

    // Una sola instrucción update que valida y actualiza a la vez.
    // En una salida, la condición "stock_actual >= cantidad" impide dejar stock negativo.
    if ($tipo === 'ENTRADA') {
        $sql = 'UPDATE producto SET stock_actual = stock_actual + :cant1
                WHERE id_producto = :id AND estado = 1';
        $parametros = [':cant1' => $cantidad, ':id' => $idProducto];
    } else {
        $sql = 'UPDATE producto SET stock_actual = stock_actual - :cant1
                WHERE id_producto = :id AND estado = 1 AND stock_actual >= :cant2';
        $parametros = [':cant1' => $cantidad, ':cant2' => $cantidad, ':id' => $idProducto];
    }
    $st = $pdo->prepare($sql);
    $st->execute($parametros);

    // Si no se modificó ninguna fila, se buca el motivo.
    if ($st->rowCount() === 0) {
        $st = $pdo->prepare('SELECT estado, stock_actual FROM producto WHERE id_producto = :id');
        $st->execute([':id' => $idProducto]);
        $producto = $st->fetch();

        if (!$producto) {
            throw new RuntimeException('El producto no existe.');
        }
        if ((int) $producto['estado'] !== 1) {
            throw new RuntimeException('El producto está desactivado: no admite movimientos.');
        }
        throw new RuntimeException('Stock insuficiente: hay ' . (int) $producto['stock_actual']
            . ' unidades disponibles y se intentan sacar ' . $cantidad . '.');
    }

    // Se registra el movimiento 
    $st = $pdo->prepare(
        'INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario)
         VALUES (:fecha, :tipo, :cantidad, :obs, :producto, :usuario)'
    );
    $st->execute([
        ':fecha'    => date('Y-m-d H:i:s'),
        ':tipo'     => $tipo,
        ':cantidad' => $cantidad,
        ':obs'      => ($observacion === null || $observacion === '') ? null : $observacion,
        ':producto' => $idProducto,
        ':usuario'  => $idUsuario,
    ]);
}

/** Productos activos cuyo stock actual es menor que el stock mínimo. */
function productos_stock_bajo(PDO $pdo): array
{
    return $pdo->query(
        'SELECT id_producto, codigo, nombre, stock_actual, stock_minimo
         FROM producto
         WHERE estado = 1 AND stock_actual < stock_minimo
         ORDER BY (stock_minimo - stock_actual) DESC, nombre'
    )->fetchAll();
}
