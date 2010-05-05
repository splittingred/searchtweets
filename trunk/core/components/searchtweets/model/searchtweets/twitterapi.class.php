<?php
/**
 * Wrapper class around the Twitter API for PHP
 * Based on the class originally developed by David Billingham
 * and accessible at http://twitter.slawcup.com/twitter.class.phps
 * @author David Billingham <david@slawcup.com>
 * @author Aaron Brazell <aaron@technosailor.com>
 * @author Keith Casey <caseysoftware@gmail.com>
 * @version 1.1-alpha
 * @package php-twitter
 * @subpackage classes
 */
class TwitterApi {
    const OPT_TWITTER_SEARCH_URL = 'twitter_search_url';
    const OPT_USERNAME = 'username';
    const OPT_PASSWORD = 'password';
    const OPT_USER_AGENT = 'user_agent';
    const OPT_TYPE = 'type';

    /**
     * Can be set to JSON (requires PHP 5.2 or the json pecl module) or XML - json|xml
     * @var string
     */
    public $type='json';

    /**
     * It is unclear if Twitter header preferences are standardized, but I would suggest using them.
     * More discussion at http://tinyurl.com/3xtx66
     * @var array
     */
    public $headers=array('X-Twitter-Client: ','X-Twitter-Client-Version: ','X-Twitter-Client-URL: ');

    /**
     * @var array
     */
    public $responseInfo=array();
   
    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;
        $this->config = array_merge(array(
            TwitterApi::OPT_TWITTER_SEARCH_URL => 'http://search.twitter.com/search',
            TwitterApi::OPT_TYPE => 'json',
            TwitterApi::OPT_USER_AGENT => '',
        ),$config);
    }

    /**
     * Can be set to JSON (requires PHP 5.2 or the json pecl module) or Atom - json|atom
     * @var string
     */
    public $stype='json';

    public function search($terms=false, $callback=false, $limit=false) {
        if (empty($terms)) return false;
        $qs = array();
        $request = $this->config[TwitterApi::OPT_TWITTER_SEARCH_URL].'.' . $this->stype;
        $qs[] = 'q=' . urlencode( $terms );
        $lt = '&rpp=' . $limit;
        if ($callback && $this->stype == 'json') {
            $qs[] = 'callback=' . $callback;
        }

        $out = $this->process($request.'?'.implode('&',$qs).$lt);
        return $this->objectify($out);
    }

    /**
     * Send a status update to Twitter.
     * @param string $status total length of the status update must be 140 chars or less.
     * @return string|boolean
     */
    public function update($status) {
        $request = 'http://twitter.com/statuses/update.' . $this->type;
        $postargs = 'status='.urlencode($status);
        $out = $this->process($request,$postargs);
        return $this->objectify($out);
    }
   
    /**
     * Send an unauthenticated request to Twitter for the public timeline.
     * Returns the last 20 updates by default
     * @param boolean|integer $sinceid Returns only public statuses with an ID greater of $sinceid
     * @return string
     */
    public function publicTimeline($sinceid = false) {
        $qs='';
        if ($sinceid !== false) {
            $qs = '?since_id=' . intval($sinceid);
        }
        $request = 'http://twitter.com/statuses/public_timeline.' . $this->type . $qs;
        $out = $this->process($request);
        return $this->objectify($out);
    }
              
    /**
     * Send an authenticated request to Twitter for the timeline of authenticating user.
     * Returns the last 20 updates by default
     * @param boolean|integer $id Specifies the ID or screen name of the user for whom to return the friends_timeline. (set to false if you want to use authenticated user).
     * @param boolean|integer $since Narrows the returned results to just those statuses created after the specified date.
     * @deprecated integer $count. As of July 7 2008, Twitter has requested the limitation of the count keyword. Therefore, we deprecate
     * @return string
     */
    public function userTimeline($id = false,$count = 20,$since = false,$since_id = false,$page = false) {
        $qs = array();
        if ($since !== false) {
            $qs[] = 'since='.urlencode($since);
        }
        if ($since_id) {
            $since_id = (int) $since_id;
            $qs[] = 'since_id=' . $since_id;
        }

        if ($page) {
            $page = (int) $page;
            $qs[] = 'page=' . $page;
        }

        if ($count == 20) {
            $qs[] = 'page='.intval($count);
        } else {
            $qs[] = 'count=' . intval($count);
        }

        $qs = count($qs) > 0 ? '?' . implode('&', $qs) : '';

        if ($id === false) {
            $request = 'http://twitter.com/statuses/user_timeline.' . $this->type . $qs;
        } else {
            $request = 'http://twitter.com/statuses/user_timeline/' . urlencode($id) . '.' . $this->type . $qs;
        }
        $out = $this->process($request);
        return $this->objectify($out);
    }
   
    /**
     * Returns a single status, specified by the id parameter below.  The status's author will be returned inline.
     * @param integer $id The id number of the tweet to be returned.
     * @return string
     */
    public function showStatus($id) {
        $request = 'http://twitter.com/statuses/show/'.intval($id) . '.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }

    /**
     * Retrieves favorited tweets
     * @param integer|string $id Required. The username or ID of the user to be fetched
     * @param integer $page Optional. Tweets are returned in 20 tweet blocks. This int refers to the page/block
     * @return string
     */
    public function getFavorites($id, $page=false) {
        if ($page != false) {
            $qs = '?page=' . $page;
        }
               
        $request = 'http://twitter.com/favorites.' . $this->type . $qs;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Favorites a tweet
     * @param integer $id Required. The ID number of a tweet to be added to the authenticated user favorites
     * @return string
     */
    public function makeFavorite($id) {
        $request = 'http://twitter.com/favorites/create/' . $id . '.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Unfavorites a tweet
     * @param integer $id Required. The ID number of a tweet to be removed to the authenticated user favorites
     * @return string
     */
    public function removeFavorite($id) {
        $request = 'http://twitter.com/favorites/destroy/' . $id . '.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Checks to see if a friendship already exists
     * @param string|integer $user_a Required. The username or ID of a Twitter user
     * @param string|integer $user_b Required. The username or ID of a Twitter user
     * @return string
     */
    public function isFriend($user_a,$user_b) {
        $qs = '?user_a=' . urlencode( $user_a ) . '&amp;' . urlencode( $user_b );
        $request = 'http://twitter.com/friendships/exists.' . $this->type . $qs;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Sends a request to follow a user specified by ID
     * @param integer|string $id The twitter ID or screenname of the user to follow
     * @return string
     */
    public function followUser($id) {
        $request = 'http://twitter.com/friendships/create/' . $id . '.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Unfollows a user
     * @param integer|string $id the username or ID of a person you want to unfollow
     * @return string
     */
    public function unfollowUser( $id ) {
        $request = 'http://twitter.com/friendships/destroy/' . $id . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Blocks a user
     * @param integer|string $id the username or ID of a person you want to block
     * @return string
     */
    public function blockUser($id) {
        $request = 'http://twitter.com/blocks/create/' . $id . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Unblocks a user
     * @param integer|string $id the username or ID of a person you want to unblock
     * @return string
     */
    public function unblockUser() {
        $request = 'http://twitter.com/blocks/destroy/' . $id . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }

    /**
     * Returns the authenticating user's friends, each with current status inline.  It's also possible to request another user's friends list via the id parameter below.
     * @param integer|string $id Optional. The user ID or name of the Twitter user to query.
     * @return string
     */
    public function friends($id = false) {
        if ($id === false) {
            $request = 'http://twitter.com/statuses/friends.' . $this->type;
        } else {
            $request = 'http://twitter.com/statuses/friends/' . urlencode($id) . '.' . $this->type;
        }
        $out = $this->process($request);
        return $this->objectify($out);
    }
   
    /**
     * Returns the authenticating user's followers, each with current status inline.
     * @return string
     */
    public function followers() {
        $request = 'http://twitter.com/statuses/followers.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
   
    /**
     * Returns a list of the users currently featured on the site with their current statuses inline.
     * @return string
     */
    public function featured() {
        $request = 'http://twitter.com/statuses/featured.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
   

    /**
     * Returns extended information of a given user, specified by ID or screen name as per the required
     * id parameter below.  This information includes design settings, so third party developers can theme
     * their widgets according to a given user's preferences.
     * @param integer|string $id Optional. The user ID or name of the Twitter user to query.
     * @return string
     */
    public function showUser($id) {
        $request = 'http://twitter.com/users/show/'.urlencode($id).'.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
   
    /**
     * Returns a list of the direct messages sent to the authenticating user.
     * @param string $since (HTTP-formatted date) Optional.  Narrows the resulting list of direct messages to just those sent after the specified date.
     * @return string
     */
    public function directMessages($since = false) {
        $qs='';
        if ($since !== false) {
            $qs = '?since=' . urlencode($since);
        }
        $request = 'http://twitter.com/direct_messages.' . $this->type .$qs;
        $out = $this->process($request);
        return $this->objectify($out);
    }
   
    /**
     * Sends a new direct message to the specified user from the authenticating user.  Requires both the user
     * and text parameters below.
     * @param string|integer Required. The ID or screen name of the recipient user.
     * @param string $user The text of your direct message.  Be sure to URL encode as necessary, and keep it under 140 characters.
     * @return string
     */
    public function sendDirectMessage($user,$text) {
        $request = 'http://twitter.com/direct_messages/new.' . $this->type;
        $postargs = 'user=' . urlencode($user) . '&text=' . urlencode($text);
        $out = $this->process($request,$postargs);
        return $this->objectify($out);
    }
       
    /**
     * Updates Geo location
     * @param string $location Required. Must be urlencoded. Example (San%20Francisco)
     * @return string
     */
    public function updateLocation($location) {
        $qs = '?location=' . urlencode($location);
        $request = 'http://twitter.com/account/update_location.' . $this->type . $qs;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Updates delivery device
     * @param string $device Required. Must be of type 'im', 'sms' or 'none'
     * @return string
     */
    public function updateDevice($device) {
        if (!in_array($device,array('im','sms','none'))) {
            return false;
        }

        $qs = '?device=' . $device;
        $request = 'http://twitter.com/account/update_delivery_device.' . $this->type . $qs;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Detects if Twitter is up or down. Chances are, it will be down. ;-) Here's a hint - display CPM ads whenever Twitter is down
     * @return boolean
     */
    public function twitterAvailable() {
        $request = 'http://twitter.com/help/test.' . $this->type;
        return $this->objectify($this->process($request)) == 'ok';
    }
       
    /**
     * Any prescheduled maintenance?
     * @return string
     */
    public function maintenanceSchedule() {
        $request = 'http://twitter.com/help/downtime_schedule.' . $this->type;
        return $this->objectify($this->process($request));
    }

    /**
     * Rate Limit API Call. Sometimes Twitter needs to degrade. Use this non-ratelimited API call to work your logic out
     * @return integer|boolean
     */
    public function ratelimit() {
        $request = 'http://twitter.com/account/rate_limit_status.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }

    /**
     * Rate Limit statuses (extended). Provides helper data like remaining-hits, hourly limit, reset time and reset time in seconds
    */
    public function ratelimit_status() {
        $request = 'http://twitter.com/account/rate_limit_status.' . $this->type;
        $out = $this->process($request);
        return $this->objectify($out);
    }
       
    /**
     * Uses the http://is.gd API to produce a shortened URL. Pluggable by extending the twitter class
     * @param string $url The URL needing to be shortened
     * @return string
     */
    public function shorturl($url) {
        // Using is.gd because it's good
        $request = 'http://is.gd/api.php?longurl=' . $url;
        return $this->process($request);
    }
              
    /**
     * Internal function where all the juicy curl fun takes place
     * this should not be called by anything external unless you are
     * doing something else completely then knock youself out.
     * @access private
     * @param string $url Required. API URL to request
     * @param string $postargs Optional. Urlencoded query string to append to the $url
     */
    private function process($url,$postargs=false) {
        if (!function_exists('curl_init')) return false;

        $ch = curl_init($url);
        if (!empty($postargs)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postargs);
        }
        if (!empty($this->config[TwitterApi::OPT_USERNAME])
         && !empty($this->config[TwitterApi::OPT_PASSWORD])) {
            curl_setopt($ch, CURLOPT_USERPWD,$this->config[TwitterApi::OPT_USERNAME].':'.$this->config[TwitterApi::OPT_PASSWORD]);
        }

        curl_setopt($ch, CURLOPT_VERBOSE,1);
        curl_setopt($ch, CURLOPT_NOBODY,0);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_USERAGENT,$this->config[TwitterApi::OPT_USER_AGENT]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $response = curl_exec($ch);

        $this->responseInfo=curl_getinfo($ch);
        curl_close($ch);

        if (intval($this->responseInfo['http_code']) == 200) {
            return $response;
        } else {
            return false;
        }
    }

    /**
     * Function to prepare data for return to client
     * @access private
     * @param string $data
     */
    private function objectify($data) {
        if ($this->type == 'json') {
            return (object) json_decode($data);
        } elseif ($this->type == 'xml') {
            if (function_exists('simplexml_load_string')) {
                $obj = simplexml_load_string($data);

                $statuses = array();
                foreach ($obj->status as $status) {
                        $statuses[] = $status;
                }
                return (object) $statuses;
            } else {
                return $out;
            }
        } else {
            return false;
        }
    }
}