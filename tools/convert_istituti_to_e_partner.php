<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$in = __DIR__ . '/../database/database.sql';
$out = __DIR__ . '/../database/istituti_to_istituti_e_partner.sql';
$raw = file_get_contents($in);
if ($raw === false) { die("Can't read input\n"); }
if (preg_match_all("/INSERT INTO `istituti`.*?VALUES(.*?);/si", $raw, $matches)) {
    $all_tuples = [];
    foreach ($matches[1] as $block) {
        $block = trim($block);
        if ($block === '') continue;
        $tuples = preg_split('/\)\s*,\s*\r?\n\s*\(/', $block);
        foreach ($tuples as $t) {
            $t = trim($t);
            $t = preg_replace('/^\(/','',$t);
            $t = preg_replace('/\)\s*$/','',$t);
            $all_tuples[] = $t;
        }
    }
    $outHandle = fopen($out,'w');
    fwrite($outHandle, "-- Generated INSERTs converting istituti -> istituti_e_partner\n");
    fwrite($outHandle, "SET AUTOCOMMIT=0;\nSTART TRANSACTION;\n");
    $batchSize = 1000; $batch = [];$i=0;
    foreach ($all_tuples as $t) {
        $i++;
        // parse fields top-level
        $fields = [];
        $len = strlen($t); $in_quote=false; $prev=''; $field='';
        for ($k=0;$k<$len;$k++){
            $c = $t[$k];
            if ($c === "'" && $prev !== "\\") { $in_quote = !$in_quote; $field .= $c; }
            elseif (!$in_quote && $c === ',') { $fields[] = trim($field); $field=''; }
            else { $field .= $c; }
            $prev = $c;
        }
        if (strlen($field)>0) $fields[] = trim($field);
        // Expecting 10 fields: id,codice_istituto,nome,email,tipo_scuola,indirizzo,comune,provincia,created_at,regione
        if (count($fields) < 10) {
            // skip malformed
            continue;
        }
        $id = $fields[0];
        $codice = $fields[1];
        $nome = $fields[2];
        $email = $fields[3];
        $tipo = $fields[4];
        $indirizzo = $fields[5];
        $comune = $fields[6];
        $provincia = $fields[7];
        $created_at = $fields[8];
        $regione = $fields[9];
        // Build new tuple in this column order:
        // (`ID_Ente`,`Cod_Mecc`,`Ragione_Sociale`,`Email`,`Tipologia`,`Indirizzo`,`Comune`,`Provincia`,`created_at`,`Regione`,`CF_PIVA`,`Cod_REA`,`Coordinate_GPS`,`Stato_Validazione`)
        $new_tuple = '(' . $id . ', ' . $codice . ', ' . $nome . ', ' . $email . ', ' . $tipo . ', ' . $indirizzo . ', ' . $comune . ', ' . $provincia . ', ' . $created_at . ', ' . $regione . ', NULL, NULL, NULL, 1)';
        $batch[] = $new_tuple;
        if (count($batch) >= $batchSize) {
            fwrite($outHandle, "INSERT INTO `istituti_e_partner` (`ID_Ente`,`Cod_Mecc`,`Ragione_Sociale`,`Email`,`Tipologia`,`Indirizzo`,`Comune`,`Provincia`,`created_at`,`Regione`,`CF_PIVA`,`Cod_REA`,`Coordinate_GPS`,`Stato_Validazione`) VALUES\n");
            fwrite($outHandle, implode(",\n", $batch) . ";\n\n");
            $batch = [];
        }
    }
    if (count($batch) > 0) {
        fwrite($outHandle, "INSERT INTO `istituti_e_partner` (`ID_Ente`,`Cod_Mecc`,`Ragione_Sociale`,`Email`,`Tipologia`,`Indirizzo`,`Comune`,`Provincia`,`created_at`,`Regione`,`CF_PIVA`,`Cod_REA`,`Coordinate_GPS`,`Stato_Validazione`) VALUES\n");
        fwrite($outHandle, implode(",\n", $batch) . ";\n\n");
    }
    fwrite($outHandle, "COMMIT;\n");
    fclose($outHandle);
    echo "Wrote file: $out\n";
    echo "Total converted tuples: " . count($all_tuples) . "\n";
} else {
    echo "No INSERT INTO `istituti` found.\n";
}
?>
