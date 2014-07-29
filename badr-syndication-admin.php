<?php
require_once(trailingslashit(dirname(__FILE__)) . 'badr-syndication-class.php');

class badrSyndicationAdmin extends badrSyndication{
	
	var $plugin_name = 'badr-syndication';
	
	function init() {
		add_action( 'admin_init', array( &$this, 'initAdmin' ) );
		add_action( 'admin_menu', array( &$this, 'initAdminPage') );	
	}
	
	function initAdmin() {
		//add_action( 'ns_update_category', array( &$this, 'setExCategory'), 10, 1 ); //제외카테고리설정반영
		add_action( 'wp_ajax_configCheck', array( &$this, 'adminConfigCheck'));
		add_action( 'wp_ajax_sendPagePing', array( &$this, 'sendPagePing'));
		add_action( 'wp_ajax_getIndexed', array( &$this, 'getIndexed'));
		add_action( 'save_post', array( &$this, 'procSavePing'), 10, 2);
		add_action( 'trashed_post', array( &$this, 'procTrashPing'), 10, 1);
		add_action( 'untrashed_post', array( &$this, 'procTrashPing'), 10, 1);
	}
	
	function initAdminPage() {
		$suffix = add_options_page( '네이버 신디케이션', '네이버 신디케이션', 'manage_options', $this->plugin_name, array( &$this , 'dispManagementPage') );
	 	add_action( 'admin_print_scripts-' . $suffix, array( &$this , 'loadAdminScript') );
		add_meta_box( 'badr_syndication_metabox', '네이버 신디케이션', array( &$this, 'dispMetabox'),'post','side','default');
	 	add_action( 'admin_enqueue_scripts', array( &$this , 'loadMetaboxScript'));
	}

	
	/*
	 * FIXME 제외 카테고리 추가시 발행된 해당 포스트를 삭제 처리 루틴 작성
	 */
	function setExCategory( $aExCategory ){
			$aCheckForPing = array_diff( $aExCategory , $this->_aCategory );
			//if( count($aCheckForPing) > 0 ) $this->procPingCategory();
	}

	function checkValidation( $id ){
		if( empty($_GET['validate']) ) return true;
		if( !extension_loaded('DOM') ) die( '996' );
		$response = wp_remote_get( get_option('siteurl').'/?syndication_feeds='.$id);
		$xml= new DOMDocument();
		$xml->loadXML($response['body']);
		$xsd_file = trailingslashit(dirname(__FILE__)).'inc/syndi.xsd';
		if ( !$xml->schemaValidate($xsd_file) ) return false;
		return true;
	}	
	
	function adminConfigCheck(){
		if( empty($this->_aOptions['key']) ) die('999');
		$id = 'page-1.xml';
		if( !$this->checkValidation( $id ) ) die('998');
		$response = $this->ping( $id );
		$this->pingResponse($response['body']);
	}
	
	function pingResponse( $response ) {
		$oXml = @simplexml_load_string( $response );
		if( isset($oXml->message) ) {
			die($oXml->error_code);
		}else{
			die('997');
		}
	}
	/**
	 * badr.kr api서버로 부터 사이트 url 등록된 색인 목록을 받아온다. 
	 * @since 0.8
	 */
	function getIndexed(){
		if(isset($_GET['page'])) return $this->sendResetPing($_GET['page']);
		$url = 'http://api.badr.kr' . (isset($_GET['start']) ? '/?start='.$_GET['start'] : '');
		ChromePhp::log($url);
		$result = wp_remote_get( $url );
		$data = json_decode($result['body']);
		$this->_procDB('emptyIndexedLog');
		$this->_procDB('indexedLog',$data->links);
		die( (string)$data->total );
	}
	
	function sendResetPing( $page ){
		$response = $this->ping( 'list-'.$page.'.xml' );
		$this->pingResponse($response['body']);
	}
	/*
	 * FIXME 컬럼에 post meta값 추가할것
	 */ 
	function sendPagePing(){
		if(empty($_GET['ping_url'])){
			$arg = array(
				'post_type' => $this->_aOptions['post_type'],
				'post_status' => 'publish',
				'has_password' => false ,
				'posts_per_page' => 100,
				'category__in' => $this->_aCategory
			);
			$query = new WP_Query( $arg );
			$output = array('pages' => $query->max_num_pages);
			die(json_encode($output));
		}else{
			$ping_url = $_GET['ping_url'];
			$result = $this->ping($ping_url);
			$oXml = simplexml_load_string($result['body']);
			die(json_encode(array( 'ping_url' => get_option('siteurl').'/?syndication_feeds='.$ping_url, 'message' => (string) $oXml->message )));
		}
	}
	
	function procTrashPing( $post_id ){
		$this->ping('post-'.$post_id.'.xml');
	}
	
	function procSavePing( $post_id, $oPost ){
  		if( !isset($_POST['_syndication_is_off']) || !isset($_POST['_syndication_do_off'])) return;
  		$is_off = (int) $_POST['_syndication_is_off'];
  		$do_off = (int) $_POST['_syndication_do_off'];
  		
  		if ( !$category_id = $this->_checkMetaData( $oPost ) ) return;
	  	if ( !$this->_savePostMeta( $post_id, $oPost ) ) return;
	  	if ( $is_off && $do_off ) return;
	  	
		$this->ping('post-'.$oPost->ID.'.xml');
	}

	function _checkMetaData( $oPost ){
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return false;
		if ( !in_array( $oPost->post_type, $this->_aOptions['post_type']) ) return false;

		$oCategory = get_the_category($oPost->ID);
		if ( !in_array( $oCategory[0]->cat_ID, $this->_aCategory ) ) {
			$this->_savePostMeta( $oPost->ID, $oPost );
			return false;			
		}
		return $oCategory[0]->cat_ID;
	}

	function _savePostMeta( $post_id, $oPost ) {
		if ( wp_is_post_revision( $oPost ) ) return false;
		if ( empty($_POST['_syndication_metabox_flag']) ) return false;
		update_post_meta($post_id, '_syndication', $_POST['_syndication_do_off']);
		return true;
	}

	function updateConfig( $input ){
		$option_name = '_syndication';
		if ( get_option( $option_name ) !== false ) {
			$bResult = update_option( $option_name, $input );
		} else {
			$bResult = add_option( $option_name, $input, null, 'no' );
		}
		$this->_aOptions = $input;
		return $bResult;
	}
	
	function loadMetaboxScript( $hook ){
    	if ( !in_array($hook, array('post.php','post-new.php')) ) return;
    	$bIsNewPost = isset($_GET['post']) ? 0 : 1;
	 	wp_register_script( 'badr-syndication-script', plugins_url( 'js/badr-syndication-metabox.js', __FILE__ ), array("jquery"));
		wp_enqueue_script( 'badr-syndication-script');
		wp_register_style( 'badr-syndication-style', plugins_url( 'css/badr-syndication-metabox.css', __FILE__ ) );
		wp_enqueue_style( 'badr-syndication-style');
		wp_localize_script( 'badr-syndication-script', 'badrSyndication', array( 'sCategory' => $this->_sCategory, 'bIsNewPost' => $bIsNewPost ) );
	}

	function dispMetabox($oPost,$box) {
	    $bSyndi = get_post_meta($oPost->ID,'_syndication',true) ? '1' : '0';
	    echo'
	    <input type="radio" name="_syndication_do_off" id="_syndication_do_on" value="0" class="syndication" />
	    <label for="_syndication_do_on" class="syndication-icon syndication-on">연동함<span id="syndi_notice"></span></label>
	    <br />
	    <input type="radio" name="_syndication_do_off" id="_syndication_do_off" value="1" class="syndication" />
	    <label for="_syndication_do_off" class="syndication-icon syndication-off">연동안함</label>
	    <br />
     	<input type="hidden" name="_syndication_metabox_flag" value="1" />
     	<input type="hidden" name="_syndication_is_off" id="syndication_status" value="'.$bSyndi.'" />';
	}

	function dispManagementPage() {
		$bResult = false;
		if( isset( $_POST['submit'] ) ) {
			$new_input = array();
			$aExCategory = empty($_POST['except_category']) ? array() : $_POST['except_category'];
			//do_action( 'ns_update_category ', $aExCategory );
			$new_input['except_category'] = $_POST['except_category'];
			$new_input['key'] = $_POST['syndi_key'];
			$new_input['email'] = sanitize_email( $_POST['syndi_email'] );
			$new_input['name'] = sanitize_text_field( $_POST['syndi_name'] );
			$new_input['site_url'] = preg_replace( '/^(http|https):\/\//i', '', get_option( 'siteurl' ));
			$new_input['post_type'] = !empty($this->_aOptions['post_type']) ? $this->_aOptions['post_type'] : array('post');
			$bResult = $this->updateConfig( $new_input );
		}
		$yeti_visited_time = get_option('_syndication_yeti');
		if( isset($_GET['page']) && $_GET['page'] == $this->plugin_name )
			require_once(trailingslashit(dirname(__FILE__)) . 'tpl/config.php');
	}
	
	function loadAdminScript(){
		if ( !isset( $_GET['page'] ) || $_GET['page'] != $this->plugin_name ) return;
	 	wp_register_script('badrSyndicationAdminJs', plugins_url( 'js/badr-syndication-admin.js', __FILE__ ), array("jquery","jquery-ui-dialog"));
		wp_register_style( 'badrSyndicationStylesheet', plugins_url('css/style.css', __FILE__) );
		wp_enqueue_script('badrSyndicationAdminJs');
		wp_enqueue_style('badrSyndicationStylesheet');
		wp_enqueue_style('wp-jquery-ui-dialog');
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'dashboard' );
		//js에서 사용할 플러그인 디렉토리 변수를 naverSyndication.plugin_url에 담는다.
   		wp_localize_script( 'badrSyndicationAdminJs', 'badrSyndication', array( 'plugin_url' => plugin_dir_url( __FILE__ ), 'ajax_url' => admin_url('admin-ajax.php') ) );
	}
		
  	function ping($id) {
		$ping_url = get_option('siteurl').'/?syndication_feeds='.$id;
		$url = 'https://apis.naver.com/crawl/nsyndi/v2';
		$arr = array(
					'method' => 'POST',
					'headers' => array(
						"Host" => $this->_aOptions['site_url'],
						"User-Agent" => "request",
						"Content-Type" => "application/x-www-form-urlencoded",
						"Authorization" => "Bearer ".$this->_aOptions['key'],
						"Accept-Encoding" => "gzip,deflate,sdch",
						"Accept-Language" => "en-US,en;q=0.8,ko;q=0.6",
						"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"	),
					'body' => array('ping_url' => $ping_url)
				);
		$result = wp_remote_post( $url, $arr );
		ChromePhp::log($ping_url);
		return $result;
  	}
  	
  	function activatePlugin() {
  		
  	}
}