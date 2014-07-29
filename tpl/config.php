<div class="wrap">
	<h2>네이버 신디케이션 V2</h2>
	<div id="updatemessage" class="updated fade" style="display: <?php echo $bResult ? 'block' : 'none'?>;"><p>설정이 업데이트 되었습니다.</p></div>
	<div class="postbox-container" style="width:60%;">
		<div class="metabox-holder">
			<div class="meta-box-sortables">
				<div id="gasettings" class="postbox">
           			<div class="handlediv" title="Click to toggle"><br/></div>
            		<h3 class="hndle"><span>설정</span></h3>
					<div class="inside">
						<form method="post">
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row"><span>연동키</span></th>
									<td>
										<input type="text" name="syndi_key" class="large-text" value="<?php echo $this->_aOptions['key'] ? $this->_aOptions['key'] : ''?>" title="연동키" />
										<p><a href="http://webmastertool.naver.com/index.naver" target="blank">네이버 웹마스터 도구</a>에서 발급받은 연동키를 입력하세요.</p>
									</td>
								</tr>
								<tr class="even">
									<th scope="row"><span>관리자</span></th>
									<td>
										<input type="text" name="syndi_name" value="<?php echo $this->_aOptions['name'] ? $this->_aOptions['name'] : ''?>"  />
										<p>사이트 관리자나 회사명, 저작권자의 이름을 입력하세요.</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><span>관리자 이메일</span></th>
									<td>
										<input type="text" name="syndi_email" value="<?php echo $this->_aOptions['email'] ? $this->_aOptions['email'] : get_option('admin_email', false)?>" />
									</td>
								</tr>
								<tr class="even">
									<th scope="row"><span>제외할 카테고리</span></th>
									<td>
								    	<div style="border-color:#CEE1EF; border-style:solid; border-width:2px; height:10em; margin:5px 0px 5px 0px; overflow:auto; padding:0.5em 0.5em; background-color:#fff;">
								    	<ul>
								    	<?php wp_category_checklist( 0, 0, explode( ',', $this->_aOptions['except_category']) );?>
								    	</ul>
								   		</div>
								   		<input type="hidden" value="<?php echo $this->_aOptions['except_category']?>" name="except_category" id="except_category" />
								   	</td>
							   	</tr>
								<tr>
									<td colspan="2">
								    	<div class="alignright"><input type="submit" class="button-primary" name="submit" value="설정 저장"></div>
								   	</td>
							   	</tr>
						   	</tbody>
					   	</table>
						</form>
					</div>
				</div>
			</div>

			<div class="meta-box-sortables">
				<div id="gasettings" class="postbox">
           			<div class="handlediv" title="Click to toggle"><br/></div>
            		<h3 class="hndle"><span>동작확인</span></h3>
					<div class="inside">
						
						<p>
							신디케이션문서의 생성여부, 문서의 정합성, 핑발송, 네이버봇의 응답을 확인합니다.<br />최근 발행된 문서가 없을 경우 임의의 문서를 생성합니다.
						</p>
						<?php echo $yeti_visited_time ? '네이버 문서수집 로봇이 최근 방문한 시각은 <span class="message">'.$yeti_visited_time.'</span>입니다.' : ''?>
						<p>
							<input type="checkbox" name="configCheckValidator" id="configCheckValidator" />	<label for="configCheckValidator">신디케이션문서가 정해진 형식에 맞는지 정합성 검사를 수행합니다.</label>
						</p>
						
			    		
			    		<p class="syndication_ajax">
			    			<input type="button" name="configCheck" class="button-primary" value="동작확인" />
			    			<span id="configCheckResult" style="display: none" class="message">데이터를 수신중입니다...</span>
			    		</p>
							

					</div>
				</div>
			</div>			
			
			<div class="meta-box-sortables">
				<div id="gasettings" class="postbox">
           			<div class="handlediv" title="Click to toggle"><br/></div>
            		<h3 class="hndle"><span>문서 목록 발송</span></h3>
					<div class="inside">
					<p>연동설정된 문서목록을 발송합니다. 플러그인이 설치되기전에 작성된 문서를 네이버신디케이션과 연동하거나 이미 색인된 문서를 갱신할수 있습니다. 목록당 최대100개의 문서정보가 포함되며 각 문서는 현재 시각으로 업데이트 날짜가 변경됩니다.</p>
			    	<p><input type="checkbox" name="sendPagesValidator" id="sendPagesValidator" /> <label for="sendPagesValidator">DOM Extension이 설치되었을 경우 생성되는 문서가 정해진 형식에 맞는지 체크합니다. </label></p>
			    	<p class="submit">
			    	<a href="<?php echo admin_url('admin-ajax.php')?>?action=sendPagePing" class="button" target="sendPages">문서목록 발송</a>
			    	</p>
					<br />  	

					</div>
				</div>
			</div>	

			<div class="meta-box-sortables">
				<div id="gasettings" class="postbox">
           			<div class="handlediv" title="Click to toggle"><br/></div>
            		<h3 class="hndle"><span>색인문서 정리</span></h3>
					<div class="inside">
					<p>네이버봇이 무작위로 수집해간 문서의 색인을 정리하기 위하여 색인된 전체 문서에 대하여 삭제요청을 보냅니다. 색인문서수를 참고하여 색인이 정리된 후에 문서목록을 재발송하는 방법이 있습니다.</p>
			    	<p style="background-color: #f6f6f" class="syndication_ajax">
			    	<input type="button" name="getIndexed" class="button" value="색인된 문서수 확인" />
			    	<span id="getIndexedResult">색인된 문서목록을 받아옵니다. 문서수에 따라 시간이 걸릴수도 있습니다.</span>
			    	</p>
			    	
			    	<a href="<?php echo admin_url('admin-ajax.php')?>?action=sendResetPing" class="button" target="sendReset">색인리셋</a>
			    	</p>
					<br />  	

					</div>
				</div>
			</div>	
			
		</div>
	</div>
</div>	