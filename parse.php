<?php
/**
 * @param $line
 * @param $xml
 */

define("HEADER", 21);
define("WRONG_OP", 22);
define("OTHER", 23);
define("MESS_OTHER", "Chyba-ostatni\n");

function checkVar($line, $xml) //zkontroluje, jestli je v line var a vrati zbytek za nim
{
    $patternVar = "/^\s+[LTG]F@([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)(.*\n)/"; #^[LTG]F@([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)^";
    if(preg_match($patternVar, $line, $matches))
    {
        echo "nalezeno var: ".$matches[1]."\n";
        $xml->addChild('arg');
        return $matches[2];
    }
    else
    {
        echo "var se nenasel\n";
        return null;
    }
}

function checkSym($line, $xml)
{
    $patternSym = "/^\s+(string|nil|int|bool)@([^\s#]*)(.*\n)/";
    if(preg_match($patternSym, $line, $matches))
    {
        echo "nalezeno sym: ".$matches[1]."\n";
        $xml->addChild('arg');
        return $matches[3];
    }
    else
    {
        echo "sym se nenasel, zkusim var\n";
        return checkVar($line, $xml);
    }
}

function checkLabel($line, $xml)
{
    $patternLabel = "/^\s+([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)(.*\n)/"; #^[LTG]F@([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)^";
    if(preg_match($patternLabel, $line, $matches))
    {
        echo "nalezen label: ".$matches[1]."\n";
        $xml->addChild('arg');
        return $matches[2];
    }
    else
    {
        echo "label se nenasel\n";
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

while($line = fgets(STDIN))
{
    $regex = preg_match("/^\s*([A-Z1-9]+)(.*\n)/", $line, $matches);
    if(!$regex) //jeste to muze byt komentar nebo prazdny radek
    {
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
        switch ($matches[1])
        {
            case "MOVE":

                $rest = checkVar($matches[2], $xmlOut);
                if($rest === null)
                {
                    echo "rest je null -> neni to var\n";
                    exit(OTHER);
                }
                else
                {
                    echo $rest."\n";
                    $rest = checkSym($rest, $xmlOut);
                }
                if(checkEOL($rest))
                    echo "konec OK\n";
                else
                {
                    echo "spatne konec\n";
                    fprintf(STDERR, MESS_OTHER);
                    exit(OTHER);
                }
                break;
        }
    }
}


/*
if()
{
    echo "je to MOVE\n";
    checkVar($matches[1], $xmlOut);

}
elseif(preg_match("/^\s*CREATEFRAME\s*\r?\n/", $line))
{

}
elseif(preg_match("/^\s*PUSHFRAME\s*\r?\n/", $line))
{

}
elseif(preg_match("/^\s*POPFRAME\s*\r?\n/", $line))
{

}
elseif(preg_match("/^\s*DEFVAR\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*CALL\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*RETURN\s*\r?\n/", $line))
{

}
elseif(preg_match("/^\s*PUSHS\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*POPS\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*ADD\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*SUB\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*MUL\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*IDIV\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*LT\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*GT\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*EQ\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*AND\s*(.*)/", $line, $matches)){

}
elseif(preg_match("/^\s*OR\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*NOT\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*INT2CHAR\s*(.*)/", $line, $matches))
{

}
elseif(preg_match("/^\s*STRI2INT\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*READ\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*WRITE\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*CONCAT\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*STRLEN\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*GETCHAR\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*SETCHAR\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*TYPE\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*LABEL\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*JUMP\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*JUMPIFEQ\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*JUMPIFNEQ\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*EXIT\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*DPRINT\s*(.*)/", $line, $matches))
elseif(preg_match("/^\s*BREAK\s*\r?\n/", $line))
{

}
else
{

}
*/



        $instr = $xmlOut->addChild('instr', 'MOVE');
$instr->addChild('arg', 'nejaky argument');

print($xmlOut->asXML());
$xmlOut->saveXML("parse_out.xml");

exit(0);
?>