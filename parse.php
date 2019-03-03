<?php
/**
 * @file parse.php
 * @author Daniel Bubenicek (xbuben05) FIT VUT v Brne
 * @date 2.3.2019
 */

#navratove kody a chybove hlasky
define("SUCCESS", 0);
define("HEADER", 21);
define("WRONG_OP", 22);
define("OTHER", 23);
define("WRONG_ARGS", 10);
define("MESS_OTHER", "Chyba-ostatni\n");

/**
 * @brief Zkontroluje, jestli je na zacatku $line promenna ve spravnem tvaru a prida jeji xml reprezentaci do $xml
 * @param $line string vstupni string kde se hleda promenna
 * @param $xml SimpleXMLElement objekt reprezentujici xml format
 * @param $cnt int poradi argumentu (promenne) v instrukci
 * @return null pokud je promenna nenalezena, jinak zbytek radku za ni
 */
function checkVar($line, $xml, $cnt)
{
    $patternVar = "/^\s+([LTG]F@[\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)(.*\n)/";
    if(preg_match($patternVar, $line, $matches))
    {
        $child = $xml->addChild("arg".$cnt, $matches[1]);
        $child->addAttribute("type", "var");
        return $matches[2];
    }
    else
    {
        return null;
    }
}

/**
 * @brief Zkontroluje, jestli je na zacatku $line literal nebo promenna ve spravnem tvaru a prida jeji xml reprezentaci do $xml
 * @param $line string vstupni string kde se hleda promenna/literal
 * @param $xml SimpleXMLElement objekt reprezentujici xml format
 * @param $cnt int poradi argumentu (promenne/literalu) v instrukci
 * @return null pokud je promenna/literal nenalezena, jinak zbytek radku za ni
 */
function checkSym($line, $xml, $cnt)
{
    $patternSym = "/^\s+(string|nil|int|bool)@([^\s#]*)(.*\n)/";
    if(preg_match($patternSym, $line, $matches))
    {
        $xmlFriendly = str_replace("&", "&amp;", $matches[2]); #nahrada & za escape sekvenci pro xml
        $child = $xml->addChild("arg".$cnt, $xmlFriendly);
        $child->addAttribute("type", $matches[1]);
        return $matches[3];
    }
    else
    {
        return checkVar($line, $xml, $cnt);
    }
}
/**
 * @brief Zkontroluje, jestli je na zacatku $line label ve spravnem tvaru a prida jeho xml reprezentaci do $xml
 * @param $line string vstupni string kde se hleda label
 * @param $xml SimpleXMLElement objekt reprezentujici xml format
 * @param $cnt int poradi argumentu (labelu) v instrukci
 * @return null pokud je label nenalezen, jinak zbytek radku za nim
 */
function checkLabel($line, $xml, $cnt)
{
    $patternLabel = "/^\s+([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)(.*\n)/";
    if(preg_match($patternLabel, $line, $matches))
    {
        $child = $xml->addChild("arg".$cnt, $matches[1]);
        $child->addAttribute("type", "label");
        return $matches[2];
    }
    else
    {
        return null;
    }
}

/**
 * @brief Zkontroluje, jestli je na zacatku $line typ promenne ve spravnem tvaru a prida jeho xml reprezentaci do $xml
 * @param $line string vstupni string kde se hleda typ promenne
 * @param $xml SimpleXMLElement objekt reprezentujici xml format
 * @param $cnt int poradi argumentu (typu promenne) v instrukci
 * @return null pokud je typ promenne nenalezena, jinak zbytek radku za nim
 */
function checkType($line, $xml, $cnt)
{
    $patternSym = "/^\s+(string|int|bool)(.*\n)/";
    if(preg_match($patternSym, $line, $matches))
    {
        $child = $xml->addChild("arg".$cnt, $matches[1]);
        $child->addAttribute("type", "type");
        return $matches[2];
    }
    else
    {
        return null;
    }
}

/**
 * @brief zkontroluje, zda je $line validni konec radku - tedy jen s pripadnym komentarem, white spaces a (CR)LF
 * @param $line string vstup
 * @return bool true pokud je to OK
 */
function checkEOL($line)
{
    if(preg_match("/^\s*(#.*)?\r?\n/", $line))
        return true;
    return false;
}

/**
 * @brief Zkontroluje hlavicku programu, pripadne ukonci program
 */
function checkHeader()
{
    $line = fgets(STDIN);
    if(!preg_match('/^\.ippcode19(\s*#.*)?\r?\n/i', $line))
    {
        fprintf(STDERR, "Spatna hlavicka ve zdrojovem kodu\n");
        exit(HEADER);
    }
}

/**
 * Kontroluje argumenty programu, v pripade potreby vypisuje napovedu a/nebo ukoncuje program
 */
function checkArgs()
{
    global $argc, $argv;
    if($argc == 1)
        return;
    elseif ($argc == 2)
    {
        if($argv[1] == "--help")
        {
            printf("Skript typu filtr nacte ze standardniho vstupu zdrojovy kod v IPPcode19, zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardni vystup XML reprezentaci programu.");
            exit(SUCCESS);
        }
        else
            exit(WRONG_ARGS);
    }
    else
        exit(WRONG_ARGS);
}

#zacatek hlavniho tela
checkArgs();
checkHeader();
$xmlOut = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><program language="IPPcode19"/>');
$counter = 1; #pocitadlo instrukci

while($line = fgets(STDIN))
{
    if($line[strlen($line)-1] != "\n") //pokud je nacten radek neukonceny znakem \n, pridam ho tam (osetreni posledniho radku souboru)
        $line .="\n";

    $regex = preg_match("/^\s*([A-Z1-9]+)(.*\n)/", $line, $matches); #vyhledani opcode
    if(!$regex) //jeste to muze byt komentar nebo prazdny radek
    {
        if(preg_match("/^\s*#.*\r?\n/", $line)) #je to komentar
            continue;
        elseif(preg_match("/^\s*\r?\n/", $line)) #je to prazdny radek
            continue;
        else
        {
            fprintf(STDERR, MESS_OTHER);
            exit(OTHER);
        }
    }
    else
    {
        $xmlOp = $xmlOut->addChild("instruction");
        $xmlOp->addAttribute("order", $counter++);
        $xmlOp->addAttribute("opcode", $matches[1]);

        switch ($matches[1])
        {
            case "MOVE": #var symb
                $rest = checkVar($matches[2], $xmlOp, 1);
                $rest = checkSym($rest, $xmlOp, 2);
                if($rest !== null)
                    if(checkEOL($rest))
                        break;
                fprintf(STDERR, MESS_OTHER);
                exit(OTHER);
                break;
            case "CREATEFRAME":
            case "PUSHFRAME":
            case "POPFRAME":
            case "RETURN":
            case "BREAK": #bez argumentu
                if(!checkEOL($matches[2]))
                {
                    fprintf(STDERR, MESS_OTHER);
                    exit(OTHER);
                }
                break;
            case "DEFVAR":
            case "POPS": #var
                $rest = checkVar($matches[2], $xmlOp, 1);
                if(!checkEOL($rest))
                {
                    fprintf(STDERR, MESS_OTHER);
                    exit(OTHER);
                }
                break;
            case "CALL":
            case "LABEL":
            case "JUMP": #label
                $rest = checkLabel($matches[2], $xmlOp, 1);
                if(!checkEOL($rest))
                {
                    fprintf(STDERR, MESS_OTHER);
                    exit(OTHER);
                }
                break;
            case "PUSHS":
            case "WRITE":
            case "EXIT":
            case "DPRINT": #symb
                $rest = checkSym($matches[2], $xmlOp, 1);
                if(!checkEOL($rest))
                {
                    fprintf(STDERR, MESS_OTHER);
                    exit(OTHER);
                }
                break;
            case "ADD":
            case "SUB":
            case "MUL":
            case "IDIV":
            case "LT":
            case "GT":
            case "EQ":
            case "AND":
            case "OR":
            case "NOT":
            case "STRI2INT":
            case "CONCAT":
            case "GETCHAR":
            case "SETCHAR": #var symb symb
                $rest = checkVar($matches[2], $xmlOp, 1);
                if($rest !== null)
                {
                    $rest = checkSym($rest, $xmlOp, 2);
                    if($rest !== null)
                    {
                        $rest = checkSym($rest, $xmlOp, 3);
                        if(checkEOL($rest))
                            break;
                    }
                }
                fprintf(STDERR, MESS_OTHER);
                exit(OTHER);
                break;
            case "INT2CHAR":
            case "STRLEN":
            case "TYPE": #var symb
                $rest = checkVar($matches[2], $xmlOp, 1);
                if($rest !== null)
                {
                    $rest = checkSym($rest, $xmlOp, 2);
                    if(checkEOL($rest))
                        break;
                }
                fprintf(STDERR, MESS_OTHER);
                exit(OTHER);
                break;
            case "JUMPIFEQ":
            case "JUMPIFNEQ": # label symb symb
                $rest = checkLabel($matches[2], $xmlOp, 1);
                if($rest !== null)
                {
                    $rest = checkSym($rest, $xmlOp, 2);
                    if($rest !== null)
                    {
                        $rest = checkSym($rest, $xmlOp, 3);
                        if(checkEOL($rest))
                            break;
                    }
                }
                fprintf(STDERR, MESS_OTHER);
                exit(OTHER);
                break;
            case "READ": #var type
                $rest = checkVar($matches[2], $xmlOp, 1);
                if($rest !== null)
                {
                    $rest = checkType($rest, $xmlOp, 2);
                    if(checkEOL($rest))
                        break;
                }
                fprintf(STDERR, MESS_OTHER);
                exit(OTHER);
                break;

        }
    }
}

print($xmlOut->asXML());

exit(0);
?>