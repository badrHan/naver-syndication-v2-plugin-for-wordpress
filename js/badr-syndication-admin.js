//var badrSyndication = badrSyndication || {};// set in naver-syndication-admin.php, wp_localize_script

jQuery( document ).ready(function( $ ){

	badrSyndication.admin = {
		
		_htCode : {
			'000' : '정상작동 중입니다.',
			'024' : '인증 실패하였습니다.',
			'028' : 'OAuth Header 가 없습니다.',
			'029' : '요청한 Authorization값을 확인할 수 없습니다.',
			'030' : 'https 프로토콜로 요청해주세요.',
			'061' : '잘못된 형식의 호출 URL입니다.',
			'063' : '잘못된 형식의 인코딩 문자입니다.',
			'071' : '지원하지 않는 리턴 포맷입니다.',
			'120' : '전송된 내용이 없습니다. (ping_url 필요)',
			'121' : '유효하지 않은 parameter가 전달되었습니다.',
			'122' : '등록되지 않은 사이트 입니다.',
			'123' : '1일 전송 횟수를 초과하였습니다.',
			'130' : '서버 내부의 오류입니다. 재시도 해주세요.',
			'999' : '액세스 토큰값이 없습니다.',
			'998' : '생성된 신디케이션 문서가 정해진 포맷에 맞지 않습니다.',
			'997' : '네이버신디케이션API서버로 부터 수신된 문서를 파싱하는데 문제가 있습니다.',
			'996' : '정합성 체크를 위해서는 PHP DOM 익스텐션이 필요합니다.'
		},
		_elLoadingImg: '<img src="' + badrSyndication.plugin_url + 'img/loadingAnimation.gif" width="208" />',
		_currentPage: 1,
			
	  init: function( elButton ) {
		this._wlButton = $( elButton ).on( 'click', $.proxy(this.setEvent, this) );
	  },
	  
	  

	  configResult: function( result ) {
		  	this._wlDialog.append( result );
	  },
	
	  setEvent: function( e ) {
		e.preventDefault();
		if( !this.checkInput() ) return;
		var el = e.target || e.srcElement;
	  	if( typeof( this[el.name] ) == 'function' ) this[el.name]( el );
	  },

	  configCheck: function( el ) {
		  var self = this, link = badrSyndication.ajax_url + '?action=' + el.name;
		  this.wlResult = $('#configCheckResult');
		  if( $('#configCheckValidator').prop( 'checked') ) link += '&validate=1';
		  $.ajax( {
			  url:link, 
			  beforeSend: function() { 
				  this._wlButton.prop('disabled', true);
				  this.wlResult.html(self._elLoadingImg).css('display', 'inline');
			  }
		  })
		  .done(function( result ){
			  console.log(result);
			  this._wlButton.prop('disabled', false);
			  this.wlResult.html(self._htCode[result]);
		  });
	  },

	  /**
	   * badrSyndicationAdmin::sendResetPing api.badr.kr 에서 인덱스된 리스트를 받아온다.
	   * @since 0.8 url admin-ajax.php?action=sendResetPing
	   * @param el object Element object 
	   */
	  getIndexed: function( el ) {
		  var self = this, link = badrSyndication.ajax_url + '?action=' + el.name;
		  this.wlResult = $('#getIndexedResult');
		  $.ajax( {
			  url:link, 
			  beforeSend: function() { 
				  self._wlButton.prop('disabled', true);
				  self.wlResult.html(self._elLoadingImg).css('display', 'inline');
			  }
		  })
		  .done(function( result ){
			 console.log(result);
			  if(parseInt(result) > 1) {
				  self.indexedTotalPage =  parseInt(result / 100 + 1);
				  self.getIndexedProcSave( link, result );
			  } else {
				  self.wlResult.html('수신된 목록이 없습니다');
			  }
		  });
	  },

	  getIndexedProcSave: function( link ) {
			if (this._currentPage > this.indexedTotalPage) {
				this._wlButton.prop("disabled", false);
				this.wlResult.html('done');
				return;
			}
			this.wlResult.html(this._currentPage + '페이지를 저장하고 있습니다...');
			this.sendIndexDelPing(this._currentPage);
			
			this._currentPage += 1;
			link += '&start=' + this._currentPage;		

			//this.setAjaxContent('sending ping for ' + ping_url);
			var self = this;
			$.ajax( link )
			.done(
				function() {
					self.getIndexedProcSave(link);
				});
			//this._currentPage = 1; //post ping을 위해서 초기화
	  },
	   
	  sendIndexDelPing: function( page ) {
		  var self = this, link = badrSyndication.ajax_url + '?action=getIndexed&page=' + page;
			$.ajax({
				url: link,
				async: false
			})
			.done(
				function( result ) {
					if( result == '000') self.wlResult.html(this._currentPage + '페이지를 저장하고 있습니다...');
					else self.wlResult.html(self._htCode[result]);
				});
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
	  
	  //badrSyndicationAdmin::sendPagePing 에 전달되는 url에 ping_url파라미터가 없을 경우 포스트리스트를 받아온다
	  getPages: function( url ) {
			$.ajax({
				url : url,
				type : "GET",
				success : $.proxy(this.sendPagePing, this, url)
			});
	  },
	
	//badrSyndicationAdmin::sendPagePing 에 전달되는 url에 ping_url파라미터가 없을 경우 posts count를 받아온다
	  sendPagePing: function( url, result ) {
			var totalpages = parseInt(JSON.parse(result).pages);
			if ( totalpages < 1 ) {
				this.setAjaxContent('no pages');
				this._wlButton.prop("disabled", false);
				return;
			}
			this.sendPostPing( totalpages, url );
	  },
	
	//badrSyndicationAdmin::sendPagePing 에 전달되는 url에 ping_url파라미터를 전달하여 핑을 발송하고 리턴메세지를 출력한다.
	  sendPostPing: function( total, url ) {
			if (this._currentPage > total) {
				this._wlButton.prop("disabled", false);
				this.addAjaxContent('done');
				return;
			}
			
			var self = this, ping_url = url + '&ping_url=page-' + this._currentPage + '.xml';
			//this.setAjaxContent('sending ping for ' + ping_url);
			$.getJSON( ping_url, function(result) {
					self.addAjaxContent('<p>' + result.ping_url + ' : ' + result.message + '</p>');
					if(result.message == 'OK') {
						self._currentPage += 1;
						self.sendPostPing(total, url);
					} else {
						this.setAjaxContent(result.message);
					}
			});
			this._currentPage = 1; //reset ping을 위해서 초기화
	  },
	  
	  dialogOpen: function( opt ){
		  this._wlDialog.dialog( "option", opt ).dialog( { 
			  open: $.proxy( this.insertValidator, this )
		  } ).dialog( 'open' );
	  },
	  
	  insertValidator: function() {
		   $('div.ui-dialog-buttonset').prepend( this._wlValidator );
	  },
	  
	  doAjax: function( link ) {
		  this.setAjaxContent( this._elLoadingImg );
		  if( this.wlValidator.prop( 'checked') ) link = link + '&validate=1';
			$.ajax({
				url : link,
				type : "GET",
				success : $.proxy(this.setAjaxContent, this)
			});
	  },
	 
	  checkInput: function() {
		  var b = true;
		  $('input[name*=syndi]').each(function(i,v) {
			  if(v.value != '') return true;
			  alert('입력항목 확인후 저장해 주세요.');
			  v.focus();
			  b= false;
		  });
		  return b;
	  }
	};

	badrSyndication.admin.init('p.syndication_ajax input[type="button"]');
	
  $('input[name="post_category[]"]').change(function(){
  	var checked_categories = $('input[name="post_category[]"]:checked').map(function(){ return this.value; }).get();
  	$('#except_category').val(checked_categories.join(','));
  });
 
  var wlUpdateMessage = $('#updatemessage');
  if( !wlUpdateMessage.prop('hidden') ) setTimeout(function(){wlUpdateMessage.hide('slow');}, 3000);
  
  
  

});