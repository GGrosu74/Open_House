<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$path = __DIR__ . '/../database/database.sql';
$raw = file_get_contents($path);
function countTuples($raw, $table) {
    $count = 0;
    if (preg_match_all("/INSERT INTO `".preg_quote($table,'/')."`.*?VALUES(.*?);/si", $raw, $m)) {
        foreach ($m[1] as $block) {
            // count occurrences of '),(' at top level -> but safe approximation: count '),\n(' sequences and leading '('
            // Normalize
            $block = trim($block);
            if ($block === '') continue;
            // Count leading '('
            $tuples = preg_split('/\)\s*,\s*\r?\n\s*\(/', $block);
            $count += count($tuples);
        }
    }
    return $count;
}
$tables = ['istituti','istituti_e_partner'];
foreach ($tables as $t) {
    $c = countTuples($raw, $t);
    echo "$t: $c\n";
}
// also search other sql files in database folder
$dir = __DIR__ . '/../database';
$files = scandir($dir);
$additional = [];
foreach ($files as $f) {
    if (preg_match('/\.sql$/i',$f) && $f !== 'database.sql') {
        $content = file_get_contents($dir . '/' . $f);
        if (stripos($content, "INSERT INTO `istituti`") !== false) $additional[] = $f;
    }
}
if (count($additional)) {
    echo "Other SQL files containing INSERT INTO `istituti`:\n";
    foreach ($additional as $a) echo " - $a\n";
} else {
    echo "No other SQL files with INSERT INTO `istituti` found.\n";
}
?>
