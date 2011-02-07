<?php

error_reporting(E_ALL);

// --- Constants: ---

$dir = dirname(__FILE__);
$kandidatai = "$dir/rinkimai/409_lt/Kandidatai";


// Field names:
$fields = array(
    "id",
    "vardas",
    "partija",
    "iskele",
    "iskele.nr",
    "savivaldybe",
    "numeris",
    "gimimo_data",
    "gyvena",
    "nebaigta_bausme",
    "pareigunas",
    "nesuderinamos_pareigos",
    "kt_valst_valdzia",
    "kt_pilietybe",
    "pilietybe.salis",
    "kt_pasyvioji_teise",
    "nusikaltimas",
    "nusikaltimas.pastaba",
    "gimimo_vieta",
    "tautybe",
    "issilavinimas",
    "mokslo_laipsnis",
    "kalbos",
    "partijos",
    "anksciau_isrinktas",
    "darboviete",
    //"pareigos",
    "visuomenine_veikla",
    "pomegiai",
    "seima",
    "sutuoktinis",
    "vaikai",
    "apie_save",
    
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


// --- Main: ---

// Open output file:
$out = fopen("$dir/kandidatai.csv", "w");

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
    $c = array_fill(0, count($f) - 1, "");

    $c[$f["id"]] = $id;

    $anketa = get_anketa_data($path . "Anketa.html");
    foreach ($anketa as $k => $v) { $c[$f[$k]] = $v; }
    
    $deklaracijos = get_deklaracijos_data($path . "Deklaracijos.html");
    foreach ($deklaracijos as $k => $v) { $c[$f[$k]] = $v; }
        
    $ideklaracija = get_ideklaracija_data($path . "InteresuDeklaracija.html");
    foreach ($ideklaracija as $k => $v) { $c[$f[$k]] = $v; }
        
    $kita = get_kita_data($path . "Kita.html");
    foreach ($kita as $k => $v) { $c[$f[$k]] = $v; }

    // Remove newlines and other special chars:
    $c = array_map(function($item) {
            return trim(str_replace(array("\r", "\n"), " ", $item));
    }, $c);

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
    if (!$td) {
        echo "\nFailed to get anketa first TD in $file_name.\n";
        return $data;
    }
    $td_xml = $td->asXml();

    $fields = array(
        "Savivaldybė:" => "savivaldybe",
        "Iškėlė:" => "partija",
        "numeris sąraše:" => "numeris",
    );
    $skip = array(
        "Politinių partijų ir kandidatų atstovus rinkimams, kandidatus kviečiame susipažinti su skelbiamais duomenimis ir, jei pastebėsite netikslumus ar neatitikimus pareiškiniams dokumentams, prašome skubiai kreiptis į savivaldybių rinkimų komisijas",
        ")",
    );

    $parts = get_anketa_parts($td_xml);
    $field_name = "vardas";
    while ($part = array_shift($parts)) {
        if (array_key_exists($part, $fields)) {
            $field_name = $fields[$part];
        } else if ($part == "Išsikėlęs kandidatas") {
            $data["iskele"] = "pats";
        } else if ($part == "(Iškėlė:") {
            $field_name = "iskele";
            $fields["numeris sąraše:"] = "iskele.nr";
        } else if (!in_array($part, $skip)) {
            if (array_key_exists($field_name, $data)) {
                $data[$field_name] .= " $part";
            } else {
                $data[$field_name] = $part;
            }
        }
    }

    $data["vardas"] = ucwords(mb_strtolower(
        preg_replace('/\s+/', " ", $data["vardas"]),
        "UTF-8"));


    if (strpos($xml->body->div[0]->div->div[1]->div[1]->div[0]->div[1]->asXml(), "Rengiama")) {
        return $data;
    }

    // second data table:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td
    $td2 = $xml->body->div[0]->div->div[1]->div[1]->div[0]->div[1]->table->tr->td;
    $td2_xml = $td2->asXml();


    $fields = array_flip(array(
        "gimimo_data" => "5. Gimimo data",
        "gyvena" => "6. Nuolatinės gyvenamosios vietos adresas",
        "nebaigta_bausme" => "8.1 Ar neturite nebaigtos atlikti teismo nuosprendžiu paskirtos bausmės?",
        "pareigunas" => "8.2 Ar nesate asmuo, atliekantis privalomąją karo arba alternatyviąją krašto apsaugos tarnybą, neišėjęs į atsargą ar pensiją profesinės karo tarnybos karys, statutinės institucijos ar įstaigos pareigūnas, kuriam pagal specialius įstatymus ar statutus apribota teisė dalyvauti politinėje veikloje?",
        "nesuderinamos_pareigos" => "8.3 Ar einate pareigas, nesuderinamas su savivaldybės tarybos nario pareigomis? 90 str. 1 d. „Savivaldybės tarybos nario pareigos nesuderinamos su Respublikos Prezidento, Seimo nario, Europos Parlamento nario, Vyriausybės nario pareigomis, su Vyriausybės įstaigos ar įstaigos prie ministerijos vadovo, kurio veikla susijusi su savivaldybių veiklos priežiūra ir kontrole, pareigomis, su Vyriausybės atstovo apskrityje pareigomis, su valstybės kontrolieriaus ir jo pavaduotojo pareigomis. Be to, savivaldybės tarybos nario pareigos nesuderinamos su tos savivaldybės mero politinio (asmeninio) pasitikėjimo valstybės tarnautojo pareigomis, tos savivaldybės kontrolieriaus ar tos savivaldybės kontrolieriaus tarnybos valstybės tarnautojo pareigomis, su tos savivaldybės administracijos direktoriaus ir jo pavaduotojo ar tos savivaldybės administracijos valstybės tarnautojo ir darbuotojo, dirbančio pagal darbo sutartis, pareigomis, su tos savivaldybės biudžetinės įstaigos vadovo pareigomis, tos savivaldybės viešosios įstaigos, tos savivaldybės įmonės vienasmenio vadovo ir kolegialaus valdymo organo nario pareigomis, tos savivaldybės kontroliuojamos akcinės bendrovės kolegialaus valdymo organo (valdybos) nario pareigomis arba tos savivaldybės kontroliuojamos akcinės bendrovės vadovo pareigomis.“",
        "kt_valst_valdzia" => "8.4 Ar esate kitos valstybės renkamos valdžios institucijos narys?",
        "kt_pilietybe" => "8.5 Ar turite kitos valstybės pilietybę?",
        "pilietybe.salis" => "8.5.1 Jeigu turite, prašome nurodyti, kokios?",
        "kt_pasyvioji_teise" => "8.5.2 ar Jūsų pasyvioji rinkimų teisė nėra apribota valstybėje, kurios pilietis Jūs esate?",
        "nusikaltimas" => "9. Ar turite ką nurodyti pagal Lietuvos Respublikos savivaldybių tarybų rinkimų įstatymo 89 straipsnio 1 dalyje išdėstytus reikalavimus, jeigu taip, įrašykite čia:",
        "nusikaltimas.pastaba" => "Jeigu į 9 p. klausimą atsakėte „Taip“ ir norite papildomai apie tai paaiškinti, tai įrašykite čia:",
        "gimimo_vieta" => "10. Gimimo vieta",
        "tautybe" => "11. Tautybė",
        "mokslo_laipsnis" => "Jei turite, nurodykite mokslo laipsnį",
        "kalbos" => "13. Kokias užsienio kalbas mokate",
        "partijos" => "14. Kokios partijos, politinės organizacijos narys esate (buvote)",
        "darboviete" => "16. Pagrindinė darbovietė, pareigos",
        "visuomenine_veikla" => "17. Visuomeninė veikla",
        "pomegiai" => "18. Pomėgiai",
        "seima" => "19. Šeiminė padėtis",
        "sutuoktinis" => "Vyro arba žmonos vardas (pavardė)",
        "vaikai" => "20. Vaikų vardai (pavardės)",
        "apie_save" => "21. Be jau išvardytų atsakymų, ką dar norėtumėte parašyti apie save",
    ));
    $fields["Jei turite, nurodykite mokslo vardą"] = "mokslo_laipsnis";
    $skip = array(
        "8. Pagal Lietuvos Respublikos savivaldybių tarybų rinkimų įstatymo 35 straipsnio 12 dalį Jūs turite atsakyti į šiuos klausimus:",
        "89 str. 1 d. „Kiekvienas kandidatas turi viešai paskelbti, jeigu jis po 1990 m. kovo 11 d. Lietuvos Respublikos ar užsienio valstybės teismo įsiteisėjusiu nuosprendžiu (sprendimu) buvo pripažintas kaltu dėl nusikalstamos veikos arba įsiteisėjusiu Lietuvos Respublikos ar užsienio valstybės teismo nuosprendžiu (sprendimu) bet kada buvo pripažintas kaltu dėl sunkaus ar labai sunkaus nusikaltimo. Apie tai jis nurodo kandidato į savivaldybės tarybos narius anketoje, nesvarbu, ar teistumas pasibaigęs ar panaikintas. Rinkimų komisijos leidžiamame kandidato plakate ar plakate su kandidatų sąrašu, prie kandidato pavardės turi būti pažymėta: „Teismo nuosprendžiu buvo pripažintas kaltu dėl nusikalstamos veikos“. Tai pažymėti neprivaloma, jeigu asmuo okupacinio režimo teismo buvo pripažintas kaltu dėl nusikaltimo valstybei.\"",
    );
    $parts = get_anketa_parts($td2_xml);
    $field_name = false;

    while ($part = array_shift($parts)) {
        if (array_key_exists($part, $fields)) {
            $field_name = $fields[$part];
        } else if (!in_array($part, $skip)) {
            if (array_key_exists($field_name, $data)) {
                $data[$field_name] .= " $part";
            } else {
                $data[$field_name] = $part;
            }
        }
    }

    
    // issilavinimas:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/table
    $table_i = 0;
    if (strpos($td2_xml, "12. Išsilavinimas:")) {
        $data["issilavinimas"] = get_table_rows($td2->table[$table_i], 2,
                array("Išsilavinimas", "Įstaiga", "Specialybė", "Baigimo metai")
            );
        $table_i++;
    }

    // pareigos:
    // /html/body/div/div/div[2]/div[2]/div/div[2]/table/tbody/tr/td/table[2]/tbody/tr[2]
    if (strpos($td2_xml, "Institucijos pavadinimas")) {
        $data["anksciau_isrinktas"] = get_table_rows($td2->table[$table_i], 2,
                array("Institucija,pareigos", "laikotarpis")
            );
    }


    return $data;
}


    /**
     *
     */
    function format_money($str) {
        return trim(str_replace(array(",", "Lt"), array(".", ""), $str));
    }

/**
 *
 */
function get_deklaracijos_data($file_name) {
    $data = array();

    $xml = get_xml($file_name);

    $td = $xml->body->div->div->div[1]->div[1]->div->div[1]->table->tr->td;
    if (!$td) {
        echo "\nFailed to get deklaracijos data from $file_name.\n";
    }
    $td_xml = $td->asXml();

    $table_i = 0;
    if (!strpos($td_xml, "Turto deklaracijos duomenys nesuvesti")) {
        $table = $td->table[$table_i];

        $data["turtas"] = format_money(get_xpath_text($table, "tr:1/td:1"));
        $data["vertybes"] = format_money(get_xpath_text($table, "tr:2/td:1"));
        $data["pinigai"] = format_money(get_xpath_text($table, "tr:3/td:1"));
        $data["paskolino"] = format_money(get_xpath_text($table, "tr:4/td:1"));
        $data["pasiskolino"] = format_money(get_xpath_text($table, "tr:5/td:1"));

        $table_i++;
    }

    if (!strpos($td_xml, "Pajamų deklaracijos duomenys nesuvesti")) {
        $table = $td->table[$table_i];

        $data["pajamos"] = format_money(get_xpath_text($table, "tr:2/td:1"));
        $data["mokesciai"] = format_money(get_xpath_text($table, "tr:3/td:1"));

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

    // Replace quote characters with unicode equivalent before returning:
    return json_encode($return, JSON_HEX_QUOT);
}


/**
 *
 */
function get_anketa_parts($xml) {
    $xml = preg_replace('|<table.*</table>|msS', "<br/>", $xml);

    $parts = preg_split('{<(b|/b|br/|br /)>}S', $xml, null, PREG_SPLIT_NO_EMPTY);
    $parts = array_map(function($item) {
            return trim(strip_tags(str_replace(array("\n", "\r",), " ", $item)));
    }, $parts);

    $parts = array_filter($parts, function ($item) { return $item; });

    return $parts;
}



