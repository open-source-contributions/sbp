<?php /* Generated By SBP */ 
$a = $a && true;
$a = $a || true;
$a = ($a and true);
$a = ($a or true);
$a = ($a xor false);
$a = $a === true;
$a = $a !== false;
$a = $a <> "abc";
$a = $a < 25;
$a = $a > -12.5/2;
$a = $a <= 25;
$a = $a >= 0;
$a = $a === "foo";
$a = $a !== "bar";
$a = $a ?: "default value";
// Équivalent pour PHP < 5.3
if(!$a) { $a = "default value"; }
// Set default value only if it not set
if(!isset($a)) { $a = "default value"; }

/*
 * is, not, lt et gt sont aussi
 * disponibles dans le contexte normal
 */

if ($a === 23) {
	echo "a is 23";
}if ($a !== 23) {
	echo "a is not 23";
}if ($a < 23) {
	echo "a is lesser than 23";
}if ($a > 23) {
	echo "a is greater than 23";
}?>