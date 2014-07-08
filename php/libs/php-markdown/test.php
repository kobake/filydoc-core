<?php

// Install PSR-0-compatible class autoloader
spl_autoload_register(function($class){
	require preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
});

// Get Markdown class
// use \Michelf\Markdown;
use \Michelf\MarkdownExtra;

# Read file and pass content through the Markdown parser
$text = <<<"EOS"
hogehoge
_hoge_a<br/>
_hoge_

hoge_hoge
hoge_
EOS;
$html = MarkdownExtra::defaultTransform($text);
echo $html;
?>
