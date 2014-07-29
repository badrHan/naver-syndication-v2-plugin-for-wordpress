<?php
/* Plugin name: 네이버 신디케이션 V2
   Plugin URI: http://badr.kr/?p=1152
   Author: badr Han
   Author URI: http://badr.kr
   Version: 0.7.3
   Description: 네이버 신디케이션 문서란, 웹 사이트의 콘텐츠를 네이버 웹 서비스에 전달할 수 있도록 정해진 형식에 맞춰 작성한 문서입니다
   Max WP Version: 3.9

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
	if( isset( $_GET['syndication_feeds'] ) ){
		require_once(trailingslashit(dirname(__FILE__)) . "badr-syndication-front.php");
		new badrSyndicationFront();
	}
	
	if( is_admin() ){
		require_once(trailingslashit(dirname(__FILE__)) . "badr-syndication-admin.php");
		$badr_syndication_admin = new badrSyndicationAdmin();
		register_activation_hook( __FILE__ , array( $badr_syndication_admin , 'activatePlugin' ));
	}