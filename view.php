<?php
function error($message = 'Not Found', $code = 404) {
	header(sprintf('HTTP/1.0 %d %s', $code, $message));
	template_header();
	dispay_a_friggin_narwhal($message);
	template_footer();
	exit;	
}

function template_header() {
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo SITE_TITLE; ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="http://twitter.github.com/bootstrap/1.4.0/bootstrap.min.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo CSS_URL; ?>" />
	</head>
	
	<body>
		<div id="inner">
			<div id="content">
<?php
}

function template_footer() {
?>
			</div>			
		</div>
	</body>
</html>
<?php
}

function make_srl( $input ) {
	return sprintf('<a href="/%s.html">(:%s:%s:)</a>', $input['i'], LINK_CODE, $input['i']);
}

function format_body( $content ) {
	$content = preg_replace('/\n+/',"\n", $content);
	$content = preg_replace_callback('/\(:(?P<i>[a-zA-Z0-9]{12}):\)/', 'make_srl', $content);
	$content = wordwrap($content, 42, "\n", TRUE);
	return $content;
}

function template_topic_detail($topic, $replies) {
?>
	<div class="topic" id="topic_<?php echo $topic['_id'] ?>">
		<div class="topic_header">
			<?php if( !empty($topic['x']) ): ?>
				<img class="topic_icon" src="<?php echo $topic['x'] ?>" width="32" height="32" />
			<?php endif; ?>

			<h2><?php echo htmlentities($topic['title']); ?></h2>
			<div class="topic_body">
				<?php echo nl2br(format_body(htmlentities($topic['body']))); ?>
			</div>
		</div>

		<?php $i = 0; ?>
		<?php foreach( $replies AS $reply_id => $reply ): ?>
		<div class="post" id="post_<?php echo $topic['_id'].$reply_id; ?>">
			<div class="post_header">
				<?php if( !empty($reply['x']) ): ?>
					<img class="post_icon" src="<?php echo $reply['x'] ?>" width="32" height="32" />
				<?php endif; ?>

				<?php if( !empty($reply['title']) ): ?>
				<div class="title"><?php echo htmlspecialchars($reply['title']); ?>
					<?php if( isset($reply['c']) && intval($reply['c']) ): ?>
						<span class="post_subcount">+<?php echo intval($reply['c']) ?></span>				
					<?php endif; ?>
				</div>	
				<?php endif; ?>
				<div class="post_info">					
					<a href="/<?php echo gimme_link($reply) . $reply['_id'] ?>.html">				
						<span class="post_author"><?php echo htmlspecialchars($reply['name']); ?></span>						
						<span class="post_id">#id:<?php echo $reply_id; ?></span>

						<span class="post_when"><?php echo date('H:i, Y-m-d', $reply['w']) ?></span>
					</a>
				</div><!-- .post_info -->
			</div>
			<div class="content">
				<?php echo nl2br(format_body(htmlentities($reply['body']))); ?>
			</div>
		</div>
		<?php endforeach ?>
		
		<?php template_topic_reply_form($topic['p'], $topic['_id'], can_add_to($topic)); ?>
	</div><!-- .topic -->
<?php
}

function template_topic_reply_form($parent_id, $id, $can_add) {
?>	
<div class="row"><div class="span8">
	<form class="form-horizontal" method="post" enctype="multipart/form-data">
	<?php if( $can_add ): ?>		
	<fieldset>
		<div class="control-group">
			<label class="control-label" for="reply_title"><?php echo REPLY_TITLE_FIELD ?></label>
			<div class="controls">
				<input type="text" id="reply_title" class="span3"  name="title" maxlength="100" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="reply_name"><?php echo REPLY_NAME_FIELD ?></label>
			<div class="controls">
				<input type="text" id="reply_name" class="span3"  name="name" maxlength="100" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="reply_icon"><?php echo REPLY_ICON_FIELD ?></label>
			<div class="controls">
				<input type="file" id="reply_icon" class="span3"  name="icon" />
				<p class="help-block"><?php echo REPLY_RULES ?></p>
			</div>
		</div>
		
		<div class="control-group">
			<div class="controls">
				<textarea id="reply" id="reply_body" class="span3"  name="body" rows="6" cols="40"></textarea><br />						
			</div>
		</div>
		<div class="form-actions">
			<input class="btn btn-inverse" type="submit" value="<?php echo REPLY_TITLE ?>" />
		</div>
	</fieldset>
	<?php else: ?>
		<h2>Topic Closed</h2>
	<?php endif; ?>
	</form>
</div></div>
<?php
}

function dispay_a_friggin_narwhal($message = 'Moar Narwhals') {
?>
<a href="<?php echo gimme_random(12) ?>.html"><img alt="<?php echo $message ?>" src="narwhal.png" id="narwhal" /></a>
<?php	
}
