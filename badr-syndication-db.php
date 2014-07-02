<?php

class badrSyndicationDB{

	private static $_instance = null;

	private final function __construct(){
		global $wpdb;
		if( $wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."syndication_deleted_log'") == $wpdb->prefix."syndication_deleted_log" ) return;
		
		$this->_createTable();
	}

	public static function &getInstance() {
		if( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	private function _createTable(){

		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."syndication_deleted_log 
		(
		uid int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(uid),
		cat_ID varchar(255),
		ID int(11),
		title varchar(255),
		link varchar(255),
		post_deleted_gmt datetime
		) CHARACTER SET=utf8 COLLATE utf8_general_ci";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		ChromePhp::log($sql);
	}
	

	function _insertLog($aPost){
		global $wpdb;
		if($wpdb->get_var( "SELECT COUNT(*) FROM ".$wpdb->prefix."syndication_deleted_log where ID=".$aPost[0]->ID." and cat_ID=".$aPost[1] )) return;
			$arr = array(
				'cat_ID' => $aPost[1],
				'ID' => $aPost[0]->ID,
				'title' => $aPost[0]->post_title,
				'link' => get_permalink( $aPost[0]->ID ),
				'post_deleted_gmt' => current_time( 'mysql' , 1 )
				);
			
				$wpdb->insert( $wpdb->prefix."syndication_deleted_log" , $arr);
		ChromePhp::log(__METHOD__, $arr);
	}

	function _deleteLog( $arr ){
		//ns_log($arr,1,0);
		global $wpdb;
		$wpdb->delete( $wpdb->prefix."syndication_deleted_log" , array('ID' => $arr[0]->ID, 'cat_ID' => $arr[1]));
		ChromePhp::log(__METHOD__,$arr);
	}
	
	function _updatePostSyndication( $arr ){
		ns_log($arr ,1,0);
		global $wpdb;
		$wpdb->update( 
			$wpdb->posts, 
			array( 
				'post_status' => 'publish',	// string
				'post_password' => ''	// integer (number) 
			), 
			array( 'ID' => $arr[0] ), 
			array( 
				'%s',	// value1
				'%s'	// value2
			), 
			array( '%d' ) 
		);
	}
}