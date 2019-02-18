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
        $xml->addChild('arg', );
    }
    else
        print("var se nenasel\n");
}

$line = readline();
if(strcmp($line, ".IPPcode19"))
{
    fprintf(STDERR, "Spatna hlavicka ve zdrojovem kodu");
    exit(21);
}
$line = readline();
$xmlOut = new SimpleXMLElement('<xml/>');
checkVar($line, $xmlOut);

if(preg_match("/^MOVE/", $line))
{
    echo "je to MOVE";
}
echo $line."bla";

$instr = $xmlOut->addChild('instr', 'MOVE');
$instr->addChild('arg', 'nejaky argument');
print($xmlOut->asXML());
exit(0);
?>