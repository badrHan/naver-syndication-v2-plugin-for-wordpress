<?php
if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) exit();

require_once (trailingslashit ( dirname ( __FILE__ ) ) . 'badr-syndication-class.php');

class badr_syndication_uninstall extends badrSyndication{
	
	function init() {
		delete_option('_syndication');
		
		$this->_procDB('deleteTable');
	}
}

new badr_syndication_uninstall();