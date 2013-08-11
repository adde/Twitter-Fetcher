<?php

class Twitter
{
    protected $_user = NULL;
    protected $_count = 3;
    protected $_url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
    protected $_consumer_key = "";
    protected $_consumer_secret = "";
    protected $_oauth_access_token = "";
    protected $_oauth_access_token_secret = "";

    public function __construct($consumer_key, $consumer_secret, $oauth_access_token, $oauth_access_token_secret)
    {
        $this->_consumer_key = $consumer_key;
        $this->_consumer_secret = $consumer_secret;
        $this->_oauth_access_token = $oauth_access_token;
        $this->_oauth_access_token_secret = $oauth_access_token_secret;
    }

    protected function _build_base_string($baseURI, $method, $params) {
        $r = array();
        ksort($params);
        foreach($params as $key=>$value){
            $r[] = "$key=" . rawurlencode($value);
        }
        return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }

    protected function _build_authorization_header($oauth) {
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach($oauth as $key=>$value)
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        $r .= implode(', ', $values);
        return $r;
    }

    public function fetch_tweets($user, $count = 3, $linkify = true)
    {
        $this->_user = $user;
        $this->_count = $count;

        $oauth = array(
            'screen_name' => $this->_user,
            'count' => $this->_count,
            'oauth_consumer_key' => $this->_consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $this->_oauth_access_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );

        $base_info = $this->_build_base_string($this->_url, 'GET', $oauth);
        $composite_key = rawurlencode($this->_consumer_secret) . '&' . rawurlencode($this->_oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;

        $header = array($this->_build_authorization_header($oauth), 'Expect:');
        $options = array( 
            CURLOPT_HTTPHEADER => $header,
            //CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $this->_url . '?screen_name='.$this->_user.'&count='.$this->_count,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        );

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);
        curl_close($feed);

        $twitter_data = json_decode($json);
        if($linkify) {
            return $this->_linkify_json($twitter_data);
        } else {
            return $twitter_data;
        }
    }

    protected function _linkify_json($json)
    {
        foreach($json as $j) {
            $j->text = $this->linkify($j->text);
        }

        return $json;
    }

    public function linkify($text) 
    {
        // linkify URLs 
        $text = preg_replace( 
            '/(https?:\/\/\S+)/', 
            '<a href="\1" target="_blank">\1</a>', 
            $text
        );
        // linkify twitter users 
        $text = preg_replace( 
            '/(^|\s)@(\w+)/', 
            '\1@<a href="https://twitter.com/\2" target="_blank">\2</a>', 
            $text
        ); 
        // linkify tags 
        $text = preg_replace( 
            '/(^|\s)#(\w+)/', 
            '\1#<a href="https://twitter.com/search?q=%23\2" target="_blank">\2</a>', 
            $text
        ); 
        return $text;
    }
}