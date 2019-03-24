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



#parametry programu - false pokud ne, true nebo hodnota file/path pokud ano
$params = array(
    RECURSIVE => false,
    PARSE_ONLY => false,
    INT_ONLY => false,
    DIRECTORY => "./",
    PARSE_FILE => "./parse.php",
    INT_FILE => "./interpret.py"
);

$output = 0; #jen nejaka inicializace


checkParams();
$srcFiles = findSrcFiles($params[DIRECTORY], $params[RECURSIVE]);
if(sizeof($srcFiles) == 0)
    return SUCCESS; //neni co testovat
generateEmptyFiles($srcFiles);

foreach ($srcFiles as $key => $srcFile) {
    if(!$params[INT_ONLY])
    {
        $tempFile = generateUniqFileName(getDirectoryFromSrcFile($srcFile));
        $rc_value = file_get_contents(changeExtension($srcFile, "rc"));
        if($rc_value === false)
            exit(OPEN_INPUT_FILE_FAIL);

        $return_value = startParse($params, $srcFile, $tempFile);
        if($params[PARSE_ONLY])
        {
            if(compareXML(changeExtension($srcFile, "out"), $tempFile))
                echo "vystup testu xml ".$srcFile." je ok\n";
            else
                echo "vystup testu xml ".$srcFile." neni ok\n";
            unlink($tempFile);
            if($rc_value == $return_value)
                echo "navratova hodnoza testu ".$srcFile." je ok\n";
            else
                echo "navratova hodnoza testu ".$srcFile."  neni ok\n";
        }
        if($return_value != 0) //chyba v parseru
        {
            unlink($tempFile);
            if($return_value == $rc_value)
                echo "navratova hodnoza testu ".$srcFile." je ok\n";
            else
                echo "navratova hodnoza testu ".$srcFile." neni ok\n";

        }
    }
}

exit(SUCCESS);
//generateUniqFileName($params[DIRECTORY]);
//compareXML("out1", "out11");

/*
$command = "diff out1 out2";
exec($command, $output);
*/

/**
 * @param $oldPath string cesta k souboru
 * @param $newExt string nova pozadovana pripona souboru
 * @return string cesta k souboru s novou priponou
 */
function changeExtension($oldPath, $newExt)
{
    return preg_replace("/(.*\.)src/", "$1".$newExt, $oldPath); //ziskani nazvu odpovidajiciho souboru *.newExt
}

/**
 * @param $filename string musi byt *.src soubor vcetne cesty
 * @return string slozku ve ktere se nachazi filename
 */
function getDirectoryFromSrcFile($filename)
{
    preg_match("/(.+\/).+\.src/", $filename, $matches);
    return $matches[1];
}

/**
 * spusti parser
 * @param $params array: parametry programu - kvuli ziskani nazvu parseru
 * @param $srcFile string ktery zdrojovy soubor presmerovat na vstup parseru
 * @param $destFile string kam se presmeruje stdout z parseru
 * @return string navratova hodnota parseru
 */
function startParse($params, $srcFile, $destFile)
{
    $command = "php7.3 ".$params[PARSE_FILE]." <".$srcFile." >".$destFile;
    exec($command, $output, $return_var);
    return $return_var;
}

/**
 * Ke kazdemu *.src souboru doplni stejne pojmenovane soubory s priponou in, out a rc, pokud neexistuji
 * @param $srcFiles array obsahuje soubory *.src
 */
function generateEmptyFiles($srcFiles)
{
    foreach ($srcFiles as $srcFileKey => $srcFile) {
        $pattern = "/(.+\/.+\.)src/"; //pro ziskani cele cesty k souboru vcetne nazvu ale bez pripony src
        $regex = preg_match($pattern, $srcFile, $matches);
        if(!$regex)
            return;
        if(!file_exists($matches[1]."in")) //pokud neexistuje odpovidajici soubor s priponou in, tak ho vytvorim
            if(false === file_put_contents($matches[1]."in", ""))
                exit(OPEN_OUTPUT_FILE_FAIL);
        if(!file_exists($matches[1]."out"))
            if(false === file_put_contents($matches[1]."out", ""))
                exit(OPEN_OUTPUT_FILE_FAIL);
        if(!file_exists($matches[1]."rc"))
            if(false === file_put_contents($matches[1]."rc", "0"))
                exit(OPEN_OUTPUT_FILE_FAIL);
    }
}

/**
 * @param $path string cesta kde se budou hledat *.src soubory
 * @param $recursive bool vyhledavat i v podslozkach
 * @return array obsahujici seznam nalezenych souboru vcetne cesty k nim
 */
function findSrcFiles($path, $recursive)
{
    $output = array();
    $command = "find %s %s -name '*.src' -printf ";
    if($recursive)
        $command = sprintf($command, $path, "");
    else
        $command = sprintf($command, $path, "-maxdepth 1");

    exec($command."'%p\n'", $output);
    return $output;
}

/**
 * @param $directory string cesta ke slozce ve ktere chceme novy soubor
 * @return string jmeno souboru vcetne cesty
 */
function generateUniqFileName($directory)
{
    do
    {
        $filename = "xbuben05tempfile".rand().".temp";
    }
    while(file_exists($directory.$filename));
    return $directory.$filename;
}

/**
 * @param $filename1 string cesta k 1. porovnavanemu xml souboru
 * @param $filename2 string cesta k 2. porovnavanemu xml souboru
 * @return bool
 */
function compareXML($filename1, $filename2)
{
    $jexamxml_command = "java -jar /pub/courses/ipp/jexamxml/jexamxml.jar ".$filename1." ".$filename2." delta.xml /pub/courses/ipp/jexamxml/options"; //todo pořešit funkčnost bez delta.xml
    $output = array(); //jen deklarace promenne
    exec($jexamxml_command, $output);
    if(array_key_exists(2, $output))
    {
        if($output[2] == "Two files are identical")
        {
            echo "xml jsou stejne\n";
            return true;
        }
        else
        {
            echo "xml NEjsou stejne!!\n";
            return false;
        }
    } //todo když se neco posere a nebudou 3 polozky v poli? - reseni navratovy kod

}

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
            "obou předchozích programů včetně vygenerování přehledného souhrnu v HTML 5 do standardního výstupu.\n";
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

