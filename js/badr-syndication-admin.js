/**
* plugin_url()로 값을 스크립트에 사용하기위해서
* naver-syndication-admin.php, wp_localize_script 함수로 전역에서 선언해야 한다.
* 플러그인 url이 필요없다면 변수 선언해주어야 한다.
* var badrSyndication = badrSyndication || {};
*/

badrSyndication.admin = {
	sButton: 'table.form-table a[href*=admin-ajax]',
	elLoadingImg: '<img src="' + badrSyndication.plugin_url + 'img/loadingAnimation.gif" width="208" />',
		
  init: function() {
  	//"동착확인", "상태확인" 버튼에 이벤트 할당	
		jQuery(this.sButton).each( jQuery.proxy(this.initEvent, this) ); // scope 때문에 proxy함수 사용. argument[index, element]는 전달된다.

  },
  
  initEvent: function() {
  	//arguments[0] = index, arguments[1] = element
  	jQuery(arguments[1]).on( 'click', jQuery.proxy(this.beforeAjax, this) );
  },
  
  beforeAjax: function( event ) {
		event = event || window.event;
		event.preventDefault();
  	var target = event.target.href || event.srcElement.href;
  	var wlLayer = this.getWlLayer(target);
  	if( wlLayer.length > 0 ) this.getWlLayer(target).html( this.elLoadingImg );
  	this.procAjax( target, wlLayer );
  },
  
  setAjaxContent: function( wlTarget, result ) {
  	if( wlTarget.length > 0 ) wlTarget.html( result );
  },
  
  jsonParser: function( str ) {
  	var res = jQuery.parseJSON( str );
  	var result = res.title + '<br />' + res.body
  	return result;
  },

  procAjax: function( _url, wlTarget ) {
		jQuery.ajax({
			url : _url,
			type : "GET",
			success : jQuery.proxy(this.setAjaxContent, this, wlTarget)
		});
  },
 
  getWlLayer: function( _url ) {
  	return jQuery('#' + _url.split('?')[1].split('=')[1] );
  }
}


badrSyndication.bulkping = {
	wlButton: {},
	wlResult: {},
	sUrl: '',
	cur: 1,
	
		
  init: function() {
  	//arguments[0] = index, arguments[1] = element
  	this.wlResult = jQuery('#adminPingCheck');
  	this.wlButton = jQuery("#bulk_ping_button").on( 'click', jQuery.proxy(this.beforeAjax, this) );
  },
  
  beforeAjax: function( event ) {
		event = event || window.event;
		event.preventDefault();
  	this.sUrl = event.target.href || event.srcElement.href;
  	this.getPages();
  },
  
  sendPagePing: function( result ) {
		var totalpages = parseInt(JSON.parse(result).pages);
		if ( totalpages < 1 ) {
			this.wlResult.text('no pages');
			this.wlButton.prop("disabled", false);
			return;
		}
		this.sendPostPing( totalpages );
  },
  
  sendPostPing: function( total ) {
  	console.log(this.cur, total);
		if (this.cur > total) {
			this.wlButton.prop("disabled", false);
			this.wlResult.text('done');
			return;
		}
		
		var self = this, ping_url = this.sUrl + '&ping_url=page-' + this.cur + '.xml';
		this.wlResult.text('sending ping for ' + ping_url);
		jQuery.getJSON( ping_url, function(result) {
				self.wlResult.text(result.ping_url + ' : ' + result.message);
				if(result.message == 'OK') {
					self.cur = self.cur + 1;
					self.sendPostPing(total);
				} else {
					this.wlResult.text(result.message);
				}
		});
  },
  
  jsonParser: function( str ) {
  	var res = jQuery.parseJSON( str );
  	var result = res.title + '<br />' + res.body
  	return result;
  },

  getPages: function() {
		jQuery.ajax({
			url : this.sUrl,
			type : "GET",
			success : jQuery.proxy(this.sendPagePing, this)
		});
  },
 
  getWlLayer: function( _url ) {
  	return jQuery('#' + _url.split('?')[1].split('=')[1] );
  }
}



jQuery( document ).ready(function( $ ){
	badrSyndication.admin.init();
	$('table.form-table a.refresh-link').trigger('click');
	
	badrSyndication.bulkping.init();
	
  $('input[name="post_category[]"]').change(function(){
  	var checked_categories = $('input[name="post_category[]"]:checked').map(function(){ return this.value}).get();
  	$('#except_category').val(checked_categories.join(','));
 	});

})