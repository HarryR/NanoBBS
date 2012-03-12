<?php
/** This will give you 4 bytes of reasonably random URL-safe data */
function gimme_random($length = 0, $seed = NULL) {
	if( ! $length ) $length = LINK_SIZE;
	assert( $length >= LINK_SIZE );
	$seed === NULL && $seed = microtime(TRUE) . uniqid() . mt_rand();
	$random = hash_hmac('sha256', $seed, LINK_SECRET, TRUE);
	$safe = str_replace(array('+','/','='), array('','',''), base64_encode($random));
	$derp = substr($safe, 0, $length);
	assert( strlen($derp) == $length );
	return $derp;
}

function check_topic_id( $id ) {
	assert( strlen($id) == LINK_SIZE );
	assert( preg_match( '/^[a-zA-Z0-9]{'.LINK_SIZE.'}$/', $id ) !== FALSE );
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
	/** RiakBucket */
	protected $topics;

	public function
	__construct( ) {
		$this->_riak = new RiakClient();
		$this->topics = $this->_riak->bucket('bbs.topics');
	}

	/** Find a single post */
	public function
	find_topic( $topic_id ) {
		if( ! $topic_id ) return NULL;
		check_topic_id($topic_id);
		$val = apc_fetch($topic_id, $exists);
		if( $exists ) {
			return $val;
		}
		$val = $this->topics->get( $topic_id )->getData();
		if( $val ) {
			apc_store($topic_id,$val);
		}
		return $val;
	}

	/** Find all comments in a thread */
	public function
	find_replies( $topic_id ) {
		if( ! $topic_id ) return NULL;
		check_topic_id($topic_id);
		$ret = apc_fetch($topic_id.'.replies', $exists);
		if( ! $exists ) {
			$topic = $this->topics->get($topic_id);
			$ret = array();
			foreach(  $topic->getLinks() AS $l ) {
				$d = $l->get()->getData();
				$ret[$d['_id']] = $d;
			}
			apc_store($topic_id.'.replies',$ret);
		}
		return $ret;
	}

	/** Adds a post to the topic */
	public function
	add_reply( $to_topic_id, $post ) {				
		$id = gimme_random();
		$post['name'] = filter_name($post['name']);
		$post['_id'] = $id;
		$post['p'] = $to_topic_id;
		$post['w'] = time();

		$o = $this->topics->newObject( $id );		
		$o->setData($post);

		$parent = $this->topics->get($to_topic_id);
		if( $parent->exists() ) {
			$o->addLink($parent);
		}
		$o->store(1);
		apc_store($id, $post);

		if( $to_topic_id && $parent->exists() ) {						
			$parent->addLink( $o );
			$parent->store();
		}
		return $o->getData();
	}

	public static function
	instance( ) {
		return new bbs();
	}
}
