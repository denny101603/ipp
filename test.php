<?php
/**
 * Created by PhpStorm.
 * User: xbuben05
 * Date: 3/5/19
 * Time: 1:05 PM
 */

echo "pocet argumentu:".$argc."\n";
$longopts = array("help", "directory:", "recursive", "parse-script:", "int-script:", "parse-only", "int-only");
$options = getopt("", $longopts);
var_dump($options);

if($argc != count($options)+1)
{
    fprintf(STDERR, "Neco je spatne s argumenty programu!\n");
    fflush(STDERR);
    exit(10);
}
else if(array_key_exists("help", $options) && count($options) > 1) //--help muze byt vzdy jen samostatne
{
    fprintf(STDERR, "help muze byt pouzit jedine samostatne!\n");
    fflush(STDERR);
    exit(10);
}
else if(array_key_exists("parse-only", $options) && array_key_exists("int-script", $options))
{
    fprintf(STDERR, "Argumenty parse-only a int-script se nesmi kobinovat!\n");
    fflush(STDERR);
    exit(10);
}
else if(array_key_exists("int-only", $options) && array_key_exists("parse-script", $options))
{
    fprintf(STDERR, "Argumenty int-only a parse-script se nesmi kobinovat!\n");
    fflush(STDERR);
    exit(10);
}
echo "vse ok";
    ?>
