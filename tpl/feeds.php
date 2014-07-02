<?php echo('<?xml version="1.0" encoding="utf-8"?>')?>

<feed xmlns="http://webmastertool.naver.com">
	<id><?php echo $result->id?></id>
	<title><?php echo $result->title?></title>
	<author>
		<name><?php echo $result->author->name?></name>
		<email><?php echo $result->author->email?></email>
	</author>
	<updated><?php echo $result->updated?></updated>
	<link rel="site" href="<?php echo $result->link->href ?>" title="<?php echo $result->link->title?>" />
<?php if(isset($result->articles) && count($result->articles->list)):?>
	<?php foreach($result->articles->list as $key => $article):?>
	<?php if(empty($article) && $result) $article = &$result;?>
	<entry>
		<id><?php echo $article->id?></id>
		<title><![CDATA[<?php echo $article->title?>]]></title>
		<author>
		    <name><?php echo $article->nick_name ?></name>
		</author>
		<updated><?php echo $article->updated ?></updated>
		<published><?php echo $article->regdate ?></published>
		<link rel="via" href="<?php echo $article->via_href ?>" title="<?php echo $article->via_title ?>" />
		<link rel="mobile" href="<?php echo $article->mobile_href ?>" />
		<content type="html"><![CDATA[<?php echo $article->content ?>]]></content>
		<?php if(isset($article->summary)):?>
		<summary type="text"><![CDATA[<?php echo $article->summary ?>]]></summary>
		<?php endif?>
		<category term="<?php echo $article->category_term ?>" label="<?php echo $article->category_label ?>" />
	</entry>
	<?php endforeach?>
<?php elseif(isset($result->deleted) && count($result->deleted->list)):?>
	<?php foreach($result->deleted->list as $key => $delete):?>
	<entry>
		<id><?php echo htmlspecialchars($delete->id)?></id>
		<title><?php echo htmlspecialchars($delete->title)?></title>
		<updated><?php echo $delete->updated?></updated>
		<deleted><?php echo $delete->deleted?></deleted>
		<link rel="alternative" href="<?php echo urldecode($delete->alternative_href)?>" />
	</entry>
	<?php endforeach?>
<?php endif?>
</feed>
