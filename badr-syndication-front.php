<?php
require_once(trailingslashit(dirname(__FILE__)) . 'badr-syndication-class.php');

class badrSyndicationFront extends badrSyndication{

	var $_page = null; //if end value is empty, use for post ID
	var $_id = null;
	var $_message = null;
	private $_maxentry = 100;	

	function init(){
		$this->setVars();
		add_filter('template_redirect', array( &$this , 'dispSyndicationList'), 1, 0);
	}
	
	function setVars() {
		if( empty($this->aOptions['key']) ) $this->setErrorMessage( 1 );
		
		preg_match('/^(p[ageost]{3})-([0-9]+)?\.xml$/i', $_GET['syndication_feeds'], $matches);
		
		if( empty($matches) ) $this->setErrorMessage( 2 );
		
		if($matches[1] == 'post'){ //post
			$this->_id = $matches[2];
		}else{ //list
			$this->_page = $matches[2];
		}
	}
	
	function setErrorMessage($nNum) {
		$this->_message = $nNum;
		$this->_dispTemplate();
	}
	
	function _dispTemplate($result = null) {
		if(!$result) {
			$result->message = $this->_message;
			$result->tplFile = $this->setTemplateFile('xml_error');
		}
		header('Content-Type: application/xml; charset=utf-8');
		require( $result->tplFile );
		exit;
	}
	
	function dispSyndicationList() {
		$wp_query->is_404 = false;
		$wp_query->is_feed = false;
		$result = $this->getSyndicationList();
		//header('Content-Type: application/xml; charset=utf-8');
		$this->_dispTemplate($result);
	}
	
	function getSyndicationList() {
		$result->id = $this->getId();
		$result->title = htmlspecialchars(get_option('blogname'));
		$result->author->name = $this->aOptions['name'];
		$result->author->email = $this->aOptions['email'];
		$result->updated = $this->getLastUpdatedTime();
		$result->link->href = get_option( 'siteurl' );
		$result->link->title = $result->title;
		$result->tplFile = $this->setTemplateFile('feeds');
		$result->entries = $this->getArticles();
		return $result;
  }

  function getArticles() {
  	
	if( !is_null($this->_id) ){
		if($this->_id == '0') return $this->getDeleted(); //using temporary post(0.xml) for admin config check
		$arg = 'p='.$this->_id;
	}else{
		$arg = array(
			'post_type' => $this->aOptions['post_type'],
			'post_status' => 'publish',
			'has_password' => false ,
			'posts_per_page' => $this->_maxentry,
			'paged' => $this->_page,
			'category__in' => $this->aCategory,
			'orderby' => 'ID',
			'order' => 'DESC'
		);
	}

	$query = new WP_Query( $arg );
	if($query->have_posts()) {
		$total_page = $query->max_num_pages;
		$result->entry = array();
	    foreach($query->posts as $oPost) {
	    	$val->syndication = !get_post_meta( $oPost->ID, '_syndication', true );
			$oCategory = $this->getUniqCategory($oPost->ID);
			$val->id = get_option( 'siteurl').'/?p='.$id.'&syni=1';
			$val->title = htmlspecialchars($oPost->post_title);
			//$val->summary = get_the_excerpt();
			$val->content = htmlspecialchars($oPost->post_content);
	    	$val->updated = $this->setDate($oPost->post_modified_gmt);
	      	$val->regdate = $this->setDate($oPost->post_date_gmt);
	      	$val->via_href = get_option( 'siteurl').'/?cat='.$oCategory->cat_ID;
	      	$val->via_title = htmlspecialchars(urldecode($oCategory->name));
	      	$val->mobile_href = $val->id;
	      	$val->nick_name = htmlspecialchars(get_the_author_meta( 'display_name', $oPost->post_author));
	      	$val->category_term = $oCategory->cat_ID;
	      	$val->category_label = htmlspecialchars($val->via_title);
	      	$result->entry[] = $val;
	      	unset($val);
	    }
	} else {
		return $this->getDeleted();
	}
	wp_reset_postdata();
	return $result;
  }

  function getDeleted(){
  	$result->entry = array();
  	$result->entry[0]->syndication = false;
  	$result->entry[0]->id = get_option( 'siteurl' ).'/?p='.$this->_id;
  	$result->entry[0]->regdate = $this->setDate(date('Y-m-d H:i:s'));
  	return $result;
  }
  
  function getID() {
      return sprintf('%s/?syndication_feeds=%s', get_option( 'siteurl' ), $this->_page ? 'page-'.$this->_page.'.xml' : 'post-'.$this->_id.'.xml');
  }
 
	function getUniqCategory( $post_id ) {
		$aCategory = get_the_category($post_id);
		foreach($aCategory as $oCategory){
				if( in_array($oCategory->cat_ID, $this->aCategory) ) return $oCategory;
		}
	}
	
	function getLastUpdatedTime($category_id = null){
		global $wpdb;
		if( is_null( $category_id ) ) $category_id = $this->sCategory;
		$query_string = "
			SELECT P.post_modified_gmt FROM " . $wpdb->posts . "
			AS P, ".$wpdb->term_relationships." AS R WHERE P.ID = R.object_id
			AND R.term_taxonomy_id in (".$category_id.") and P.post_status='publish'
			AND P.post_type = 'post' AND P.post_password = '' ORDER BY P.post_date DESC LIMIT 1";
		return $this->setDate($wpdb->get_var($query_string));
	}
	
	function setDate($time){
		return mysql2date('Y-m-d\TH:i:s\Z', $time, false);
	}	
	
	
	function setTemplateFile($file_name){
		return plugin_dir_path( __FILE__ ).'tpl/'.$file_name.'.php';
	}

}