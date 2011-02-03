<?php

error_reporting(E_ALL);

// --- Constants: ---

$dir = dirname(__FILE__);
$kandidatai = "$dir/rinkimai/409_lt/Kandidatai";


// --- Main: ---

// Open output file:
$out = fopen("$dir/kandidatai.csv", "w");

// Field names:
$fields = array(
    "id",
    "vardas",
    "partija",
    "savivaldybe",
    "numeris",
    "gimimo_data",
    "gyvena",
    "nebaigta_bausme",
    "pareigunas",
    "nesuderinamos_pareigos",
    "kt_valst_valdzia",
    "kt_pilietybe",
    "nusikaltimas",
    "gimimo_vieta",
    "tautybe",
    "issilavinimas",
    "mokslo_laipsnis",
    "kalbos",
    "partijos",
    "anksciau_isrinktas",
    "darboviete",
    "pareigos",
    "visuomenine_veikla",
    "pomegiai",
    "seima",
    "sutuoktinis",
    "vaikai",
    
    "turtas",
    "vertybes",
    "pinigai",
    "paskolino",
    "pasiskolino",
    "pajamos",
    "mokesciai",

    "deklaruojantis_asmuo",
    "valst_darboviete",
    "valst_pareigos",
    "darbovietes",
    "sutuoktinis.vardas",
    "sutuoktinis.pavarde",
    "sutuoktinis.darbovietes",
    "juridiniai_asmenys",
    "naryste",
    "dovanos",
    "sandoriai",

    "kita",
);

// Write CSV header:
fputcsv($out, $fields);

$f = array_flip($fields);

echo "Started: " . date("c") . "\n";
$dh = opendir($kandidatai);
while (false !== ($entry = readdir($dh))) {
    if (is_candidate($entry, $kandidatai)) {
        $candidate = get_candidate_data($entry, $kandidatai, $f);
        if (is_array($candidate) && count($candidate)) {
            fputcsv($out, $candidate);
            echo ".";
        } else {
            echo "x";
        }
    } else {
        echo "?";
    }
}
closedir($dh);
fclose($out);

echo "Finished: " . date("c") . "\n";
exit(1);


// --- Functions: ---

/**
 * Checks if the entry is a valid Candidate directory:
 */
function is_candidate($entry, $path) {
   return ($entry != "." && $entry != ".." && preg_match('/Kandidatas\d+/AS', $entry) && is_dir("$path/$entry"));
}


/**
 *
 */
function get_candidate_data($entry, $path, $f) {
    $id = substr($entry, 10);
    $path .= "/Kandidatas$id/Kandidato$id";

    //echo "$id: $path\n";

    // candidate:
    $c = array();

    $c[$f["id"]] = $id;

    $anketa = get_anketa_data($path . "Anketa.html");
    foreach ($anketa as $k => $v) { $c[$f[$k]] = $v; }
    
    $deklaracijos = get_deklaracijos_data($path . "Deklaracijos.html");
    foreach ($deklaracijos as $k => $v) { $c[$f[$k]] = $v; }
        
    $ideklaracija = get_ideklaracija_data($path . "InteresuDeklaracija.html");
    foreach ($ideklaracija as $k => $v) { $c[$f[$k]] = $v; }
        
    $kita = get_kita_data($path . "Kita.html");
    foreach ($kita as $k => $v) { $c[$f[$k]] = $v; }

    return $c;
}


/**
 *
 */
function get_anketa_data($file_name) {
    $data = array();

    $xml = get_xml($file_name);

    // first data table:
    $td = $xml->body->div[0]->div->div[1]->div[1]->div[0]->div[0]->table->tr->td;
    $td_xml = $td->asXml();

    // vardas:
    // /html/body/div/div/div[2]/div[2]/div/div/table/tbody/tr/td/b
    $data["vardas"] = get_xpath_text($td, "b:0");

    $i = 1;

    if (strpos($td_xml, "Savivaldybė:")) {
        $data["savivaldybe"] = get_xpath_text($td, "b:$i");
        $i++;
    }

    if (strpos($td_xml, "Iškėlė:")) {
        $data["partija"] = get_xpath_text($td, "b:$i");
        $i++;
    }

    if (strpos($td_xml, "numeris")) {
        $data["numeris"] = get_xpath_text($td, "b:$i");
        $i++;
    }

    if (strpos($xml->body->div[0]->div->div[1]->div[1]->div[0]->div[1]->asXml(), "Rengiama")) {
        return $data;
    }

    // second data table:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td
    $td2 = $xml->body->div[0]->div->div[1]->div[1]->div[0]->div[1]->table->tr->td;
    $td2_xml = $td2->asXml();

    // gimimo_data:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b
    $data["gimimo_data"] = get_xpath_text($td2, "b:0");

    // gyvena:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[2]
    $data["gyvena"] = get_xpath_text($td2, "b:1");

    // nebaigta_bausme:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[3]
    $data["nebaigta_bausme"] = get_xpath_text($td2, "b:2");

    // pareigunas:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[4]
    $data["pareigunas"] = get_xpath_text($td2, "b:3");

    // nesuderinamos_pareigos:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[5]
    $data["nesuderinamos_pareigos"] = get_xpath_text($td2, "b:4");

    // kt_valst_valdzia:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[6]
    $data["kt_valst_valdzia"] = get_xpath_text($td2, "b:5");

    // kt_pilietybe:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[7]
    $data["kt_pilietybe"] = get_xpath_text($td2, "b:6");

    // nusikaltimas:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[8]
    $data["nusikaltimas"] = get_xpath_text($td2, "b:7");

    // gimimo vieta:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[9]
    $data["gimimo_vieta"] = get_xpath_text($td2, "b:8");

    // tautybe:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[10]
    $data["tautybe"] = get_xpath_text($td2, "b:9");

    
    // issilavinimas:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/table
    $table_i = 0;
    if (strpos($td2_xml, "Išsilavinimas")) {
        $data["issilavinimas"] = get_table_rows($td2->table[$table_i], 2,
                array("Išsilavinimas", "Įstaiga", "Specialybė", "Baigimo metai")
            );
        $table_i++;
    }

    // mokslo_laipsnis
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/b[11]
    $data["mokslo_laipsnis"] = get_xpath_text($td2, "b:10");

    // kalbos:
    $data["kalbos"] = get_xpath_text($td2, "b:11");

    // partijos:
    $data["partijos"] = get_xpath_text($td2, "b:12");

    // pareigos:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/table[2]/tbody/tr[2]
    if (strpos($td2_xml, "Institucijos pavadinimas")) {
        $data["pareigos"] = get_table_rows($td2->table[$table_i], 2,
                array("Institucija,pareigos", "laikotarpis")
            );
    }

    // darboviete:
    $data["darboviete"] = get_xpath_text($td2, "b:13");

    // visuomeninė veikla:
    $data["visuomenine_veikla"] = get_xpath_text($td2, "b:14");

    // pomegiai:
    $data["pomegiai"] = get_xpath_text($td2, "b:15");

    // seima:
    $data["seima"] = get_xpath_text($td2, "b:16");

    $i = 17;
    // sutuoktinis:
    if (strpos($td2_xml, "vyro")) {
        $data["sutuoktinis"] = get_xpath_text($td2, "b:$i");
        $i++;
    }

    // vaikai:
    if (strpos($td2_xml, "vardai")) {
        $data["vaikai"] = get_xpath_text($td2, "b:$i");
    }



    return $data;
}


/**
 *
 */
function get_deklaracijos_data($file_name) {
    $data = array();

    $xml = get_xml($file_name);

    $td = $xml->body->div->div->div[1]->div[1]->div->div[1]->table->tr->td;
    $td_xml = $td->asXml();

    $table_i = 0;
    if (!strpos($td_xml, "Turto deklaracijos duomenys nesuvesti")) {
        $table = $td->table[$table_i];

        $data["turtas"] = get_xpath_text($table, "tr:1/td:1");
        $data["vertybes"] = get_xpath_text($table, "tr:2/td:1");
        $data["pinigai"] = get_xpath_text($table, "tr:3/td:1");
        $data["paskolino"] = get_xpath_text($table, "tr:4/td:1");
        $data["pasiskolino"] = get_xpath_text($table, "tr:5/td:1");

        $table_i++;
    }

    if (!strpos($td_xml, "Pajamų deklaracijos duomenys nesuvesti")) {
        $table = $td->table[$table_i];

        $data["pajamos"] = get_xpath_text($table, "tr:2/td:1");
        $data["mokesciai"] = get_xpath_text($table, "tr:3/td:1");

        $table_i++;
    }

    return $data;
}


/**
 *
 */
function get_ideklaracija_data($file_name) {
    $data = array();

    return $data;
}


/**
 *
 */
function get_kita_data($file_name) {
    $data = array();

    return $data;
}


/**
 *
 */
function get_xml($file_name) {

    $html = file_get_contents($file_name);

    $tidy = tidy_parse_string($html,
            array("clean" => true,
                "output-xhtml" => true,
                "wrap" => 0),
            "UTF8");
    $tidy->cleanRepair();

    $patterns = array(
        "&nbsp;",
    );
    $replacements = array(
        " ",
    );
    $tidy = str_replace($patterns, $replacements, $tidy);

    return new SimpleXMLElement($tidy);
}


/**
 *
 */
function get_xpath_text($node, $path) {

    if (!is_array($path)) {
        $path = explode("/", $path);
    }

    if (!is_object($node)) {
        return $node;
    }

    if (count($path) == 0) {
        return trim(strip_tags($node->asXml()));
    }

    $p = explode(":", array_shift($path));
    $tag = $p[0];

    if (!$node->$tag) {
        return false;
    } else {
        $i = @intval($p[1]);
        return get_xpath_text($node->{$tag}[$i], $path);
    }
}


/**
 *
 */
function get_table_rows($table, $start = 0, $fields = false) {
    //var_dump($start, $fields, $table);
    $fields = $fields ? $fields : array("value");

    $length = $table->count();

    $return = array();
    for ($i=$start;$i<$length;$i++) {
        //echo "--- $i ---\n";
        //var_dump($table->tr[$i]);
        $row = array();
        foreach ($fields as $n => $field) {
            //echo "-- $n : $field --\n";
            $row[$field] = get_xpath_text($table->tr[$i], "td:$n");
            //echo $row[$field] . "\n";
        }
        $return[] = $row;
    }
    return json_encode($return);
}
