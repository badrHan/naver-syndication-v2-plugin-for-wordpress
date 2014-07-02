var badrSyndication = badrSyndication || {};

/**
	* badrSyndication variables
	* aCategory : 연동카테고리
	* aSelectedCategory : 포스트 카테고리
	*
	*/
jQuery( document ).ready(function( $ ){
	
	badrSyndication.metabox = {
		
		_bIsNewPost: false, //새글
		_bLastSatus: false, //현재 연동여부
		_bHandled: false, //이벤트 발생
		_bCateIsSyndi: true, //체크된 카테고리의 연동여부
		_bVisiIsSyndi: true, //체크된 visibility 연동여부
		_bStatusIsSyndi: true, //
		_wlButton: {},
		_wlStatusSelector: {},
		_wlCategorySelector: {},
		_wlCategorySelector: {},
			
	  init: function( bIsNewPost ) {
	  	if ( bIsNewPost ) this._bIsNewPost = bIsNewPost;
	  	this._wlButton = $('select[name="_syndication_do_off"]').on( 'change', $.proxy(this._setEvent, this, '_syndiSelector') );;
	  	this._wlStatusSelector = $('#post-status-select').on( 'change', 'select', $.proxy(this._setEvent, this, '_statusSelector') );
	  	this._wlVisiableSelector = $('#post-visibility-select').on('change', 'input:radio', $.proxy(this._setEvent, this, '_visibilitySelector') );
	  	this._wlCategorySelector = $('#categorychecklist').on( 'change', 'input:checkbox', $.proxy(this._setEvent, this, '_categorySelector') );
	  	this._setInit();
	   },
	  
	  _setInit: function () { //수정의 경우
	  	if( this._bIsNewPost ) return;  //새글일 경우 
	  	this.bLastStatus = !parseInt($('#syndication_status').val()); //post meta 에 저장된 _syndication 값
	  	this.bLastStatus ? this.setOnSyndi() : this.setOffSyndi();
	  },
	  
	  _setEvent: function( method_name ) {
	  	this._bHandled = true;
	  	this[method_name]();
	  },
	  
	  setMessagesetEvent: function(str) {
	  	var wlMessage = this._wlButton.parent().siblings('span');
	  	wlMessage.empty();
	  	wlMessage.text(str);
	  },

	  _syndiSelector: function(){
	  	console.log( this._wlButton.val() );
	   },
	   	
	  _statusSelector: function(){
			this._bStatusIsSyndi = this._wlStatusSelector.find('select').val() != 'publish' ? false : true ;
			return this.setSyndiButton();
	   },
	   
		_visibilitySelector: function(){
	   	//this._bVisiIsSyndi = e.target.value != 'public' ? false : true;
			this._bVisiIsSyndi = this._wlVisibilitySelector.find('input:radio:checked').val() != 'public' ? false : true;
	    this.setSyndiButton();
	   },
	  
	  _categorySelector: function(){
	   	var aCategory = badrSyndication.sCategory.split(',');
			this._bCateIsSyndi = !!this._wlCategorySelector.find('input:checkbox:checked').map(function(i,el){
				if( aCategory.indexOf(el.value) > -1 ) return el.value;
	    }).length;
	    return this.setSyndiButton();
	   },
	  
	  setSyndiButton: function() {
	    (this._bCateIsSyndi && this._bVisiIsSyndi && this._bStatusIsSyndi) ? this.setOnSyndi() : this.setOffSyndi();
	  },
	
	  setOnSyndi: function() {
	  	//this._wlButton.val('0');
	  	if ( !this._bLastSatus ) return;
	  	this._wlButton.find('option[value="0"]').attr('selected', 'selected');
	  	//if ( this._bHandled ) this.setButtonProperty( false );
	  },
	  
	  setOffSyndi: function() {
	  	//this._wlButton.val('1');
	  	this._wlButton.find('option[value="1"]').attr('selected', 'selected');
			if ( this._bHandled ) this.setButtonDisabled();
	  },
	  
	  setButtonDisabled: function() {
	  	this._wlButton.find('option[value="0"]').attr('disabled', 'true');
	  	var str = 'This post can\'t be syndicated';
	  	this.setMessagesetEvent( str );	  	
	  } 
	}


	/*
	 * for localize_script.js bug?
	 * convert string to int 
	*/
	badrSyndication.metabox.init( !!parseInt( badrSyndication.bIsNewPost ) );
})