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
		_bLastSatusIsOn: false, //현재 연동여부
		_bCateIsSyndi: true, //체크된 카테고리의 연동여부
		_bVisiIsSyndi: true, //체크된 visibility 연동여부
		_bStatusIsSyndi: true, //
		_wlButtonOn: {},
		_wlButtonOff: {},
		_wlStatusSelector: {},
		_wlCategorySelector: {},
		_wlCategorySelector: {},
		_wlMessage: {},
			
	  init: function( bIsNewPost ) {
	  	if ( bIsNewPost ) this._bIsNewPost = bIsNewPost;
	  	this._bLastSatusIsOn = ! parseInt($('#syndication_status').val());
	  	this._wlButtonOn = $('#_syndication_do_on');
	  	this._wlButtonOff = $('#_syndication_do_off');
	  	this._wlStatusSelector = $('#post-status-select').on( 'click', 'a.save-post-status', $.proxy(this._setEvent, this, '_statusSelector') );
	  	this._wlVisibilitySelector = $('#post-visibility-select').on( 'click', 'a.save-post-visibility', $.proxy(this._setEvent, this, '_visibilitySelector') );
	  	this._wlCategorySelector = $('#categorychecklist').on( 'click', 'input:checkbox', $.proxy(this._setEvent, this, '_categorySelector') );
	  	this._wlMessage = $('#syndi_notice');
	  	this._setInit();
	   },
	  
	  _setInit: function () { //수정의 경우
	  	this._categorySelector();  //새글일 경우 
	  	if( this._bIsNewPost ) return;  //새글일 경우 
	  	this._statusSelector();
	  	this._visibilitySelector();
	  },
	    
	  _setEvent: function() {
	  	this._bHandled = true;
	  	this[arguments[0]]( arguments[1] );
	  },
	  
	  setMessagese: function( str ) {
	  	this._wlMessage.empty();
	  	this._wlMessage.text( str );
	  },
   	
	  _statusSelector: function(){
			this._bStatusIsSyndi = this._wlStatusSelector.find('select').val() != 'publish' ? false : true ;
			return this.setSyndiButton();
	   },
	   
		_visibilitySelector: function(){
			this._bVisiIsSyndi = this._wlVisibilitySelector.find('input:radio:checked').val() != 'public' ? false : true;
	    this.setSyndiButton();
	   },
	  
	  _categorySelector: function(){
	   	var aCategory = badrSyndication.sCategory.split(',');
			this._bCateIsSyndi = !!this._wlCategorySelector.find('input:checkbox:checked').map(function(i,el){
				if( aCategory.indexOf(el.value) > -1 ) return el.value;
	    }).length;
	   	this.setSyndiButton();
	   },
	  
	  setSyndiButton: function() {
	    this._bCateIsSyndi && this._bVisiIsSyndi && this._bStatusIsSyndi ? this.setOnSyndi() : this.setOffSyndi();
	  },
	
	  setOnSyndi: function() {
	  	if ( !this._bLastSatusIsOn ) return this.setOffSyndi();
	  	this.setButtonEnable();
	  	this._wlButtonOn.prop('checked', true);
	  },
	  
	  setOffSyndi: function() {
	  	this._wlButtonOff.prop('checked', true);
			if (!this._bCateIsSyndi || !this._bStatusIsSyndi || !this._bVisiIsSyndi ) this.setButtonDisabled();
			else this.setButtonEnable();
	  },
	  
	  setButtonDisabled: function() {
	  	this.setButtonAttr( true );
	  	this.setMessagese( 'Can not syndicate' );	  	
	  },
	  
	  setButtonEnable: function() {
	  	this.setButtonAttr( false );
	  	this.setMessagese();	  	
	  },
	  
	  setButtonAttr: function (b) {
	  	this._wlButtonOn.prop('disabled', b);
	  },
	}

	badrSyndication.metabox.init( !!parseInt( badrSyndication.bIsNewPost ) );
})