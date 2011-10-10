<?php
/** This will give you 4 bytes of reasonably random URL-safe data */
function gimme_random($length = 6, $seed = NULL) {
	assert( $length >= 4 );
	$seed === NULL && $seed = microtime(TRUE) . uniqid() . mt_rand();
	$random = hash_hmac('sha256', $seed, 'derp', TRUE);
	$nearly_safe = strtolower(base64_encode($random));
	$safe = str_replace(array('+','/','='), array('','',''), $nearly_safe);
	$derp = substr($safe, 0, $length);
	assert( strlen($derp) == $length );
	return $derp;
}

function check_topic_id( $id ) {
	assert( strlen($id) == 6 );
	assert( preg_match( '/^[a-zA-Z0-9]{4}$/', $id ) !== FALSE );
}

function check_reply_id( $id ) {
	assert( strlen($id) == 11 );
	assert( preg_match( '/^[a-zA-Z0-9]{8}$/', $id ) !== FALSE );	
}

function filter_name( $name ) {
	$name = explode('#', $name, 2);
	$name = $name[0];
	$derp = explode('!', $name, 2);
	if( isset($derp[1]) ) {
		$name = $derp[0] . '#' .gimme_random(8, $derp[1]);
	}
	return $name;
}

class bbs {
	/** MongoCollection */
	protected $posts;

	/** MongoCollection */
	protected $topics;

	public function
	__construct( $uri = 'mongodb://127.0.0.1/nanobbs' ) {
		$m = new Mongo($uri);
		$this->posts = $m->selectCollection('bbs', 'posts');
		$this->topics = $m->selectCollection('bbs', 'topics');
	}

	public function
	all_topics( $count = 50, $tags = NULL ) {
		$query = array();
		if( is_array($tags) ) {
			$query = array('tags' => $tags);
		}
		else if( strlen($tags) ) {
			$query = array('tags' => explode($tags));
		}
		return $this->topics->find()->limit(50);
	}

	/** Find a single post */
	public function
	find_topic( $topic_id ) {
		if( ! $topic_id ) return NULL;
		check_topic_id($topic_id);
		return $this->topics->findOne( array('_id' => $topic_id) );
	}

	/** Find all comments in a thread */
	public function
	find_replies( $topic_id ) {
		if( ! $topic_id ) return NULL;
		check_topic_id($topic_id);
		return $this->posts->find( array( 't' => $topic_id ) );
	}

	public function
	add_topic( $post ) {
		$post['_id'] = gimme_random();
		$post['c'] = 0;
		$post['name'] = filter_name($post['name']);
		$post['w'] = time();
		$x = $this->topics->insert( $post, array('safe' => TRUE) );
		return $post['_id'];
	}

	/** Adds a post to the topic */
	public function
	add_reply( $to_topic_id, $post ) {
		$this->topics->update(
			array('_id'  => $to_topic_id),
			array('$inc' => array('c' => 1))
		);
		$post['_id'] = gimme_random();
		$post['t'] = $to_topic_id;
		$post['name'] = filter_name($post['name']);
		$post['w'] = time();
		$this->posts->insert( $post );
		return $post['_id'];
	}

	public static function
	instance( ) {
		return new bbs();
	}
}
