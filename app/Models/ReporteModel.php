<?php
namespace App\Models;

use Core\Model;

// Consultas de agregación para el panel de control (dashboard).
// Solo considera ventas con estado 'completada' (excluye anuladas).
class ReporteModel extends Model {

    // Totales de hoy y de la semana ISO actual: cantidad de ventas y recaudación.
    public function resumen() {
        $hoy = $this->query(
            "SELECT COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS recaudado
             FROM ventas
             WHERE estado = 'completada' AND DATE(creado_en) = CURDATE()"
        )[0];

        $semana = $this->query(
            "SELECT COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS recaudado
             FROM ventas
             WHERE estado = 'completada' AND YEARWEEK(creado_en, 3) = YEARWEEK(CURDATE(), 3)"
        )[0];

        return [
            'hoy' => [
                'ventas'    => (int) $hoy['ventas'],
                'recaudado' => (float) $hoy['recaudado'],
            ],
            'semana' => [
                'ventas'    => (int) $semana['ventas'],
                'recaudado' => (float) $semana['recaudado'],
            ],
        ];
    }

    // Serie de los últimos $dias días (rellena con 0 los días sin ventas).
    public function serieDias($dias = 7) {
        $filas = $this->query(
            "SELECT DATE(creado_en) AS dia, COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS recaudado
             FROM ventas
             WHERE estado = 'completada' AND creado_en >= (CURDATE() - INTERVAL ? DAY)
             GROUP BY DATE(creado_en)",
            [$dias - 1]
        );

        $mapa = [];
        foreach ($filas as $f) {
            $mapa[$f['dia']] = $f;
        }

        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $serie[] = [
                'dia'       => $d,
                'ventas'    => isset($mapa[$d]) ? (int) $mapa[$d]['ventas'] : 0,
                'recaudado' => isset($mapa[$d]) ? (float) $mapa[$d]['recaudado'] : 0.0,
            ];
        }
        return $serie;
    }

    // Cuenta productos activos cuyo stock está en o por debajo del umbral.
    public function contarStockBajo($umbral = 5) {
        $fila = $this->query(
            'SELECT COUNT(*) AS total FROM productos WHERE activo = 1 AND stock <= ?',
            [(int) $umbral]
        )[0];
        return (int) $fila['total'];
    }

    // Top $limite productos más vendidos (por unidades), solo ventas completadas.
    public function topProductos($limite = 3) {
        $limite = (int) $limite;
        return $this->query(
            "SELECT vd.producto_id,
                    vd.nombre_producto,
                    SUM(vd.cantidad)  AS unidades,
                    SUM(vd.subtotal)  AS recaudado
             FROM venta_detalle vd
             JOIN ventas v ON v.id = vd.venta_id
             WHERE v.estado = 'completada'
             GROUP BY vd.producto_id, vd.nombre_producto
             ORDER BY unidades DESC
             LIMIT {$limite}"
        );
    }

    // Serie de las últimas $semanas semanas ISO (rellena con 0 las vacías).
    public function serieSemanas($semanas = 8) {
        $filas = $this->query(
            "SELECT YEARWEEK(creado_en, 3) AS yw, COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS recaudado
             FROM ventas
             WHERE estado = 'completada' AND creado_en >= (CURDATE() - INTERVAL ? WEEK)
             GROUP BY YEARWEEK(creado_en, 3)",
            [$semanas - 1]
        );

        $mapa = [];
        foreach ($filas as $f) {
            $mapa[(int) $f['yw']] = $f;
        }

        $serie = [];
        for ($i = $semanas - 1; $i >= 0; $i--) {
            $ts = strtotime("monday this week -{$i} week");
            $yw = (int) date('oW', $ts); // mismo formato yyyyww que YEARWEEK(...,3)
            $serie[] = [
                'semana'    => date('d/m', $ts), // etiqueta = lunes de esa semana
                'ventas'    => isset($mapa[$yw]) ? (int) $mapa[$yw]['ventas'] : 0,
                'recaudado' => isset($mapa[$yw]) ? (float) $mapa[$yw]['recaudado'] : 0.0,
            ];
        }
        return $serie;
    }
}
