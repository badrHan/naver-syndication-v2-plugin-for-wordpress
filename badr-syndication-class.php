<?php

abstract class badrSyndication{

	protected $_aOptions = array();
	protected $_aCategory = array();
	protected $_sCategory = null;
	protected $_error = 0;
	
	final function __construct(){
		$this->_aOptions = get_option( '_syndication' );
		if( !empty($this->_aOptions['except_category']) ) {
			$this->_aCategory = array_diff( get_all_category_ids(), explode(',',$this->_aOptions['except_category']));
		} else {
			$this->_aCategory = get_all_category_ids();
		}
		$this->_sCategory = implode(',',$this->_aCategory);
		$this->init();
	}
		
	function init() {}

	protected function _procDB($method, $arr = null){
		if( !class_exists('naverSyndicationDB') )
			require_once(trailingslashit(dirname(__FILE__)) . 'badr-syndication-db.php');
	
		$oDB = &badrSyndicationDB::getInstance();
	
		return call_user_func( array($oDB, $method),  $arr);
	}
}