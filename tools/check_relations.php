<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$path = __DIR__ . '/../database/database.sql';
$raw = file_get_contents($path);
function extractTuples($raw, $table) {
    if (!preg_match("/INSERT INTO `".preg_quote($table, '/')."`.*?VALUES(.*?);/si", $raw, $m)) return [];
    $block = trim($m[1]);
    // normalize separators
    $block = preg_replace('/\)\s*,\s*\r?\n\s*\(/', ')###(', $block);
    $parts = explode('###', $block);
    return $parts;
}
$instTuples = extractTuples($raw, 'istituti_e_partner');
$totalInst = count($instTuples);
$instIDs = [];
$instWith1 = 0;
foreach ($instTuples as $t) {
    $s = trim($t);
    if (substr($s,0,1) === '(') $s = substr($s,1);
    $s = rtrim($s, ")\r\n\t ;,");
    if (preg_match('/^\s*([0-9]+)\s*,/',$s,$mm)) $instIDs[] = intval($mm[1]);
    if (preg_match('/,\s*1\s*$/', $s)) $instWith1++;
}
$instIDs = array_values(array_unique($instIDs));

$attTuples = extractTuples($raw, 'attivita');
$attTotal = count($attTuples);
$refIDs = [];
foreach ($attTuples as $t) {
    $s = trim($t);
    if (substr($s,0,1) === '(') $s = substr($s,1);
    $s = rtrim($s, ")\r\n\t ;,");
    // naive split: get up to second comma ignoring quotes
    $len = strlen($s);
    $in_quote = false; $prev=''; $commas = 0; $pos = 0;
    for ($i=0;$i<$len;$i++){
        $c = $s[$i];
        if ($c === "'" && $prev !== "\\") $in_quote = !$in_quote;
        if (!$in_quote && $c === ',') {
            $commas++;
            if ($commas==2) { $pos = $i; break; }
        }
        $prev = $c;
    }
    if ($commas>=1) {
        // find first comma position
        $firstComma = strpos($s, ',');
        if ($firstComma !== false) {
            $secondField = trim(substr($s, $firstComma+1, ($pos ? $pos-$firstComma-1 : null)));
            $secondField = trim($secondField, " '\\t\n\r");
            if (preg_match('/^\d+$/', $secondField)) $refIDs[] = intval($secondField);
        }
    }
}
$instIDsSet = $instIDs; sort($instIDsSet);
$refIDsSet = array_values(array_unique($refIDs)); sort($refIDsSet);
$missing = array_diff($refIDsSet, $instIDsSet);

echo "istituti_e_partner tuples: $totalInst\n";
echo "istituti_e_partner with Stato_Validazione=1: $instWith1\n";
echo "distinct istituti_e_partner IDs: " . count($instIDsSet) . "\n";
echo "attivita tuples: $attTotal\n";
echo "distinct referenced istituto_ids in attivita: " . count($refIDsSet) . "\n";
if (count($missing)>0) {
    echo "Referenced istituto_ids not present in istituti_e_partner:\n";
    foreach ($missing as $m) echo "$m\n";
} else {
    echo "All attivita.istituto_id reference existing istituti_e_partner IDs\n";
}
?>
