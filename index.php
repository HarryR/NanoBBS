<?php	
include 'model.php';
include 'riak.php';
include 'view.php';

get_magic_quotes_gpc() && die('Disable magic_quotes_gpc in php.ini');
date_default_timezone_set('UTC');

header("HTTP/1.0 404 Not Found");
ob_start("ob_gzhandler");

###############################################################################
# Configuration
# - Edit the following fields

// Translatable fields

define('LINK_CODE', 'p6n6');
define('LINK_SECRET', 'derp');
define('TOPIC_LIMIT', 9);

define('SITE_TITLE', 'Nar Nine Kay');
define('TOPIC_TITLE', 'Topic');
define('DELETE_TITLE', 'Delete');
define('NEW_TOPIC_TITLE', 'New Topic');
define('ADD_TITLE', 'Add');
define('REPLY_TITLE', 'Reply');
define('REPLY_NAME_FIELD', 'Name');
define('REPLY_TITLE_FIELD','Title');
define('REPLY_ICON_FIELD','Icon');
define('REPLY_CONTENT_FIELD', 'Body');
define('REPLY_RULES', '(square JPEG image, max 32px, 32 KiB)');

// URLs
define('CSS_URL', 'style.css');

# End of Configuration
###############################################################################

function param($name, $default = NULL) {
	if (!isset($_REQUEST[$name])) {
		return $default;
	}
	return $_REQUEST[$name];
}

try {	
	$bbs = bbs::instance();
}
catch( Excepiton $ex ) {
	error($ex->getMessage());
}

if( param('ice') == NULL && param('bootstrap') == LINK_SECRET ) {
	$tmp = gimme_random();
	$post = $bbs->add_reply($tmp, array(
		'title' => 'Welcome',
		'name' => 'Bootstrap!'.LINK_CODE.LINK_SECRET,
		'body' => 'Use this bootstrap post for your own convenience :)',
	));
	$_REQUEST['ice'] = gimme_link($post) . $post['_id'];
	$_REQUEST['pak'] = 'html';
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
	return TRUE;
}

function data_uri($contents, $mime) {
    $base64 = base64_encode($contents);
    return "data:$mime;base64,$base64";
}

function handle_upload( $name ) {
	if( ! isset($_FILES[$name]) ) return NULL;
	$f = $_FILES[$name];
	if( $f['error'] !== UPLOAD_ERR_OK ) return NULL;
	if( $f['type'] != 'image/jpeg' ) return NULL;
	if( filesize($f['tmp_name']) > (1024 * 32) ) return NULL;
	
	$info = getimagesize($f['tmp_name']);
	if( ! $info ) return NULL;
	if( $info[2] != IMAGETYPE_JPEG ) return NULL;
	if( $info[1] != $info[0] ) return NULL;
	if( $info[0] < 16 || $info[0] > 32 ) return NULL;

	$readable_metadata = format_metadata($f['tmp_name']);	

	$x = file_get_contents( $f['tmp_name'] );
	unlink($f['tmp_name']);
	return array($readable_metadata,data_uri($x, $f['type']));
}

function format_metadata( $filename ) {
	$exif = exif_read_data($filename, 0, true);
	if( !$exif ) return NULL;
	
	$readable = '';
	foreach ($exif as $key => $section) {
	    foreach ($section as $name => $val) {
	    	if( $key == 'FILE' && $name == 'FileName' ) continue;
	    	if( strlen($val) > 500 ) continue;
	    	$readable .= sprintf("%s.%s: %s\n", $key, $name, $val);
	    }
	    $readable .= "\n";
	}

	return $readable;
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
	case 'json':
		header('Content-Type: application/json');
		echo json_encode(array(
			'topic' => censor($topic),
			'replies' => iterator_to_array($replies),
		));		
	break;

	case 'html':
		if( count($replies) < TOPIC_LIMIT
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

				// Only allow icon upload with title
				if( !empty($title) ) {
					list($metadata, $icon) = handle_upload('icon');
					$post['x'] = $icon;
					if( !empty($metadata) ) {
						$post['body'] .= "\n\n" . $metadata;
					}
				}				
				$post = $bbs->add_reply($topic_id, $post);
				$replies[] = $post;
				apc_delete($topic_id.'.replies');
				header('Location: /' . gimme_link($post) . $post['_id'] . '.html');
				exit;
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

