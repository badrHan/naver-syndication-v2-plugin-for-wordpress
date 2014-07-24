<?php
require_once(trailingslashit(dirname(__FILE__)) . 'badr-syndication-class.php');

class badrSyndicationAdmin extends badrSyndication{

	function init() {
		add_action( 'admin_init', array( &$this, 'initAdmin' ) );
		add_action( 'admin_menu', array( &$this, 'initAdminPage') );	
	}
	
	function initAdmin() {
		//add_action( 'ns_update_category', array( &$this, 'setExCategory'), 10, 1 ); //제외카테고리설정반영
		add_action( 'wp_ajax_adminConfigCheck', array( &$this, 'adminConfigCheck'));
		add_action( 'wp_ajax_sendPagePing', array( &$this, 'sendPagePing'));
		add_action( 'save_post', array( &$this, 'procSavePing'), 10, 2);
		add_action( 'trashed_post', array( &$this, 'procTrashPing'), 10, 1);
		add_action( 'untrashed_post', array( &$this, 'procTrashPing'), 10, 1);
	}
	
	function initAdminPage() {
		$suffix = add_options_page( '네이버 신디케이션', '네이버 신디케이션', 'manage_options', 'badr-syndication', array( &$this , 'dispManagementPage') );
	 	add_action( 'admin_print_scripts-' . $suffix, array( &$this , 'loadAdminScript') );
		add_meta_box( 'badr_syndication_metabox', '네이버 신디케이션', array( &$this, 'dispMetabox'),'post','side','default');
	 	add_action( 'admin_enqueue_scripts', array( &$this , 'loadMetaboxScript'));
	}

	
	/*
	 * FIXME 제외 카테고리 추가시 발행된 해당 포스트를 삭제 처리 루틴 작성
	 */
	function setExCategory( $aExCategory ){
			$aCheckForPing = array_diff( $aExCategory , $this->aCategory );
			//if( count($aCheckForPing) > 0 ) $this->procPingCategory();
	}

	function _checkValidation( $id ){
		if( empty($_GET['validate']) ) return true;
		if( !extension_loaded('DOM') ) die( 'PHP DOM 익스텐션이 필요합니다.' );
		$response = wp_remote_get( get_option('siteurl').'/?syndication_feeds='.$id);
		$xml= new DOMDocument();
		$xml->loadXML($response['body']);
		$xsd_file = trailingslashit(dirname(__FILE__)).'inc/syndi.xsd';
		if ( !$xml->schemaValidate($xsd_file) ) return false;
		return true;
	}	
	
	function adminConfigCheck(){
		if( empty($this->aOptions['key']) ) die('액세스 토큰을 입력해 주세요');
		$id = 'post-0.xml';
		if( !$this->_checkValidation( $id ) ) die(get_option('siteurl').'/?syndication_feeds='.$id.' - 문서의 정합성문제가 발생했습니다.'.$response['body']);
		$response = $this->_ping( $id );
		$oXml = @simplexml_load_string( $response['body'] );
		if( isset($oXml->message) ) {
			die($oXml->message);
		}else{
			echo"<p class='error'>Parsing error!!</p>";
			echo"<pre>";
			var_dump($response);
			echo"</pre>";
			exit;
		}
	}
	
	function sendPagePing(){
		if(empty($_GET['ping_url'])){
			$arg = array(
				'post_type' => $this->aOptions['post_type'],
				'post_status' => 'publish',
				'has_password' => false ,
				'posts_per_page' => 100,
				'category__in' => $this->aCategory
			);
			$query = new WP_Query( $arg );
			$output = array('pages' => $query->max_num_pages);
			die(json_encode($output));
		}else{
			$ping_url = $_GET['ping_url'];
			$result = $this->_ping($ping_url);
			$oXml = simplexml_load_string($result['body']);
			die(json_encode(array( 'ping_url' => get_option('siteurl').'/?syndication_feeds='.$ping_url, 'message' => (string) $oXml->message )));
		}
	}
	
	function procTrashPing( $post_id ){
		$this->_ping('post-'.$post_id.'.xml');
	}
	
	function procSavePing( $post_id, $oPost ){
  		if( !isset($_POST['_syndication_is_off']) || !isset($_POST['_syndication_do_off'])) return;
  		$is_off = (int) $_POST['_syndication_is_off'];
  		$do_off = (int) $_POST['_syndication_do_off'];
  		
  		if ( !$category_id = $this->_checkMetaData( $oPost ) ) return;
	  	if ( !$this->_savePostMeta( $post_id, $oPost ) ) return;
	  	if ( $is_off && $do_off ) return;
	  	
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

	function _savePostMeta( $post_id, $oPost ) {
		if ( wp_is_post_revision( $oPost ) ) return false;
		if ( empty($_POST['_syndication_metabox_flag']) ) return false;
		update_post_meta($post_id, '_syndication', $_POST['_syndication_do_off']);
		return true;
	}

	function updateConfig( $input ){
		$this->aOptions = $input;
		update_option( '_syndication' , $input );
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
     	<input type="hidden" name="_syndication_is_off" id="syndication_status" value="'.$bSyndi.'" />';
	}

	function dispManagementPage() {
		if( isset( $_POST['submit'] ) ) {
			$aExCategory = empty($_POST['except_category']) ? array() : $_POST['except_category'];
			//do_action( 'ns_update_category ', $aExCategory );
			$new_input['except_category'] = $_POST['except_category'];
			$new_input['key'] = $_POST['syndi_key'];
			$new_input['email'] = sanitize_email( $_POST['syndi_email'] );
			$new_input['name'] = sanitize_text_field( $_POST['syndi_name'] );
			$new_input['site_url'] = preg_replace( '/^(http|https):\/\//i', '', get_option( 'siteurl' ));;
			$new_input['post_type'] = !empty($this->aOptions['post_type']) ? $this->aOptions['post_type'] : array('post');
			$this->updateConfig( $new_input );
		}		
?>
<div class="wrap">
	<h2>네이버 신디케이션 V2</h2>
	<p>네이버 신디케이션 문서란, 웹 사이트의 콘텐츠를 네이버 웹 서비스에 전달할 수 있도록 정해진 형식에 맞춰 작성한 문서입니다.  네이버 신디케이션 문서는 XML 기반의 문서 포맷인 ATOM을 참고하여 네이버 검색 서비스에 연동할 수 있게 보완한 문서 형식을 사용합니다.</p>
	<form method="post">

	<table class="form-table">
		<tbody>
			<tr>
			<th scope="row">연동키</th>
				<td>
					<input type="text" name="syndi_key" class="large-text" value="<?php echo $this->aOptions['key'] ? $this->aOptions['key'] : ''?>" title="연동키" />
					<p><a href="http://webmastertool.naver.com/index.naver" target="blank">네이버 웹마스터 도구</a>에서 발급받은 연동키를 입력하세요.</p>
				</td>
			</tr>
			<tr>
			<th scope="row">관리자</th>
				<td>
					<input type="text" name="syndi_name" value="<?php echo $this->aOptions['name'] ? $this->aOptions['name'] : ''?>"  />
					<p>사이트 관리자나 회사명, 저작권자의 이름을 입력하세요.</p>
				</td>
			</tr>
			<tr>
			<th scope="row">관리자 이메일</th>
				<td>
					<input type="text" name="syndi_email" value="<?php echo $this->aOptions['email'] ? $this->aOptions['email'] : get_option('admin_email', false)?>" />
				</td>
			</tr>
			<tr>
			<th scope="row">제외할 카테고리</th>
				<td>
			    	<div style="border-color:#CEE1EF; border-style:solid; border-width:2px; height:10em; margin:5px 0px 5px 0px; overflow:auto; padding:0.5em 0.5em; background-color:#fff;">
			    	<ul>
			    	<?php wp_category_checklist( 0, 0, explode( ',', $this->aOptions['except_category']) );?>
			    	</ul>
			   		</div>
			   		<input type="hidden" value="<?php echo $this->aOptions['except_category']?>" name="except_category" id="except_category" />
			   	</td>
		   	</tr>
	   	</tbody>
   	</table>
   	<p class="submit">
		<input type="submit" name="submit" id="submit" class="button button-primary" value="저장">
		<a href="<?php echo admin_url('admin-ajax.php')?>?action=adminConfigCheck" class="button" target="configCheck" title="임의의 삭제 엔트리를 생성하여 핑을 보냅니다. 진행하시겠습니까?">동작확인</a>
		<a href="<?php echo admin_url('admin-ajax.php')?>?action=sendPagePing" class="button" target="sendPages" title="연동설정된 전체포스트를 페이지단위로 핑을 보냅니다. (100Posts/Page)">문서목록 발송</a>
	</p>
	</form>
</div>
<?php
	}
	
	function loadAdminScript(){
	 	wp_register_script('badrSyndicationAdminJs', plugins_url( 'js/badr-syndication-admin.js', __FILE__ ), array("jquery","jquery-ui-dialog"));
		wp_register_style( 'badrSyndicationStylesheet', plugins_url('css/style.css', __FILE__) );
		wp_enqueue_script("badrSyndicationAdminJs");
		wp_enqueue_style("wp-jquery-ui-dialog");
		wp_enqueue_style("badrSyndicationStylesheet");
		//js에서 사용할 플러그인 디렉토리 변수를 naverSyndication.plugin_url에 담는다.
   		wp_localize_script( 'badrSyndicationAdminJs', 'badrSyndication', array( 'plugin_url' => plugin_dir_url( __FILE__ ) ) );
	}
		
	function procDB($method, $arr){
		if( !class_exists('naverSyndicationDB') )
			require_once(trailingslashit(dirname(__FILE__)) . 'badr-syndication-db.php');

		$oDB = &badrSyndicationDB::getInstance();

		call_user_func( array($oDB, $method),  $arr);
	}
		
  function _ping($id) {
		$ping_url = get_option('siteurl').'/?syndication_feeds='.$id;
		$url = 'https://apis.naver.com/crawl/nsyndi/v2';
		$arr = array(
					'method' => 'POST',
					'headers' => array(
						"Host" => $this->aOptions['site_url'],
						"User-Agent" => "request",
						"Content-Type" => "application/x-www-form-urlencoded",
						"Authorization" => "Bearer ".$this->aOptions['key'],
						"Accept-Encoding" => "gzip,deflate,sdch",
						"Accept-Language" => "en-US,en;q=0.8,ko;q=0.6",
						"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"	),
					'body' => array('ping_url' => $ping_url)
				);
		$result = wp_remote_post( $url, $arr );
		return $result;
  }
}