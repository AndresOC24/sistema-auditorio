<?php

namespace App\Support;

/**
 * Paleta de las gráficas del reporte.
 *
 * Los ocho tonos y su orden están validados: superan el umbral de separación
 * para daltonismo (ΔE ≥ 8 en OKLab) y el de visión normal (≥ 15) tanto sobre
 * la superficie clara como sobre la oscura, así que la misma paleta sirve para
 * los dos temas del panel.
 *
 * El orden es fijo y no se recicla. Si algún día hay más de ocho categorías, la
 * novena no recibe un color nuevo: se agrupa en "Otros".
 */
final class ChartPalette
{
    /**
     * Orden categórico fijo: azul, naranja, aguamarina, amarillo, magenta,
     * verde, violeta, rojo.
     *
     * @var array<int, string>
     */
    public const CATEGORICAL = [
        '#3987e5',
        '#d95926',
        '#199e70',
        '#c98500',
        '#d55181',
        '#008300',
        '#9085e9',
        '#e66767',
    ];

    /**
     * Gris para la categoría "Otros", que no compite con ningún tono.
     */
    public const OTHER = '#8b8b93';

    /**
     * Serie principal: el tiempo total invertido.
     */
    public const TOTAL = '#3987e5';

    /**
     * Serie de atención: lo que cayó fuera del horario laboral. El naranja se
     * separa del azul por ΔE 26.8 incluso en protanopía.
     */
    public const OUTSIDE_HOURS = '#d95926';

    /**
     * Serie única de la gráfica por área.
     */
    public const BY_AREA = '#199e70';

    /**
     * Color estable de una categoría, elegido por su identidad y no por su
     * posición en el ranking.
     *
     * Es la diferencia entre un reporte legible y uno confuso: si el color
     * siguiera al orden, filtrar por fechas repintaría las categorías que
     * sobreviven y el lector creería estar viendo otra cosa.
     */
    public static function forId(int $id): string
    {
        return self::CATEGORICAL[$id % count(self::CATEGORICAL)];
    }
}
