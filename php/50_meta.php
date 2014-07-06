<?php

// メタ
// keyは常に小文字とする（※この関数の中で強制的に小文字に変換しているので、各.mdの中で小文字にする必要はない）
function readMetas($text)
{
	$metas = array();
	$lines = explode("\n", $text);
	foreach($lines as $line){
		// meta部分 {* *}
		if(preg_match('/^\\{\\* ?([A-Za-z0-9]+)\\: ?(.+) \\*\\}/', $line, $match)){
			$metas[strtolower($match[1])] = $match[2]; //title,path0,path1,path2
		}
		// meta部分 <!-- -->
		else if(preg_match('/^<!-- ([A-Za-z0-9]+)\\: ?(.+) -->/', $line, $match)){
			$metas[strtolower($match[1])] = $match[2]; //title,path0,path1,path2
		}
	}
	return $metas;
}
