<?php
require_once(trailingslashit(dirname(__FILE__)) . 'badr-syndication-class.php');
/**
 *XML출력을 담당하는 클래스
 */
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
		
		preg_match('/^([p||t][ageost]{3})-([0-9]+)?\.xml$/i', $_GET['syndication_feeds'], $matches);
		
		if( empty($matches) ) $this->setErrorMessage( 2 );
		
		if($matches[1] == 'post'){ //post
			$this->_id = $matches[2];
		}elseif($matches[1] == 'page'){ //list
			$this->_page = $matches[2];
		}else{ //test
			$result->id = $matches[0];
			$result->date = $this->setRFC3339(date('Y-m-d H:i:s'));
			$result->tplFile = $this->setTemplateFile('xml_test');
			$this->_dispTemplate( $result );
		}
	}
	
	function setErrorMessage($nNum) {
		$this->_message = $nNum;
		$this->_dispTemplate();
	}
	
	public function _dispTemplate($result = null) {
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
		$result->link->href = site_url();
		$result->link->title = $result->title;
		$result->tplFile = $this->setTemplateFile('feeds');
		
		$result->articles = $this->getArticles();

		return $result;
  }

	function getProtocol() {
		return stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
	}
	
  private function getArticles() {
		if($this->_id) $arg = 'p='.$this->_id;
		else
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
		$query = new WP_Query( $arg );
		$total_page = $query->max_num_pages;
		$result->list = array();
		//$result->next_url = $this->getPageNav($total_page,$id,$type); 
		if($query->have_posts()) {
		    foreach($query->posts as $oPost) {
		    	if ( $oPost->status != 'publish' ) {
		    		$val->ref = get_option( 'siteurl' ) .'?p='.$oPost->guid;
		    		$val->when = $this->setRFC3339($oPost->post_modified_gmt);
		    		return $val;
		    	}
				$oCategory = $this->getUniqCategory($oPost->ID);
				$val->id = $oPost->guid;
				$val->title = htmlspecialchars($oPost->post_title);
				//$val->summary = get_the_excerpt();
				$val->content = $oPost->post_content;
		    	$val->updated = $this->setRFC3339($oPost->post_modified_gmt);
		      	$val->regdate = $this->setRFC3339($oPost->post_date_gmt);
		      	$val->via_href = get_option( 'siteurl').'/?cat='.$oCategory->cat_ID;
		      	$val->via_title = htmlspecialchars(urldecode($oCategory->name));
		      	$val->mobile_href = $oPost->guid;
		      	$val->nick_name = htmlspecialchars(get_the_author_meta( 'display_name', $oPost->post_author));
		      	$val->category_term = $oCategory->cat_ID;
		      	$val->category_label = $val->via_title;
		      	$result->list[] = $val;
		      	unset($val);
		      	wp_reset_postdata();
		    }
		}
		return $result;
  }
	             
  function getID() {
      return sprintf('%s/?syndication_feeds=%s', $this->aOptions['site_url'], $this->_page ? 'page-'.$this->_page.'.xml' : 'post-'.$this->_id.'.xml');
  }

	function getUniqCategory( $post_id ) {
		$aCategory = get_the_category($post_id);
		foreach($aCategory as $oCategory){
				if( in_array($oCategory->cat_ID, $this->aCategory) ) return $oCategory;
		}
	}
	
	private function getLastUpdatedTime($category_id = null){
		global $wpdb;
		if( is_null( $category_id ) ) $category_id = $this->sCategory;
		$query_string = "
			SELECT P.post_modified_gmt FROM " . $wpdb->posts . "
			AS P, ".$wpdb->term_relationships." AS R WHERE P.ID = R.object_id
			AND R.term_taxonomy_id in (".$category_id.") and P.post_status='publish'
			AND P.post_type = 'post' AND P.post_password = '' ORDER BY P.post_date DESC LIMIT 1";
		return $this->setRFC3339($wpdb->get_var($query_string));
	}
	
	function getStatusResult($response) {
		if($response['response']['code'] != 200) {
			$result->message = "no response";
		}
		
		$oStatus = new SimpleXMLElement($response['body']);

		if($oStatus->error != 0){
			$result->message = $oStatus->message;
		}

		$result->site_name = $oStatus->site_name;
		$result->first_update = $oStatus->first_update;
		$result->last_update = $oStatus->last_update;
		$result->visit_ok_count = $oStatus->visit_ok_count;
		$result->visit_fail_count = $oStatus->visit_fail_count;
		$result->status = $oStatus->status;
		
		if(is_object($oStatus->sync->article)){
			$article_count = array();
			foreach($oStatus->sync->article as $article){
				$article_count[(string)$article['date']] = (string) $article;
			}
			$result->article_count = $article_count;
			$result->max_article_count = max($result->article_count);
		}

		$result->tplFile = $this->setTemplateFile('status_result');
		return $result;
	}
		
	function setRFC3339($time){
		return mysql2date('Y-m-d\TH:i:s\Z', $time, false);
	}	
	
	
	function setTemplateFile($file_name){
		return plugin_dir_path( __FILE__ ).'tpl/'.$file_name.'.php';
	}

}