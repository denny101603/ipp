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

$okTestsCnt = 0;
$failTestsCnt = 0;

checkParams();
$srcFiles = findSrcFiles($params[DIRECTORY], $params[RECURSIVE]);
generateHTMLhead();

if(sizeof($srcFiles) == 0)
{
    generateHTMLend();
    return SUCCESS; //neni co testovat
}
generateEmptyFiles($srcFiles);


foreach ($srcFiles as $key => $srcFile)
{
    $rc_value = file_get_contents(changeExtension($srcFile, "rc"));
    if($rc_value === false)
        exit(OPEN_INPUT_FILE_FAIL);
    if(!$params[INT_ONLY])
    {
        $tempFileParseOut = generateUniqFileName($params[DIRECTORY]);

        $return_value = startParse($params, $srcFile, $tempFileParseOut);
        if($params[PARSE_ONLY])
        {
            $isOutputOk =compareXML(changeExtension($srcFile, "out"), $tempFileParseOut);
            if($isOutputOk && $rc_value == $return_value)
                generateHTMLtestSuccess($srcFile);
            else
                generateHTMLtestFail($srcFile, $return_value, $rc_value, $isOutputOk);
            unlink($tempFileParseOut);
        }
        else
        {
            if($return_value != 0) //chyba v parseru
            {
                unlink($tempFileParseOut);
                if ($return_value == $rc_value)
                    generateHTMLtestSuccess($srcFile);
                else
                    generateHTMLtestFail($srcFile, $return_value, $rc_value, true);
                break;
            }
            $tempFileIntOut = generateUniqFileName($params[DIRECTORY]);
            $return_value = startInterpret($params, $tempFileParseOut, changeExtension($srcFile, "in"), $tempFileIntOut);
            unlink($tempFileParseOut);
            $isOutputOk = compareDiff(changeExtension($srcFile, "out"), $tempFileIntOut);
            if($isOutputOk && $rc_value == $return_value)
                generateHTMLtestSuccess($srcFile);
            else
                generateHTMLtestFail($srcFile, $return_value, $rc_value, $isOutputOk);
            unlink($tempFileIntOut);
        }
    }
    else
    {
        $tempFileIntOut = generateUniqFileName($params[DIRECTORY]);
        $return_value = startInterpret($params, $srcFile, changeExtension($srcFile, "in"), $tempFileIntOut);
        $isOutputOk = compareDiff(changeExtension($srcFile, "out"), $tempFileIntOut);
        if($isOutputOk && $rc_value == $return_value) //uspesny test
            generateHTMLtestSuccess($srcFile);
        else
            generateHTMLtestFail($srcFile, $return_value, $rc_value, $isOutputOk);
        unlink($tempFileIntOut);
    }
}
generateHTMLend();
exit(SUCCESS);

function generateHTMLhead()
{
    printf("<!doctype html>\n");
    printf("<html lang=\"en\">\n");
    printf("<head><meta charset=\"utf-8\"><title>Test.php</title></head>\n");
    printf("<font size='+2'>Výsledky testů:</font>\n");
    printf("<body>\n");
}

function generateHTMLtestSuccess($path)
{
    global $okTestsCnt;
    $okTestsCnt++;
    printf("<p><b><font color='green' size='+1'>Test <ins>".$path."</ins> byl úspěšný!</font></br></b>\n");
    printf("<font color='green'>Návratová hodnota i výstup je v pořádku.</font></p>\n");
}

function generateHTMLtestFail($path, $actualRetCode, $expectedRetCode, $output)
{
    global $failTestsCnt;
    $failTestsCnt++;
    printf("<p><b><font color='red' size='+1'>Test <ins>".$path."</ins> selhal!</font></br></b>\n");
    if($output) //vystup v poradku
        printf("<font color='green'>Výstup je v pořádku.</br></font>\n");
    else
        printf("<font color='red'>Výstup je chybný.</br></font>\n");

    if($actualRetCode == $expectedRetCode)
        printf("<font color='green'>Návratová hodnota je v pořádku.</font></p>\n");
    else
        printf("<font color='red'>Návratová hodnota je ".$actualRetCode." ale očekávala se ".$expectedRetCode.".</font></p>\n");
}

function generateHTMLend()
{
    global $okTestsCnt, $failTestsCnt;
    printf("<font size='+2'>Celkem proběhlo ".($okTestsCnt+$failTestsCnt)." testů, z toho ".$okTestsCnt." úspěšně a ".$failTestsCnt." selhalo.</font>\n");
    printf("</body></html>\n");
}
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
 * @param $params array: parametry programu - kvuli ziskani nazvu parseru
 * @param $sourceFile string zdrojovy program v xml pro interpret
 * @param $inputFile string jako vstup pro interpret
 * @param $destFile string kam se presmeruje stdout z interpretu
 * @return int navratova hodnota interpretu
 */
function startInterpret($params, $sourceFile, $inputFile, $destFile)
{
    $command = "python3.6 \"".$params[INT_FILE]."\" --source=\"".$sourceFile."\" --input=\"".$inputFile."\" >\"".$destFile."\"";
    exec($command, $output, $return_var);
    return $return_var;
}

/**
 * spusti parser
 * @param $params array: parametry programu - kvuli ziskani nazvu parseru
 * @param $srcFile string ktery zdrojovy soubor presmerovat na vstup parseru
 * @param $destFile string kam se presmeruje stdout z parseru
 * @return int navratova hodnota parseru
 */
function startParse($params, $srcFile, $destFile)
{
    $command = "php7.3 \"".$params[PARSE_FILE]."\" <\"".$srcFile."\" >\"".$destFile."\"";
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
    else return false;
}

/**
 * @param $filename1 string prvni soubor pro porovnani
 * @param $filename2 string druhy soubor pro porovnani
 * @return bool
 */
function compareDiff($filename1, $filename2)
{
    $command = "diff \"".$filename1."\" \"".$filename2."\"";
    exec($command, $output, $return_var);
    return $return_var == 0;
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
