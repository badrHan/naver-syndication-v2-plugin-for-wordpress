//var badrSyndication = badrSyndication || {};// set in naver-syndication-admin.php, wp_localize_script

badrSyndication.dialog = {
	
	_htDialogOpt: {
		'width' : 500,
		'resizable' : true,              
		'dialogClass'   : 'wp-dialog',
		'modal'         : true,
		'autoOpen'      : false, 
		'closeOnEscape' : true
	},
	_wlDialog: jQuery('<div id="badrSyndication_result" />'),
	_elLoadingImg: '<p align="center"><img src="' + badrSyndication.plugin_url + 'img/loadingAnimation.gif" width="208" /></p>',
	_wlValidator: jQuery('<input type="checkbox" name="doValidater" id="doValidator" /><label for="doValidator">xml문서 정합성 검사</label>'),
	_currentPage: 1,
		
  init: function( elButton ) {
	this._wlButton = jQuery( elButton ).on( 'click', jQuery.proxy(this.setEvent, this) );
  },
  
  loadInfo: function( el ) {
	var content = el.title ? el.title : this._elLoadingImg;
   	this._wlDialog.html( content );
  },
  
  setAjaxContent: function( result ) {
  	this._wlDialog.html( result );
  },
  
  addAjaxContent: function( result ) {
	  	this._wlDialog.append( result );
  },

  setEvent: function( e ) {
	e.preventDefault();
	if( !this.checkInput() ) return;
	var el = event.target || event.srcElement;
	this._wlDialog.appendTo("body").dialog( this._htDialogOpt )
	.on( 'dialogopen' , jQuery.proxy(this.loadInfo, this, el ) )
	.on( 'dialogclose' , function() { jQuery(this).remove(); } );
	var procAjax = true;
  	if( typeof( this[el.target] ) == 'function' ) procAjax = this[el.target]( el ); // a엘리먼트 target속성을 함수명으로 지정
  	if( procAjax ) this.doAjax( el.href );
  },
  
   //a target 속성
  configCheck: function( el ) {
	  var self = this,
	  opt = {
			  title : el.innerText || el.textContent,
			  buttons : {	
				  '아니오' : function(){ self._wlDialog.dialog('close');  },
				  '예' : function() { self.doAjax( el.href ); }
				}
	  		};
	  this.dialogOpen( opt );
	  return false;
  },

  //a target 속성
  sendPages: function( el ) { 
	  var self = this,
	  opt = {
			  title : el.innerText || el.textContent,
			  buttons : {	
				  '아니오' : function(){ self._wlDialog.dialog('close');  },
				  '예' : function() { self.getPages( el.href ); }
				}
	  		};
	  this.dialogOpen( opt );
	  return false;
  },
 
  getPages: function( url ) {
		jQuery.ajax({
			url : url,
			type : "GET",
			success : jQuery.proxy(this.sendPagePing, this, url)
		});
  },

  sendPagePing: function( url, result ) {
		var totalpages = parseInt(JSON.parse(result).pages);
		if ( totalpages < 1 ) {
			this.setAjaxContent('no pages');
			this._wlButton.prop("disabled", false);
			return;
		}
		this.sendPostPing( totalpages, url );
  },

  sendPostPing: function( total, url ) {
		if (this._currentPage > total) {
			this._wlButton.prop("disabled", false);
			this.addAjaxContent('done');
			return;
		}
		
		var self = this, ping_url = url + '&ping_url=page-' + this._currentPage + '.xml';
		//this.setAjaxContent('sending ping for ' + ping_url);
		jQuery.getJSON( ping_url, function(result) {
				self.addAjaxContent('<p>' + result.ping_url + ' : ' + result.message + '</p>');
				if(result.message == 'OK') {
					self._currentPage += 1;
					self.sendPostPing(total, url);
				} else {
					this.setAjaxContent(result.message);
				}
		});
  },
  
  dialogOpen: function( opt ){
	  this._wlDialog.dialog( "option", opt ).dialog( { 
		  open: jQuery.proxy( this.insertValidator, this )
	  } ).dialog( 'open' );
  },
  
  insertValidator: function() {
	   jQuery('div.ui-dialog-buttonset').prepend( this._wlValidator );
  },
  
  doAjax: function( link ) {
	  this._wlDialog.html( this._elLoadingImg );
	  if( this._wlValidator.prop( 'checked') ) link = link + '&validate=1';
		jQuery.ajax({
			url : link,
			type : "GET",
			success : jQuery.proxy(this.setAjaxContent, this)
		});
  },
 
  
  checkInput: function() {
	  var b = true;
	  jQuery('input[name*=syndi]').each(function(i,v) {
		  if(v.value != '') return true;
		  alert('입력항목 확인후 저장해 주세요.');
		  v.focus();
		  b= false;
	  });
	  return b;
  }
};


jQuery( document ).ready(function( $ ){
  $('input[name="post_category[]"]').change(function(){
  	var checked_categories = $('input[name="post_category[]"]:checked').map(function(){ return this.value; }).get();
  	$('#except_category').val(checked_categories.join(','));
  });
 	
  badrSyndication.dialog.init('p.submit a[href*=admin-ajax]');

});