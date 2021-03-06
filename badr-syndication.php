<?php
/* Plugin name: 네이버 신디케이션 V2
   Plugin URI:http://note.badr.kr/category/syndication/
   Author: changhoon Han
   Author URI: http://note.badr.kr
   Version: 0.7
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

function badr_syndication_setup() {
	if (version_compare($GLOBALS["wp_version"], "2.6", "<")) {
		add_action('admin_notices', 'initError');
		return;
	}
	

	if( isset( $_GET['syndication_feeds'] ) ){
		require_once(trailingslashit(dirname(__FILE__)) . "badr-syndication-front.php");
		new badrSyndicationFront();
	}
	
	if( is_admin() ){
		require_once(trailingslashit(dirname(__FILE__)) . "badr-syndication-admin.php");
		new badrSyndicationAdmin();
	}
}

function initError() {
		echo "<div class='error fade'><p><strong>Naver Syndication 플러그인은 2.6버전 이상이 필요합니다.</strong></p></div>";
}

if(defined('ABSPATH') && defined('WPINC')) {
	badr_syndication_setup();
}