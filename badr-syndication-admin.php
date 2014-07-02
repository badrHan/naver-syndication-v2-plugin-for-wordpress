<?php
require_once(trailingslashit(dirname(__FILE__)) . 'badr-syndication-class.php');

class badrSyndicationAdmin extends badrSyndication{
	
	var $mode = 'new';
	var $ping_url;
	
	function init() {
		$this->ping_url = site_url()."/?syndication_feeds=";
		add_action( 'admin_init', array( &$this, 'initAdmin' ) );
		add_action( 'admin_menu', array( &$this, 'initAdminPage') );	
	}
	
	function initAdmin() {
		//ns_log(__METHOD__,1,0);
		add_action( 'transition_post_status', array( &$this , 'setPostTransition'), 10, 3 );
		add_action( 'ns_update_category', array( &$this, 'setExCategory'), 10, 2 ); //sanitize 제외카테고리설정반영
		add_action( 'wp_ajax_adminPingCheck', array( &$this, 'adminPingCheck'));
		add_action( 'wp_ajax_sendBulkPing', array( &$this, 'sendBulkPing'));
		add_action( 'save_post', array( &$this, 'sendPing'), 10, 2);
	}
	
	function initAdminPage() {
		//ns_log(__METHOD__,1,0);
		$suffix = add_options_page( '네이버 신디케이션', '네이버 신디케이션', 'manage_options', 'badr-syndication', array( &$this , 'dispManagementPage') );
	 	add_action( 'admin_print_scripts-' . $suffix, array( &$this , 'loadAdminScript') );
		add_meta_box( 'badr_syndication_metabox', '네이버 신디케이션', array( &$this, 'dispMetabox'),'post','side','default');
	 	add_action( 'admin_enqueue_scripts', array( &$this , 'loadMetaboxScript'));
		register_setting( 'badrSyndication', '_syndication', array( &$this, 'formSanitize' ) );
		add_settings_section( 'config-1', '', array( &$this, 'disp_section_info' ), 'syndication' );  
		add_settings_field( 'ping_check', '문서 생성  확인<a href="'.admin_url('admin-ajax.php').'?action=adminPingCheck" class="refresh-link" title="refresh"> </a>', array( &$this, 'disp_ping_check'), 'syndication', 'config-1' );
		add_settings_field( 'auth_key', '연동키', array( &$this, 'disp_key_input'), 'syndication', 'config-1' );
		add_settings_field( 'manager_name', '관리자', array( &$this, 'disp_manager_name'), 'syndication', 'config-1' );  
		add_settings_field( 'manager_email', '관리자 이메일', array( &$this, 'disp_manager_email'), 'syndication', 'config-1' );  
		add_settings_field( 'except_category', '제외할 카테고리', array( &$this, 'disp_category' ), 'syndication', 'config-1' );  
	}

	
	/**
	 * 문서 상태변경시 키워드를 멤버변수로 저장후 save_post 액션에서 사용
	 * 2014 6/16 연동설정을 반영하기 위해서 수정됨.
	 * 신규 포스트 등록시 front end 에서 _
	 * $_POST['_syndication_do_off']
	 * $_POST['_syndication_is_off']
	*/
	function setPostTransition( $new_status, $old_status, $oPost ){
		if ($old_status == 'publish') { 
			switch($new_status):
				case 'publish' : //발행문서 수정
					$this->mode = 'modify';
					break;
				default : //삭제
					$this->mode = 'deleted';
					break;
			endswitch;
		} else {
					$this->mode = 'new';
		}
	}

	/**
	 * FIXME 제외 카테고리 추가시 발행된 해당 포스트를 삭제 처리 루틴 작성
	 */
	function setExCategory( $aNew, $aOld ){
			$aCheckForPing = array_diff($aNew, $aOld );
			//if( count($aCheckForPing) > 0 ) $this->procPingCategory();
	}

	function _checkConfigForAjax(){
		if( empty($this->aOptions['key']) ) die('액세스 토큰을 입력해 주세요');
	}

	function _checkValidation( $xml_file ){
		$xml= new DOMDocument();
		$xml->load($xml_file); 
		$xsd_file = trailingslashit(dirname(__FILE__)).'inc/syndi.xsd';
		if ( is_file($xsd_file) && !$xml->schemaValidate($xsd_file) ) return false;
		return true;
	}	
	
	function adminPingCheck(){
		$this->_checkConfigForAjax();
		$last = wp_get_recent_posts( array('numberposts' => 1, 'post_type' => $this->aOptions['post_type']), ARRAY_A);
		$last_id = $last['0']['ID'];
		$ping_url = $this->ping_url."post-".$last_id.".xml";
		$response = wp_remote_get( $ping_url );
		$oXml = new SimpleXMLElement($response['body']);
		if($oXml || is_object($oXml) || $oXml->id || $oXml->title) {
			if( !$this->_checkValidation($ping_url) ) die($ping_url.' - 문서의 정합성문제가 발생했습니다.');
			die('<a href="'.$ping_url.'" target="blank">'.$ping_url.'</a>');
		}else{
			if($oXml->message) {
				die($oXml->message);
			}else{
				die("unknown error!!! ");
			}
		}
	}
	
	
	function sendBulkPing(){
		if(empty($_GET['ping_url'])){
			$arg = array(
				'post_type' => $this->aOptions['post_type'],
				'post_status' => 'publish',
				'has_password' => false ,
				'posts_per_page' => 100,
				'category__in' => $this->sCategory
			);
			$query = new WP_Query( $arg );
			$output = array('pages' => $query->max_num_pages);
			die(json_encode($output));
		}else{
			$ping_url = $_GET['ping_url'];
			$result = $this->_ping($ping_url);
			$oXml = simplexml_load_string($result['body']);
			die(json_encode(array( 'ping_url' => $this->ping_url.$ping_url, 'message' => (string) $oXml->message )));
		}
	}
	
	
	function sendPing( $post_id, $oPost ){
  	ChromePhp::log($_POST);
  	if(empty($_POST['_syndication_is_off']) || empty($_POST['_syndication_do_off'])) return;
  	$is_off = (int) $_POST['_syndication_is_off'];
  	$do_off = (int) $_POST['_syndication_do_off'];
  	
  	
  	if ( !$category_id = $this->_checkMetaData( $oPost ) ) return;

  	if ( !$this->_savePostMeta( $post_id, $oPost ) ) return;;
  	
 
		if ($this->mode == 'modify') { //수정시
			
			if ($is_off){ // 연동안되어 있는  포스트를
				if ($do_off) { //그대로 연동 안할때
					return;
				} else { // 연동안되어 있는 포스트를  -> 연동할때
					$this->procDB('_deleteLog', array( $oPost, $category_id ) );
				}
			} else { //연동되어있는 포스트를
				if ($do_off) { //연동 안할대
					$this->procDB('_insertLog', array( $oPost, $category_id ) );
				}
			}
		}
		
		if($this->mode == 'new') { //새포스트 
			$this->procDB('_deleteLog', array( $oPost, $category_id ) ); //delete if exist
			if ($do_off) return;
		}
  		
  	if($this->mode == 'delete'){
			$this->procDB('_insertLog', array( $oPost, $category_id ) );
  	}
  	
		$this->_ping('post-'.$oPost->ID.'.xml');
  }

	function _checkMetaData( $oPost ){
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return false;
		if ( !in_array( $oPost->post_type, $this->aOptions['post_type']) ) return false;

		$oCategory = get_the_category($oPost->ID);
		if ( !in_array( $oCategory[0]->cat_ID, $this->aCategory ) ) {
			$this->_savePostMeta( $oPost->ID, $oPost );
			return false;			
		}
		return $oCategory[0]->cat_ID;
	}

  /**
  *FIXME add post_type selection
  *
  */
	function _savePostMeta( $post_id, $oPost ) {
		if ( wp_is_post_revision( $oPost ) ) return false;
		if ( empty($_POST['_syndication_metabox_flag']) ) return false;
		update_post_meta($post_id, '_syndication', $_POST['_syndication_do_off']);
		return true;
	}
		
	function loadMetaboxScript( $hook ){
    if ( !in_array($hook, array('post.php','post-new.php')) ) return;
    $bIsNewPost = isset($_GET['post']) ? 0 : 1;
	 	wp_register_script( 'badr-syndication-script', plugins_url( 'js/badr-syndication-metabox.js', __FILE__ ), array("jquery"));
		wp_enqueue_script( 'badr-syndication-script');
		wp_register_style( 'badr-syndication-style', plugins_url( 'css/badr-syndication-metabox.css', __FILE__ ) );
		wp_enqueue_style( 'badr-syndication-style');
		wp_localize_script( 'badr-syndication-script', 'badrSyndication', array( 'sCategory' => $this->sCategory, 'bIsNewPost' => $bIsNewPost ) );
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
     	<input type="text" name="_syndication_is_off" id="syndication_status" value="'.$bSyndi.'" />';
	}

	function dispManagementPage() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>네이버 신디케이션 설정</h2>
	      <form method="post" action="options.php">
				<?php
				settings_fields( 'badrSyndication' );   
				do_settings_sections( 'syndication' );
				?>
				<p class="submit">
				<?php submit_button('저장','primary','submit',false)?>
				<?php submit_button('초기화','secondary','option_reset',false)?>
				</p>
      </form>
		</div>
		<?php
	}
	
	function loadAdminScript(){
	 	wp_register_script('badrSyndicationAdminJs', plugins_url( 'js/badr-syndication-admin.js', __FILE__ ), array("jquery"));
		wp_register_style( 'badrSyndicationStylesheet', plugins_url('css/style.css', __FILE__) );
		wp_enqueue_script("badrSyndicationAdminJs");
		wp_enqueue_style("badrSyndicationStylesheet");
		//js에서 사용할 플러그인 디렉토리 변수를 naverSyndication.plugin_url에 담는다.
    wp_localize_script( 'badrSyndicationAdminJs', 'badrSyndication', array( 'plugin_url' => plugin_dir_url( __FILE__ ) ) );
	}
		
  function disp_section_info(){
		print '신디케이션이란 검색 서비스 업체와 syndication 이라는 표준 규약을 통해서 보다 더 잘 검색되게 하는 기능입니다. 최소한의 요청만으로 효과적으로 컨텐츠를 검색 서비스 업체와 동기화합니다';
	}

  function disp_ping_check(){?>
		<div id="adminPingCheck">creating....</div>
		<a href="<?php echo admin_url('admin-ajax.php')?>?action=sendBulkPing" class="button" id="bulk_ping_button">핑발송</a>
	<?php
	}

  function disp_key_input(){?>
		<input type="text" name="_syndication[key]" class="regular-text" value="<?php echo $this->aOptions['key'] ? $this->aOptions['key'] : ''?>">
		<?php
	}

  function disp_manager_name(){?>
		<input type="text" name="_syndication[name]" class="regular-text" value="<?php echo $this->aOptions['name'] ? $this->aOptions['name'] : ''?>">
		<?php
	}

  function disp_manager_email(){?>
		<input type="text" name="_syndication[email]" class="regular-text" value="<?php echo $this->aOptions['email'] ? $this->aOptions['email'] : get_option('admin_email', false)?>">
		<?php
	}
	
  function disp_category(){?>
    	<div style="border-color:#CEE1EF; border-style:solid; border-width:2px; height:10em; margin:5px 0px 5px 0px; overflow:auto; padding:0.5em 0.5em; background-color:#fff;">
    	<ul>
    	<?php wp_category_checklist( 0, 0, explode( ',', $this->aOptions['except_category']) );?>
    	</ul>
   		</div>
   		<input type="hidden" value="<?php echo $this->aOptions['except_category']?>" name="_syndication[except_category]" id="except_category" />
   	<?php
  }
  
	function formSanitize( $input ){	
		if(isset($_POST['option_reset'])){
			delete_option( '_syndication' );
			unset($input);
			$this->aOptions['post_type'] = '';
		} 
		do_action('ns_update_category', explode(',',$input['except_category']), explode(',',$this->aOptions['except_category']));
		$new_input['except_category'] = $input['except_category'];
		$new_input['key'] = $input['key'];
		$new_input['email'] = sanitize_email( $input['email'] );
		$new_input['name'] = sanitize_text_field( $input['name'] );
		$new_input['site_url'] = preg_replace( '/^(http|https):\/\//i', '', site_url());;
		$new_input['post_type'] = !empty($this->aOptions['post_type']) ? $this->aOptions['post_type'] : array('post');
		return $new_input;
	}

	function procDB($method, $arr){
		if( !class_exists('naverSyndicationDB') )
			require_once(trailingslashit(dirname(__FILE__)) . 'badr-syndication-db.php');

		$oDB = &badrSyndicationDB::getInstance();

		call_user_func( array($oDB, $method),  $arr);
	}
		
  function _ping($id) {
	
		$ping_url = $this->ping_url.$id;
		$url = 'http://www.autodiary.kr/?act=test';
		$url = 'https://apis.naver.com/crawl/nsyndi/v2';

		$result = wp_remote_post( $url, 
		array('method' => 'POST',
					'headers' => array(
						"Host" => $this->aOptions['site_url'],
						"User-Agent" => "request",
						"Content-Type" => "application/x-www-form-urlencoded",
						"Authorization" => "Bearer ".$this->aOptions['key'],
						"Accept-Encoding" => "gzip,deflate,sdch",
						"Accept-Language" => "en-US,en;q=0.8,ko;q=0.6",
						"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"
						),
					'body' => array('ping_url' => $ping_url) )
		);
		return $result;
  }
}