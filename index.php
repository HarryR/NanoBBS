<?php	
get_magic_quotes_gpc() && die('Disable magic_quotes_gpc in php.ini');
date_default_timezone_set('UTC');

###############################################################################
# Configuration
# - Edit the following fields

// Translatable fields
define('SITE_TITLE', 'Mongochan');
define('TOPIC_TITLE', 'Topic');
define('DELETE_TITLE', 'Delete');
define('NEW_TOPIC_TITLE', 'New Topic');
define('ADD_TITLE', 'Add');
define('REPLY_TITLE', 'Reply');
define('REPLY_NAME_FIELD', 'Name');
define('REPLY_CONTENT_FIELD', 'Body');
define('REPLY_NOTE', 'Tripcodes supported');
define('FOOTER_TEXT', 'Powered by <a href="http://mongodb.org/">MongoDB</a> and <a href="https://github.com/Purgox/NanoBBS">NanoBBS</a> - loaded in %.4f seconds');

// URLs
define('BASE_URL', 'http://localhost:8888/');
define('CSS_URL', 'style.css');
define('HOME_URL', '');
define('SELF', HOME_URL);

// Anti Spam
define('TIME_BETWEEN_TOPICS', 120);
define('TIME_BETWEEN_POSTS', 30);

# End of Configuration
###############################################################################

try {
	require_once('model.php');
	require_once('view.php');
	require_once('util.php');
	$bbs = bbs::instance();
}
catch( Excepiton $ex ) {
	error($ex->getMessage());
}

define('START_TIME', microtime(TRUE));

switch( param('a', 'list') ) {
case 'list':
	template_header();
	template_index_header();
	foreach( $bbs->all_topics() AS $id => $topic ) {
		template_index_topic($id, $topic);
	}
	template_index_footer();
	template_footer();
break;

case 'list-json':
	header('Content-Type: application/json');
	echo json_encode(iterator_to_array($bbs->all_topics()));
break;

case 'new':	
	if( strlen( $title = trim(param('title')) ) > 2
	 && strlen( $name = trim(param('name')) ) > 2
	 && strlen( $body = trim(param('body')) ) 
	){
		$post = compact('title','name','body');
		$post['_id'] = $bbs->add_topic($post);
		header('Location: /'.$post['_id']);
		exit;
	}
	template_header();	
	template_new_header();
	template_new_form();
	template_new_footer();
	template_footer();
break;

case 'topic-json':
	$topic_id = param('id');
	$topic = $bbs->find_topic($topic_id);

	if( $topic_id && $topic ) {
		$replies = $bbs->find_replies($topic_id);
		header('Content-Type: application/json');

		echo json_encode(array(
			'topic' => $topic,
			'replies' => iterator_to_array($replies),
		));
	}
break;

case 'topic':
	($topic_id = param('id'))
	or error('No topic passed');

	($topic = $bbs->find_topic($topic_id))
	or error('Cannot find topic');
	
	$replies = $bbs->find_replies($topic_id);

	if( strlen( $name = trim(param('name')) ) > 2
	 && strlen( $body = trim(param('body')))
	){
		$post = compact('name','body');
		$post['_id'] = $bbs->add_reply($topic_id, $post);
		header('Location: /'.$topic_id);
		exit;
	}

	template_header();
	template_topic_header($topic);
	foreach($replies as $id => $post)
		template_topic_post($topic_id, $id, $post);
	template_topic_reply_form($_GET['id']);
	template_topic_footer();
	template_footer();
break;

default:
	redirect(SELF);
break;
}
