<input type="hidden" name="playedGroup" value="<?=$this->groupId?>" />
<input type="hidden" name="playedId" value="<?=$this->played?>" />
<input type="hidden" name="type" value="<?=$this->type?>" />
<?php foreach(array(' (龙) ') as $var){ ?>
<div class="pp" action="tz5xDwei" length="1" random="sscRandom">
	<div class="wei"><?=$var?></div>
	&nbsp;
	<ul class="nList" style="display:inline;float:left;">
	<input type="button" value="万千 " class="code reset2" />
	<input type="button" value="万百 " class="code reset2" />
	<input type="button" value="万十 " class="code reset2" />
	<input type="button" value="万个 " class="code reset2" />
	<input type="button" value="千百 " class="code reset2" />
	<input type="button" value="千十 " class="code reset2" />
	<input type="button" value="千个 " class="code reset2" />
	<input type="button" value="百十 " class="code reset2" />
	<input type="button" value="百个 " class="code reset2" />
	<input type="button" value="十个" class="code reset2" />

	&nbsp;&nbsp;

</div>
<?php
	}
	
	$maxPl=$this->getPl($this->type, $this->played);
?>
<script type="text/javascript">
$(function(){
	gameSetPl(<?=json_encode($maxPl)?>);
})
</script>

