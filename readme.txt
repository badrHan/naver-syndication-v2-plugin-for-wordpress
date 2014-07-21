=== Naver Syndication V2 ===
Contributors: badrHan
Donate link: http://badr.kr
Plugin URI: http://badr.kr/?p=1152
Tags: syndication, naver, 네이버, 신디케이션
Requires at least: 2.6
Tested up to: 3.9.1
Stable tag: 0.7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

2014년 7월 새로 공개된 네이버신디케이션 API를 이용 네이버검색서비스와 연동합니다.

== Description ==

네이버 신디케이션 API를 이용해 워드프레스의 컨텐츠를 네이버 웹 서비스에 전달할 수 있도록 정해진 형식에 맞춰 작성하여 네이버검색로봇에 알리는 플러그인 입니다.
포스트가 작성, 수정, 휴지통으로 이동, 휴지통에서 복구 될때 마다 네이버 검색서비스에 해당포스트의 주소를 담은 핑을 발송하며
핑을 수신한 네이버봇은 해당 주소를 방문하여 포스트의 정보를 수집해 갑니다.

= Features =

2014년 7월 이전에 공개된 네이버 신디케이션 API는 현재 사용할 수 없으며 새로 공개된 API가 적용되었습니다.
* 기존 문서 목록 발송
* 연동키 체크
* 문서 정합성 체크
* 핑발송 체크
* 응답 체크
* 포스트 메타박스 체크박스


== Installation ==

* 다운로드 받은 파일의 압축을 풀고, wp-content/plugins 디렉토리 하위에 badr-naver-syndication 디렉토리를 업로드하고 플러그인을 활성화 합니다.
* [네이버 웹마스터 도구](http://webmastertool.naver.com/ "Your favorite software")에서 연동키를 발급받습니다.
* 관리자메뉴의 "설정 > 네이버 신디케이션" 설정페이지에서 발급받은 연동키를 입력하고 저장한 후에 "설정확인" 버튼을 눌러서 "OK"메세지가 나오면 정상적으로 핑을 주고 받는 상태입니다. 

== Screenshots ==

1. 설정화면
2. 동작확인
3. 문서목록 발송
4. 포스트 발행 여부 체크

== Changelog ==

= 0.7.1 =
badr-loger 플러그인이 설치되지 않았을 경우 ChromePhp::log 실행 제거

= 0.7 =
* 기존 문서 목록 발송
* 연동키 체크
* 문서 정합성 체크
* 핑발송 체크
* 응답 체크
* 포스트 메타박스 체크박스
