<?php
/**
 * Created by PhpStorm.
 * User: danbu
 * Date: 10.2.2019
 * Time: 11:45
 */

define("SUCCESS", 0);
define("WRONG_PARAMS", 10);
define("OPEN_INPUT_FILE_FAIL", 11);
define("OPEN_OUTPUT_FILE_FAIL", 12);

define("RECURSIVE", "recursive");
define("PARSE_ONLY", "parse-only");
define("INT_ONLY", "int-only");
define("DIRECTORY", "directory");
define("PARSE_FILE", "parse-script");
define("INT_FILE", "int-script");

$jexamxml_command = "java -jar /pub/courses/ipp/jexamxml/jexamxl.jar";


#parametry programu - false pokud ne, true nebo hodnota file/path pokud ano
$params = array(
    RECURSIVE => false,
    PARSE_ONLY => false,
    INT_ONLY => false,
    DIRECTORY => "./",
    PARSE_FILE => "./parse.php",
    INT_FILE => "./interpret.py"
);


checkParams();
/*
$command = "diff out1 out2";
$output = 0; #jen nejaka inicializace
exec($command, $output);
var_dump($output);
*/

/**
 * Kontroluje parametry programu a nastavuje globalni pole params na spravne hodnoty
 * V pripade --help nebo nepovolene kombinace parametru ukonci program (u --help jeste vypise napovedu)
 */
function checkParams()
{
    global $argc;
    $longopts = array("help", "directory:", "recursive", "parse-script:", "int-script:", "parse-only", "int-only");
    $options = getopt("", $longopts);
    //var_dump($options); smazat

    if($argc != count($options)+1)
    {
        fprintf(STDERR, "Neco je spatne s argumenty programu!\n");
        fflush(STDERR);
        exit(WRONG_PARAMS);
    }
    else if(array_key_exists("help", $options) && count($options) > 1) //--help muze byt vzdy jen samostatne
    {
        fprintf(STDERR, "help muze byt pouzit jedine samostatne!\n");
        fflush(STDERR);
        exit(WRONG_PARAMS);
    }
    else if(array_key_exists("parse-only", $options) && array_key_exists("int-script", $options))
    {
        fprintf(STDERR, "Argumenty parse-only a int-script se nesmi kobinovat!\n");
        fflush(STDERR);
        exit(WRONG_PARAMS);
    }
    else if(array_key_exists("int-only", $options) && array_key_exists("parse-script", $options))
    {
        fprintf(STDERR, "Argumenty int-only a parse-script se nesmi kobinovat!\n");
        fflush(STDERR);
        exit(WRONG_PARAMS);
    }
    else if(array_key_exists("int-only", $options) && array_key_exists("parse-only", $options))
    {
        fprintf(STDERR, "Argumenty int-only a parse-only se nesmi kobinovat!\n");
        fflush(STDERR);
        exit(WRONG_PARAMS);
    }
    else if(array_key_exists("help", $options) && count($options) == 1) //jen --help
    {
        $help_out = "Skript slouží pro automatické testování postupné aplikace parse.php a interpret.py. ".
            "Skript projde zadaný adresář s testy a využije je pro automatické otestování správné funkčnosti ".
            "obou předchozích programů včetně vygenerování přehledného souhrnu v HTML 5 do standardního výstupu.";
        printf($help_out);
        exit(SUCCESS);
    }
    set_params($options);
}

function set_params($options)
{
    global $params;
    if(array_key_exists(DIRECTORY, $options))
        $params[DIRECTORY] = $options[DIRECTORY];
    if(array_key_exists(RECURSIVE, $options))
        $params[RECURSIVE] = true;
    if(array_key_exists(PARSE_FILE, $options))
        $params[PARSE_FILE] = $options[PARSE_FILE];
    if(array_key_exists(INT_FILE, $options))
        $params[INT_FILE] = $options[INT_FILE];
    if(array_key_exists(PARSE_ONLY, $options))
        $params[PARSE_ONLY] = true;
    if(array_key_exists(INT_ONLY, $options))
        $params[INT_ONLY] = true;
}


?>

