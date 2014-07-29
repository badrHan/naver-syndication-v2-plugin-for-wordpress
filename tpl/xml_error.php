<?php echo('<?xml version="1.0" encoding="utf-8"?>')?>
<?php
switch($this->message){
	case 1:
		$message = '설정이 유효하지 않습니다.';
		break;
	case 2:
		$message = '유효한 파라미터가 아닙니다. page-0000.xml, post-0000.xml의 형식이어야 합니다.';
		break;
	case 2:
		$message = 'target is not founded';
		break;
	default:
		$message = '알수없는 에러가 발생하였습니다';
		break;
}
?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<error>-1</error>
 	<message><?php echo $message?></message>
</feed>
