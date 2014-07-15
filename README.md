# 네이버 신디케이션 워드프레스 플러그인

## 네이버 신디케이션 문서
네이버 신디케이션 문서란, 웹 사이트의 콘텐츠를 네이버 웹 서비스에 전달할 수 있도록 정해진 형식에 맞춰 작성한 문서입니다.  네이버 신디케이션 문서는 XML 기반의 문서 포맷인 ATOM을 참고하여 네이버 검색 서비스에 연동할 수 있게 보완한 문서 형식을 사용합니다.

## 설치
다운로드 받은 파일의 압축을 풀고, wp-content/plugins 디렉토리 하위에 badr-naver-syndication 디렉토리를 업로드 합니다.


## 기능
포스트 작성, 수정,휴지통이동, 휴지통복구 시 네이버봇에 알림

## 설정

### 설정페이지
* 연동키 입력
* 연동제외 카테고리 선택

![ScreenShot](http://note.badr.kr/wp-content/uploads/2014/07/admin-1.png)

### 동작확인
* 연동키 확인
* 생성문서의 정합성 확인
* 네이버봇 응답 확인
![ScreenShot](http://note.badr.kr/wp-content/uploads/2014/07/config-check.png)



### 문서목록 발송
* 기존 포스트의 정합성 확인후 발송
* 페이지당 100개의 포스트 목록을 발송
![ScreenShot](http://note.badr.kr/wp-content/uploads/2014/07/send-page-ping.png)

### 포스트 작성시 발행여부 체크
* 제외카테고리는 연동체크가 안됨
* 비밀글이나, pending등의 경우 연동안됨
* 특정글 연동제외 할 경우 선택
![ScreenShot](http://note.badr.kr/wp-content/uploads/2014/07/post.png)
