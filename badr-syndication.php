<?php
/* Plugin name: 네이버 신디케이션
   Plugin URI: http://badr.kr
   Author: badr Han
   Author URI: http://badr.kr
   Version: 0.5
   Description: 네이버와 직접 통신을 통해 컨텐츠를 잘 검색되도록 하는 Syndication 규약을 따라 정보를 주고받는 플러그인
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
	if (version_compare($GLOBALS["wp_version"], "3.3", "<")) {
		add_action('admin_notices', 'initError');
		return;
	}
	
	/**
	 * 포스트 ID 나  unixtimestamp 중 하나를  파라미터로 받아들인다.
	 */
	if( isset( $_GET['syndication_feeds'] ) ){
		ChromePhp::log(GetAllHeaders());
		require_once(trailingslashit(dirname(__FILE__)) . "badr-syndication-front.php");
		new badrSyndicationFront();
	}
	
	if( is_admin() ){
		require_once(trailingslashit(dirname(__FILE__)) . "badr-syndication-admin.php");
		new badrSyndicationAdmin();
	}
}

function initError() {
		echo "<div class='error fade'><p><strong>3.5버전 이상이 필요합니다.</strong></p></div>";
}

if(defined('ABSPATH') && defined('WPINC')) {
	badr_syndication_setup();
}