<?php
$line = readline();
if(strcmp($line, ".IPPcode19"))
{
    fprintf(STDERR, "Spatna hlavicka ve zdrojovem kodu");
    exit(21);
}
$line = readline();
$patternVar = "^[LTG]F@[\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*";
if(preg_match("/^MOVE/", $line))
{
    echo "je to MOVE";
}
echo $line."bla";
exit(0);
?>