<?php
/**
 * @param $line
 * @param $xml
 */
function checkVar($line, $xml)
{
    $patternVar = "^[LTG]F@([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)^";
    if(preg_match($patternVar, $line, $matches))
    {
        print("nalezeno var: ".$matches[1]."\n");
        $xml->addChild('arg');
    }
    else
        print("var se nenasel\n");
}


print("start");

$line = fgets(STDIN);
if(!preg_match('/^\.ippcode19\r?\n/i', $line))
{
    fprintf(STDERR, "Spatna hlavicka ve zdrojovem kodu\n");
    exit(21);
}
$xmlOut = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><program language="IPPcode19"/>');

$line = fgets(STDIN);
if(preg_match("/^\s*MOVE\s*(.*)/", $line, $matches))
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




        $instr = $xmlOut->addChild('instr', 'MOVE');
$instr->addChild('arg', 'nejaky argument');

print($xmlOut->asXML());
$xmlOut->saveXML("parse_out.xml");

exit(0);
?>