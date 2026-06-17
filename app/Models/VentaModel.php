<?php
namespace App\Models;

use Core\Model;

// Modelo de ventas. El corazón es registrar(), que graba una venta completa
// de forma atómica (cabecera + líneas + descuento de stock) en una sola transacción.
class VentaModel extends Model {

    // Lista las cabeceras de venta, la más reciente primero.
    public function listar() {
        return $this->query('SELECT * FROM ventas ORDER BY id DESC');
    }

    // Devuelve una venta con su arreglo de líneas de detalle, o null si no existe.
    public function buscar($id) {
        $venta = $this->find('ventas', $id);
        if ($venta === null) {
            return null;
        }
        $venta['detalle'] = $this->query(
            'SELECT * FROM venta_detalle WHERE venta_id = ? ORDER BY id ASC',
            [(int) $id]
        );
        return $venta;
    }

    /**
     * Registra una venta completa de forma atómica.
     *
     * @param array $datos [
     *   'metodo_pago'  => 'efectivo'|'tarjeta'|'transferencia',
     *   'monto_pagado' => float,
     *   'cajero'       => string|null,
     *   'cliente'      => string|null,
     *   'nota'         => string|null,
     *   'items'        => [ ['producto_id'=>int, 'cantidad'=>int], ... ]
     * ]
     * @return int  id de la venta creada
     * @throws \RuntimeException  ante errores de negocio (producto/stock/pago)
     * @throws \PDOException       ante cualquier fallo de BD (provoca rollback)
     */
    public function registrar($datos) {
        $this->db->beginTransaction();
        try {
            // (1) Validar cada línea y armar los snapshots. Bloqueamos las filas
            //     de producto (FOR UPDATE) para evitar sobreventa concurrente.
            $lineas = [];
            $total  = 0.0;

            foreach ($datos['items'] as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad   = (int) $item['cantidad'];

                if ($cantidad < 1) {
                    throw new \RuntimeException("Cantidad inválida para el producto {$productoId}.");
                }

                $stmt = $this->db->prepare(
                    'SELECT id, nombre, precio, stock, activo
                     FROM productos WHERE id = ? FOR UPDATE'
                );
                $stmt->execute([$productoId]);
                $producto = $stmt->fetch();

                if ($producto === false) {
                    throw new \RuntimeException("El producto {$productoId} no existe.");
                }
                if ((int) $producto['activo'] !== 1) {
                    throw new \RuntimeException("El producto '{$producto['nombre']}' está inactivo.");
                }
                if ((int) $producto['stock'] < $cantidad) {
                    throw new \RuntimeException(
                        "Stock insuficiente de '{$producto['nombre']}' " .
                        "(disponible {$producto['stock']}, pedido {$cantidad})."
                    );
                }

                $precioUnitario = (float) $producto['precio'];
                $total += $precioUnitario * $cantidad;

                $lineas[] = [
                    'producto_id'     => $productoId,
                    'nombre_producto' => $producto['nombre'],  // snapshot
                    'precio_unitario' => $precioUnitario,      // snapshot
                    'cantidad'        => $cantidad,
                ];
            }

            // (2) El monto pagado debe cubrir el total.
            //     vuelto es columna GENERADA (monto_pagado - total): NO se inserta.
            $montoPagado = (float) $datos['monto_pagado'];
            if ($montoPagado < $total) {
                throw new \RuntimeException(
                    "El monto pagado es menor que el total de la venta."
                );
            }

            // (3) Insertar la cabecera (sin la columna generada 'vuelto').
            $stmt = $this->db->prepare(
                'INSERT INTO ventas (total, monto_pagado, metodo_pago, estado, cajero, cliente, nota)
                 VALUES (?, ?, ?, "completada", ?, ?, ?)'
            );
            $stmt->execute([
                $total,
                $montoPagado,
                $datos['metodo_pago'],
                $datos['cajero']  ?? null,
                $datos['cliente'] ?? null,
                $datos['nota']    ?? null,
            ]);
            $ventaId = (int) $this->db->lastInsertId();

            // (4) Insertar cada línea (sin la columna generada 'subtotal')
            //     y (5) descontar el stock con guardia anti-carrera.
            $stmtDetalle = $this->db->prepare(
                'INSERT INTO venta_detalle
                    (venta_id, producto_id, nombre_producto, precio_unitario, cantidad)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtStock = $this->db->prepare(
                'UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?'
            );

            foreach ($lineas as $linea) {
                $stmtDetalle->execute([
                    $ventaId,
                    $linea['producto_id'],
                    $linea['nombre_producto'],
                    $linea['precio_unitario'],
                    $linea['cantidad'],
                ]);

                $stmtStock->execute([
                    $linea['cantidad'],
                    $linea['producto_id'],
                    $linea['cantidad'],
                ]);
                if ($stmtStock->rowCount() === 0) {
                    // Alguien consumió el stock entre el SELECT y el UPDATE.
                    throw new \RuntimeException(
                        "No se pudo descontar el stock del producto {$linea['producto_id']}."
                    );
                }
            }

            $this->db->commit();
            return $ventaId;

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e; // el controlador traduce el mensaje a una respuesta JSON
        }
    }

    // Anula una venta (no la borra). Devuelve cuántas filas cambiaron.
    public function anular($id) {
        $stmt = $this->db->prepare(
            'UPDATE ventas SET estado = "anulada" WHERE id = ? AND estado <> "anulada"'
        );
        $stmt->execute([(int) $id]);
        return $stmt->rowCount();
    }
}
