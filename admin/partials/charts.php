<?php
/** Gráficas SVG generadas en el servidor (sin librerías externas). */

function chart_bars(array $labels, array $values, string $color = '#1677FF', int $h = 220): string
{
    $n = max(count($values), 1);
    $max = max(max($values ?: [0]), 1);
    $w = 640; $pad = 30; $bw = ($w - $pad * 2) / $n;
    $svg = '<svg viewBox="0 -18 ' . $w . ' ' . ($h + 58) . '" class="chart" role="img">';
    for ($g = 0; $g <= 4; $g++) {
        $y = 10 + ($h - 10) * $g / 4;
        $svg .= '<line x1="' . $pad . '" x2="' . ($w - $pad) . '" y1="' . $y . '" y2="' . $y . '" stroke="#e2e8f0"/>';
        $svg .= '<text x="' . ($pad - 6) . '" y="' . ($y + 4) . '" text-anchor="end" class="ct">' . round($max * (4 - $g) / 4) . '</text>';
    }
    foreach ($values as $i => $v) {
        $bh = ($h - 10) * $v / $max;
        $x = $pad + $i * $bw + $bw * .18;
        $svg .= '<rect x="' . round($x, 1) . '" y="' . round($h - $bh, 1) . '" width="' . round($bw * .64, 1) . '" height="' . round($bh, 1) . '" rx="5" fill="' . $color . '"><title>' . e($labels[$i]) . ': ' . $v . '</title></rect>';
        $svg .= '<text x="' . round($x + $bw * .32, 1) . '" y="' . round($h - $bh - 6, 1) . '" text-anchor="middle" class="cv">' . $v . '</text>';
        $svg .= '<text x="' . round($x + $bw * .32, 1) . '" y="' . ($h + 22) . '" text-anchor="middle" class="ct">' . e($labels[$i]) . '</text>';
    }
    return $svg . '</svg>';
}

function chart_donut(array $data, array $colors): string
{
    $total = array_sum($data) ?: 1;
    $r = 70; $c = 2 * M_PI * $r; $off = 0; $i = 0;
    $svg = '<div class="donut-wrap"><svg viewBox="0 0 200 200" class="donut"><circle cx="100" cy="100" r="' . $r . '" fill="none" stroke="#eef2f7" stroke-width="28"/>';
    $legend = '<ul class="legend">';
    foreach ($data as $label => $v) {
        $col = $colors[$i % count($colors)];
        $len = $c * $v / $total;
        if ($v > 0) $svg .= '<circle cx="100" cy="100" r="' . $r . '" fill="none" stroke="' . $col . '" stroke-width="28" stroke-dasharray="' . round($len, 2) . ' ' . round($c - $len, 2) . '" stroke-dashoffset="' . round(-$off, 2) . '" transform="rotate(-90 100 100)"><title>' . e($label) . ': ' . $v . '</title></circle>';
        $off += $len;
        $legend .= '<li><i style="background:' . $col . '"></i>' . e($label) . ' <b>' . $v . '</b> <small>' . round($v * 100 / $total) . '%</small></li>';
        $i++;
    }
    $svg .= '<text x="100" y="96" text-anchor="middle" class="donut-total">' . array_sum($data) . '</text><text x="100" y="118" text-anchor="middle" class="ct">total</text></svg>';
    return $svg . $legend . '</ul></div>';
}

function chart_hbars(array $data, string $color = '#16A34A'): string
{
    $max = max(max($data ?: [0]), 1);
    $html = '<div class="hbars">';
    foreach ($data as $label => $v) {
        $html .= '<div class="hbar"><span>' . e($label) . '</span><div><i style="width:' . round($v * 100 / $max) . '%;background:' . $color . '"></i></div><b>' . $v . '</b></div>';
    }
    return $html . '</div>';
}

function meses_es(): array { return ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']; }
