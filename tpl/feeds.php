<feed xmlns="http://webmastertool.naver.com">
	<id><?php echo $this->id?></id>
	<title><?php echo $this->title?></title>
	<author>
		<name><?php echo $this->author->name?></name>
		<email><?php echo $this->author->email?></email>
	</author>
	<updated><?php echo $this->updated?></updated>
	<link rel="site" href="<?php echo $this->link->href ?>" title="<?php echo $this->link->title?>" />
<?php if(isset($this->entries) && count($this->entries)):?>
	<?php foreach($this->entries as $key => $entry):?>
	<?php if($entry->syndication):?>
	<entry>
		<id><?php echo $entry->id?></id>
		<title><?php echo $entry->title?></title>
		<author>
		    <name><?php echo $entry->nick_name ?></name>
		</author>
		<updated><?php echo $entry->updated ?></updated>
		<published><?php echo $entry->regdate ?></published>
		<link rel="via" href="<?php echo $entry->via_href ?>" title="<?php echo $entry->via_title ?>" />
		<link rel="mobile" href="<?php echo $entry->mobile_href ?>" />
		<content type="html"><?php echo $entry->content ?></content>
		<?php if(isset($entry->summary)):?>
		<summary type="text"><?php echo $entry->summary ?></summary>
		<?php endif?>
		<category term="<?php echo $entry->category_term ?>" label="<?php echo $entry->category_label ?>" />
	</entry>

	<?php else:?>
	<deleted-entry ref="<?php echo $entry->id ?>" when="<?php echo $entry->regdate?>" />
	<?php endif?>


	<?php endforeach?>
<?php endif?>

</feed>
