<?php
/**
 * @param $line
 * @param $xml
 */

#PORESIT POSLEDNI RADEK BEZ \n

define("HEADER", 21);
define("WRONG_OP", 22);
define("OTHER", 23);
define("MESS_OTHER", "Chyba-ostatni\n");

function checkVar($line, $xml, $cnt) //zkontroluje, jestli je v line var a vrati zbytek za nim
{
    $patternVar = "/^\s+([LTG]F@[\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)(.*\n)/"; #^[LTG]F@([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)^";
    if(preg_match($patternVar, $line, $matches))
    {
        echo "nalezeno var: ".$matches[1]."\n";
        $child = $xml->addChild("arg".$cnt, $matches[1]);
        $child->addAttribute("type", "var");
        return $matches[2];
    }
    else
    {
        echo "var se nenasel\n";
        return null;
    }
}

function checkSym($line, $xml, $cnt)
{
    $patternSym = "/^\s+(string|nil|int|bool)@([^\s#]*)(.*\n)/";
    if(preg_match($patternSym, $line, $matches))
    {
        echo "nalezeno sym: ".$matches[1]."\n";
        $child = $xml->addChild("arg".$cnt, $matches[2]);
        $child->addAttribute("type", $matches[1]);
        return $matches[3];
    }
    else
    {
        echo "sym se nenasel, zkusim var\n";
        return checkVar($line, $xml, $cnt);
    }
}

function checkLabel($line, $xml, $cnt)
{
    $patternLabel = "/^\s+([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)(.*\n)/"; #^[LTG]F@([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)^";
    if(preg_match($patternLabel, $line, $matches))
    {
        $child = $xml->addChild("arg".$cnt, $matches[1]);
        $child->addAttribute("type", "label");
        echo "nalezen label: ".$matches[1]."\n";
        return $matches[2];
    }
    else
    {
        echo "label se nenasel\n";
        return null;
    }
}

function checkType($line, $xml, $cnt)
{
    $patternSym = "/^\s+(string|int|bool)(.*\n)/";
    if(preg_match($patternSym, $line, $matches))
    {
        echo "nalezen type: ".$matches[1]."\n";
        $child = $xml->addChild("arg".$cnt, $matches[1]);
        $child->addAttribute("type", $matches[1]);
        return $matches[3];
    }
    else
    {
        echo "type se nenasel\n";
        return null;
    }
}

function checkEOL($line)
{
    if(preg_match("/^\s*(#.*)?\r?\n/", $line))
        return true;
    return false;
}

function checkHeader()
{
    $line = fgets(STDIN);
    if(!preg_match('/^\.ippcode19(\s*#.*)?\r?\n/i', $line))
    {
        fprintf(STDERR, "Spatna hlavicka ve zdrojovem kodu\n");
        exit(HEADER);
    }
}

echo "start\n";

checkHeader();
$xmlOut = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><program language="IPPcode19"/>');
$counter = 1;
while($line = fgets(STDIN))
{
    if($line[strlen($line)-1] != "\n") //pokud je nacten radek neukonceny znakem \n, pridam ho tam (osetreni posledniho radku souboru)
        $line .="\n";

    $regex = preg_match("/^\s*([A-Z1-9]+)(.*\n)/", $line, $matches);
    if(!$regex) //jeste to muze byt komentar nebo prazdny radek
    {
        echo "vstupni regex se posral:".$line;
        if(preg_match("/^\s*#.*\r?\n/", $line)) #je to komentar
        {
            continue;
        }
        elseif(preg_match("/^\s*\r?\n/", $line)) #je to prazdny radek
        {
            continue;
        }
        else
        {
            fprintf(STDERR, MESS_OTHER);
            exit(OTHER);
        }
    }
    else
    {
        $xmlOp = $xmlOut->addChild("instruction");# order=\"\'.$counter++.\'\" opcode=\"\'.$matches[1].\'\"/>');
        $xmlOp->addAttribute("order", $counter++);
        $xmlOp->addAttribute("opcode", $matches[1]);
        switch ($matches[1])
        {
            case "MOVE":
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
            case "BREAK":
                if(!checkEOL($matches[2]))
                {
                    fprintf(STDERR, MESS_OTHER);
                    exit(OTHER);
                }
                break;
            case "DEFVAR":
            case "POPS":
                $rest = checkVar($matches[2], $xmlOp, 1);
                if(!checkEOL($rest))
                {
                    fprintf(STDERR, MESS_OTHER);
                    exit(OTHER);
                }
                break;
            case "CALL":
            case "LABEL":
            case "JUMP":
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
            case "DPRINT":
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
            case "SETCHAR":
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
            case "TYPE":
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
            case "JUMPIFNEQ": #35
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
            case "READ":
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

/*
        $instr = $xmlOut->addChild('instr', 'MOVE');
$instr->addChild('arg', 'nejaky argument');

print($xmlOut->asXML());
$xmlOut->saveXML("parse_out.xml");
*/
print(str_replace("><",">\n<", $xmlOut->asXML()));

exit(0);
?>