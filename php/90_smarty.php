<?php

require_once(APP_ROOT . '/php/libs/smarty/SmartyBC.class.php');

// Smartyシングルトン
class MySmarty extends SmartyBC
{ 
	static $instance = null; 

	public static function getInstance($newInstance = null) 
	{ 
		if( !is_null($newInstance) )
			self::$instance = $newInstance;
		if ( is_null(self::$instance) )
			self::$instance = new MySmarty();
		return self::$instance;
	}

	public function __construct()
	{
		parent::__construct();
		// initialize smarty here
		$this->php_handling = Smarty::PHP_ALLOW;
	}
}
