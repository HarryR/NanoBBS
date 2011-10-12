<?php
/** This will give you 4 bytes of reasonably random URL-safe data */
function gimme_random($length = 6, $seed = NULL) {
	assert( $length >= 4 );
	$seed === NULL && $seed = microtime(TRUE) . uniqid() . mt_rand();
	$random = hash_hmac('sha256', $seed, LINK_SECRET, TRUE);
	$safe = str_replace(array('+','/','='), array('','',''), base64_encode($random));
	$derp = substr($safe, 0, $length);
	assert( strlen($derp) == $length );
	return $derp;
}

function check_topic_id( $id ) {
	assert( strlen($id) == 6 );
	assert( preg_match( '/^[a-zA-Z0-9]{4}$/', $id ) !== FALSE );
}

// Implements tripcodes
function filter_name( $name ) {
	$name = explode('#', $name, 2);
	$name = trim($name[0]);
	$derp = explode('!', $name, 2);
	if( isset($derp[1]) ) {
		$name = trim($derp[0]) . ' #' . LINK_CODE . ':' .gimme_random(8, trim($derp[1]));
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
		return $this->topics->find( array( 'p' => $topic_id ) );
	}

	/** Adds a post to the topic */
	public function
	add_reply( $to_topic_id, $post ) {
		$this->topics->update(
			array('_id'  => $to_topic_id),
			array('$inc' => array('c' => 1))
		);
		$post['_id'] = gimme_random();
		$post['c'] = 0;
		$post['p'] = $to_topic_id;
		$post['name'] = filter_name($post['name']);
		$post['w'] = time();
		$this->topics->insert( $post );
		return $post;
	}

	public static function
	instance( ) {
		return new bbs();
	}
}
