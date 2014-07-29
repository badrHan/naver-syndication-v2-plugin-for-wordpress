<?php
require_once (trailingslashit ( dirname ( __FILE__ ) ) . 'badr-syndication-class.php');
class badrSyndicationFront extends badrSyndication {
	private $page = null; 
	private $post_id = null;
	private $indexed = null;
	private $message = null;
	private $maxentry = 100;
	
	function init() {
		$this->setVars ();
		add_filter ( 'template_redirect', array ( &$this, 'dispSyndicationList' ), 1, 0 );
	}
	function setVars() {
		if (empty ( $this->_aOptions['key'] ) || empty ( $this->_aOptions['name'] ) || empty ( $this->_aOptions['email'] ))
			$this->setErrorMessage ( 1 );
		
		preg_match ( '/^([p||l][aegiost]{3})-([0-9]+)?\.xml$/i', $_GET ['syndication_feeds'], $matches );
		
		if (empty ( $matches[0] ))
			$this->setErrorMessage ( 2 );
		
		if ($matches [1] == 'post') {
			$this->post_id = $matches [2];
		} elseif($matches [1] == 'page') {
			$this->page = $matches [2];
		} else {
			$this->indexed =  $matches [2];
		}
		$this->insertLog();
	}
	function insertLog(){
		if( empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'Yeti') !== false ) return;
		$this->procDB('insertLog', array('url' => $_SERVER['REQUEST_URI']));
		//update_option('_syndication_yeti', current_time('Y-m-d H:i:s') );
	}
	function setErrorMessage($nNum) {
		$this->message = $nNum;
		$this->dispTemplate ();
	}
	function dispTemplate() {
		if ($this->message ) {
			$this->message = $this->message;
			$this->tplFile = $this->setTemplateFile ( 'xml_error' );
		}
		header ( 'Content-Type: application/xml; charset=utf-8' );
		require ($this->tplFile);
		exit ();
	}
	function dispSyndicationList() {
		global $wp_query;
		$wp_query->is_404 = false;
		$wp_query->is_feed = false;
		$this->getSyndicationList ();
		// header('Content-Type: application/xml; charset=utf-8');
		$this->dispTemplate ();
	}
	function getSyndicationList() {
		$this->id = $this->getId ();
		$this->title = htmlspecialchars ( get_option ( 'blogname' ) );
		$this->author->name = $this->_aOptions['name'];
		$this->author->email = $this->_aOptions['email'];
		$this->updated = $this->getLastUpdatedTime ();
		$this->link->href = get_option ( 'siteurl' );
		$this->link->title = $this->title;
		$this->tplFile = $this->setTemplateFile ( 'feeds' );
		$this->getEntries ();
	}
	function getEntries() {
		if (! is_null ( $this->indexed )) return $this->getIndexedList();
		if (! is_null ( $this->post_id )) {
			//if ($this->_id == '0')	return $this->getDeleted (); // using temporary post(0.xml) for admin config check
			$arg = 'p=' . $this->post_id;
		} else {
			$arg = array (
					'post_type' => $this->_aOptions['post_type'],
					'post_status' => 'publish',
					'has_password' => false,
					'posts_per_page' => $this->maxentry,
					'paged' => $this->page,
					'category__in' => $this->_aCategory,
					'orderby' => 'ID',
					'order' => 'DESC' 
			);
		}
		
		$query = new WP_Query ( $arg );
		if ($query->have_posts ()) {
			$total_page = $query->max_num_pages;
			$i = 0;
			foreach ( $query->posts as $oPost ) {
				$this->entries[$i]->syndication = ! get_post_meta ( $oPost->ID, '_syndication', true );
				$oCategory = $this->getUniqCategory ( $oPost->ID );
				$this->entries[$i]->id = htmlspecialchars( $oPost->guid.'&syndi=on' );
				$this->entries[$i]->title = htmlspecialchars ( get_the_title($oPost->ID) );
				// $this->entries[$i]->summary = get_the_excerpt();
				$this->entries[$i]->content = htmlspecialchars ( $oPost->post_content );
				$this->entries[$i]->updated = $this->setDate ( $oPost->post_modified_gmt );
				$this->entries[$i]->regdate = $this->setDate ( $oPost->post_date_gmt );
				$this->entries[$i]->via_href = get_option ( 'siteurl' ) . '/?cat=' . $oCategory->cat_ID;
				$this->entries[$i]->via_title = htmlspecialchars ( urldecode ( $oCategory->name ) );
				$this->entries[$i]->mobile_href = $this->entries[$i]->id;
				$this->entries[$i]->nick_name = htmlspecialchars ( get_the_author_meta ( 'display_name', $oPost->post_author ) );
				$this->entries[$i]->category_term = $oCategory->cat_ID;
				$this->entries[$i]->category_label = htmlspecialchars ( $this->entries[$i]->via_title );
				$i++;
			}
		} else {
			return $this->getDeleted ();
		}
		wp_reset_postdata ();
	}
	function getDeleted() {
		$this->entries[0]->syndication = false;
		$this->entries[0]->id = get_option ( 'siteurl' ) . '/?p=' . $this->_id;
		$this->entries[0]->regdate = $this->setDate ( date ( 'Y-m-d H:i:s' ) );
	}
	function getIndexedList() {
		$aResult = $this->_procDB('getIndexedLog',array('start' => $this->indexed));
		$i = 0;
		foreach( $aResult as $list){
			$this->entries[$i]->syndication = false;
			$this->entries[$i]->id = 'http://'.htmlentities($list['link']);
			$this->entries[$i]->regdate = $this->setDate ( date ( 'Y-m-d H:i:s' ) );
			$i++;
		}
	}
	function getID() {
		return sprintf ( '%s/?%s', get_option ( 'siteurl' ), $_SERVER['QUERY_STRING'] );
	}
	function getUniqCategory($post_id) {
		$aCategory = get_the_category ( $post_id );
		foreach ( $aCategory as $oCategory ) {
			if (in_array ( $oCategory->cat_ID, $this->_aCategory ))
				return $oCategory;
		}
	}
	function getLastUpdatedTime($category_id = null) {
		global $wpdb;
		if (is_null ( $category_id ))
			$category_id = $this->_sCategory;
		$query_string = "
			SELECT P.post_modified_gmt FROM " . $wpdb->posts . "
			AS P, " . $wpdb->term_relationships . " AS R WHERE P.ID = R.object_id
			AND R.term_taxonomy_id in (" . $category_id . ") and P.post_status='publish'
			AND P.post_type = 'post' AND P.post_password = '' ORDER BY P.post_date DESC LIMIT 1";
		return $this->setDate ( $wpdb->get_var ( $query_string ) );
	}
	
	/*
	 * 0.7.3 getLastUpdatedTime()에서 get_var()이 null을 리턴하는 경우
	 */
	function setDate($time = null) {
		if (is_null ( $time ))
			$time = date ( 'Y-m-d H:i:s' );
		return mysql2date ( 'Y-m-d\TH:i:s\Z', $time, false );
	}
	function setTemplateFile($file_name) {
		return plugin_dir_path ( __FILE__ ) . 'tpl/' . $file_name . '.php';
	}
}