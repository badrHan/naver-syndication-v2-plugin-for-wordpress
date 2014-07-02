<?php
if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) exit();

delete_option('_syndication');

global $wpdb;

$sql = "DROP TABLE ".$wpdb->prefix."syndication_deleted_log";
$wpdb->query($sql);
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );