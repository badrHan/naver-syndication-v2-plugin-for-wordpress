<?php

abstract class badrSyndication{

	protected $aOptions = array();
	protected $aCategory = array();
	protected $sCategory = null;
	protected $error = 0;
	
	final function __construct(){
		$this->aOptions = get_option( '_syndication' );
		if( !empty($this->aOptions['except_category']) ) {
			$this->aCategory = array_diff( get_all_category_ids(), explode(',',$this->aOptions['except_category']));
			$this->sCategory = implode(',',$this->aCategory);
		}
		$this->init();
	}
		
	function init() {}
	
	function __clone() { 
		return new WP_Error( 'broke', __CLASS__ . 'clone is not allowed.' );
	} 

}