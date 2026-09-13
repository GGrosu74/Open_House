<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$path = __DIR__ . '/../database/database.sql';
$backup = $path . '.bak.' . time();
copy($path, $backup) or die("Backup failed\n");
$contents = file_get_contents($path);
$offset = 0;
$modified_headers = 0;
$modified_tuples = 0;
while (true) {
    $pos = strpos($contents, "INSERT INTO `istituti_e_partner`", $offset);
    if ($pos === false) break;
    // find opening parenthesis of column list
    $open = strpos($contents, '(', $pos);
    if ($open === false) break;
    // find matching closing parenthesis for column list
    $i = $open + 1;
    $depth = 1;
    $len = strlen($contents);
    while ($i < $len && $depth > 0) {
        $ch = $contents[$i];
        if ($ch === '(') $depth++;
        elseif ($ch === ')') $depth--;
        $i++;
    }
    $close = $i - 1;
    $collist = substr($contents, $open+1, $close-$open-1);
    if (strpos($collist, 'Stato_Validazione') === false) {
        // insert Stato_Validazione before closing
        $new_collist = trim($collist);
        if (substr($new_collist, -1) === ',') $new_collist = $new_collist . " `Stato_Validazione`";
        else $new_collist = $new_collist . ", `Stato_Validazione`";
        $contents = substr($contents, 0, $open+1) . $new_collist . substr($contents, $close);
        $modified_headers++;
        // adjust indices due to length change
        $delta = strlen($new_collist) - strlen($collist);
        $close += $delta;
        $pos = $close;
    } else {
        $pos = $close;
    }
    // find "VALUES" after close
    $values_pos = stripos($contents, 'VALUES', $pos);
    if ($values_pos === false) { $offset = $pos; continue; }
    // find end of this INSERT statement (semicolon)
    $stmt_end = strpos($contents, ';', $values_pos);
    if ($stmt_end === false) break;
    $tuples_block = substr($contents, $values_pos + strlen('VALUES'), $stmt_end - ($values_pos + strlen('VALUES')));
    // process each line within tuples_block
    $lines = preg_split("/\r?\n/", $tuples_block);
    $new_lines = [];
    foreach ($lines as $line) {
        $trim = trim($line);
        if (strlen($trim) === 0) { $new_lines[] = $line; continue; }
        if ($trim[0] === '(') {
            // find content inside outermost parentheses
            $start = strpos($line, '(');
            $end = strrpos($line, ')');
            if ($start === false || $end === false) { $new_lines[] = $line; continue; }
            $inner = substr($line, $start+1, $end-$start-1);
            // count top-level commas ignoring commas inside single quotes
            $in_quote = false;
            $prev = '';
            $commas = 0;
            for ($k=0,$L=strlen($inner); $k<$L; $k++) {
                $c = $inner[$k];
                if ($c === "'" && $prev !== "\\") { $in_quote = !$in_quote; }
                if (!$in_quote && $c === ',') $commas++;
                $prev = $c;
            }
            $values_count = $commas + 1;
            // determine header column count (we can approximate by counting columns in last header we modified)
            // to be safe, if values_count < 11, append , 1
            if ($values_count < 11) {
                // insert ', 1' before the closing ')'
                $trailing = substr($line, $end+1); // includes comma or semicolon and spaces
                $new_inner = $inner . ", 1";
                $new_line = substr($line, 0, $start+1) . $new_inner . ')' . $trailing;
                $new_lines[] = $new_line;
                $modified_tuples++;
            } else {
                $new_lines[] = $line;
            }
        } else {
            $new_lines[] = $line;
        }
    }
    $new_block = implode("\n", $new_lines);
    // replace tuples block in contents
    $contents = substr($contents, 0, $values_pos + strlen('VALUES')) . $new_block . substr($contents, $stmt_end);
    // continue search after this statement
    $offset = $values_pos + strlen('VALUES') + strlen($new_block) + 1;
}
file_put_contents($path, $contents) or die("Write failed\n");
echo "Modified headers: $modified_headers\n";
echo "Modified tuples: $modified_tuples\n";
echo "Backup saved to: $backup\n";
?>
