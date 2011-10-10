<?php
// template functions
function template_header( $breadcrumbs = NULL ) {
	if( is_array($breadcrumbs) ) {
		$breadcrumbs = ' - ' . implode(' - ', $breadcrumbs);
	}
	else if( strlen($breadcrumbs) ) {
		$breadcrumbs = ' - ' . $breadcrumbs;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo SITE_TITLE . $breadcrumbs; ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="<?php echo CSS_URL; ?>" />
		
	</head>
	
	<body>
		<div id="inner">
			<div class="right">
				<a class="new_topic_link" href="/post"><?= NEW_TOPIC_TITLE ?></a>
			</div>
			<div id="content">
<?php
}

function template_footer() {
	define('END_TIME', microtime(TRUE));
?>
			</div>
			
			<div id="footer">
				<sub><?= sprintf(FOOTER_TEXT, END_TIME - START_TIME) ?></sub>			
			</div>
		</div>
	</body>

</html>
<?php
}

function template_index_header() {
?>
	<h2><?= TOPIC_TITLE ?></h2>
	
	<table class="topic_list">
		<thead>
			<tr>
				<th class="topic_head topic_id">ID</th>
				<th class="topic_head topic_name">Name</th>
				<th class="topic_head topic_when">When</th>
				<th class="topic_head"># <?= REPLY_TITLE ?></th>
			</tr>
		</thead>
		<tbody class="topic_list">
<?php
}

function template_index_topic($id, $topic) {
?>
	<tr>
		<td><small><code><?= $topic['_id'] ?></code></small></td>
		<td>
			<a class="topic_link topic_title" href="/<?= $id ?>">
				<?= htmlentities($topic['title']); ?>
			</a>
		</td>
		<td>
			<?= date('Y-m-d H:i', $topic['w']) ?>
		</td>
		<td><?= sprintf('%d', $topic['c']); ?></td>	
	</tr>
<?php
}

function template_index_footer() {
?>
		</tbody>
		<tfoot>
			<tr>
			</tr>
		</tfoot>
	</table>	
<?php
}

function template_new_header() {
?>
	<h2><?= NEW_TOPIC_TITLE ?></h2>
<?php
}

function template_new_form() {
?>
	<form action="/post" method="post">
		Title<br />
		<input type="text" name="title" maxlength="100" /><br />
		<?= REPLY_NAME_FIELD ?><br />
		<sub><?= REPLY_NOTE ?></sub><br />
		<input type="text" name="name" maxlength="100" /><br />
		<?= REPLY_CONTENT_FIELD ?><br />
		<textarea name="body" rows="6" cols="50"></textarea><br />
		<input type="submit" value="<?= ADD_TITLE ?>" />
	</form>
<?php
}

function template_new_footer() {
?>
	<sub>Please don't post anything illegal, kthxbye.</sub>
<?php
}

function template_topic_header($topic) {
?>
	<h2>Topic: <?= htmlentities($topic['title']); ?></h2>
	<div>
		<?= htmlentities($topic['body']) ?>
	</div>
<?php
}

function template_topic_post($topic_id, $post_id, $post) {
?>
	<div class="post" id="post_<?= $topic_id.$post_id; ?>">
		<div class="author">
			<span class="author"><?= $post['name']; ?></span>
			<small>@ <?= date('Y-m-d H:i', $post['w']) ?></small>			
			<a href="#reply" class="quote_link right">
				#<code><?= $post_id; ?></code>
			</a>
		</div>
		<div class="content">
			<?php echo $post['body']; ?>
		</div>
	</div>
<?php
}

function template_topic_reply_form($id) {
?>
	<h3><?= REPLY_TITLE ?></h3>
	<form id="reply_form" action="/<?= $id ?>" method="post">
		<a name="reply"></a>
		<?= REPLY_NAME_FIELD ?><br />
		<sub><?= REPLY_NOTE ?></sub><br />
		<input type="text" name="name" maxlength="100" /><br />
		<?= REPLY_CONTENT_FIELD ?><br />
		<textarea id="reply" name="body" rows="6" cols="50"></textarea><br />
		<input type="submit" value="<?= REPLY_TITLE ?>" />
	</form>
<?php
}

function template_topic_footer() {
?>
				
<?php
}