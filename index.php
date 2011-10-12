<?php	
get_magic_quotes_gpc() && die('Disable magic_quotes_gpc in php.ini');
date_default_timezone_set('UTC');

###############################################################################
# Configuration
# - Edit the following fields

// Translatable fields

define('LINK_CODE', 'p6n6');
define('LINK_SECRET', 'derp');
define('TOPIC_LIMIT', 25);

define('SITE_TITLE', 'Nar Nine Kay');
define('TOPIC_TITLE', 'Topic');
define('DELETE_TITLE', 'Delete');
define('NEW_TOPIC_TITLE', 'New Topic');
define('ADD_TITLE', 'Add');
define('REPLY_TITLE', 'Reply');
define('REPLY_NAME_FIELD', 'Name');
define('REPLY_TITLE_FIELD','Title');
define('REPLY_CONTENT_FIELD', 'Body');

// URLs
define('BASE_URL', 'http://localhost:8888/');
define('CSS_URL', 'style.css');

// Anti Spam
define('TIME_BETWEEN_TOPICS', 120);
define('TIME_BETWEEN_POSTS', 30);

# End of Configuration
###############################################################################

function param($name, $default = NULL) {
	if (!isset($_REQUEST[$name])) {
		return $default;
	}
	return $_REQUEST[$name];
}

try {
	require_once('model.php');
	require_once('view.php');
	$bbs = bbs::instance();
}
catch( Excepiton $ex ) {
	error($ex->getMessage());
}

($ice = param('ice')) || error('Cannot Break Ice');	
($pak = param('pak')) || error('What Do You Want');

narwhal($bbs,$ice,$pak);

function gimme_link( $topic ) {
	return gimme_random(6,$topic['p'].$topic['_id']);
}

function censor($topic) {
	unset($topic['p']);
	unset($topic['name']);
	return $topic;
}

function can_add_to( $topic ) {
	return (!isset($topic['c']) || $topic['c'] < TOPIC_LIMIT);
}

function narwhal( $bbs, $ice, $pak ) {
	assert( strlen($ice) == 12 );

	$entry_id = substr($ice, 0, 6);	
	$topic_id = substr($ice, 6, 6);	

	($topic = $bbs->find_topic($topic_id)) || error('Cannot Find Topic');

	$calced_entry_id = gimme_link($topic); 

	( $calced_entry_id != $entry_id) && error('Cannot Find Topic');
	$replies = $bbs->find_replies($topic_id);

	switch( $pak ) {
	case 'bson':
		header('Content-Type: application/bson');
		echo bson_encode(array(
			'topic' => censor($topic),
			'replies' => iterator_to_array($replies),
		));

	case 'json':
		header('Content-Type: application/json');
		echo json_encode(array(
			'topic' => censor($topic),
			'replies' => iterator_to_array($replies),
		));
	break;

	case 'html':
		if( can_add_to($topic['c'])
		 && strlen( $title = trim(param('title'))) >= 0
		 && strlen( $name = trim(param('name'))) >= 0
		 && strlen( $body = trim(param('body')))
		){
			if( strlen($title) < 100
			 && strlen($name) < 50
			 && strlen($body) < 2048
			){
				if( empty($name) ) {
					$name = 'Anonymous!'.LINK_CODE.LINK_SECRET;
				}
				$post = compact('title','name','body');
				$post = $bbs->add_reply($topic_id, $post);
			}
		}

		template_header();
		template_topic_detail($topic, $replies);
		template_footer();
	break;	

	default:	
		error('Try Harder');
	break;
	}
}

