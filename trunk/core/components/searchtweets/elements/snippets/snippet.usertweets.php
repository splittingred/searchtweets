<?php
/**
 * Snippet: UserTweets
 *
 * Displays a user's tweets.
 *
 * @author Jay Gilmore
 * @author Shaun McCormick
 *
 * @example [[!UserTweets? &id=`username`]]
 *
 */

/* setup default properties */
$namespace = $modx->getOption('namespace',$scriptProperties,'stream');
$ttl = $modx->getOption('ttl',$scriptProperties,30);
$cacheKey = $namespace.$modx->resource->id.'searchtweets';

/* retrieves cache file of tweets to prevent overusing api */
$output = $modx->cacheManager->get($cacheKey);
if (!empty($output)) return $output;

/* run logic */
$searchtweets = $modx->getService('SearchTweets','SearchTweets',$modx->getOption('searchtweets.core_path',null,$modx->getOption('core_path').'components/searchtweets/').'model/searchtweets/',$scriptProperties);
if (!($searchtweets instanceof SearchTweets)) return '';
$api = $searchtweets->getApi();

$output = '';

/* setup default properties */
$id = $modx->getOption('id',$scriptProperties,'');
$display = $modx->getOption('display',$scriptProperties,10);
$noResults = $modx->getOption('noResults',$scriptProperties,'<p>There are currently no results or twitter search may be down. Please try again.</p>');
$tpl = $modx->getOption('tpl',$scriptProperties,'sttweetsmall');
$highlight = $modx->getOption('highlight',$scriptProperties,true);
$highlightStartTag = $modx->getOption('highlightStartTag',$scriptProperties,'<strong>');
$highlightEndTag = $modx->getOption('highlightEndTag',$scriptProperties,'</strong>');

$r = $api->userTimeline($id,$display);
if (!empty($r) && is_array($r)) {
    $idx = 0;
    foreach($r as $k => $tweet) {
        $string = $tweet['text'];
        $find_url = "/(http:\/\/|https:\/\/|ftp:\/\/)[^0-9][A-z0-9_]+([.][A-z0-9_]+)*([\/][A-z0-9_]*)*/";
        $make_link = "<a href=\"$0\" target=\"_blank\">$0</a>";
        $linked_txt = preg_replace($find_url, $make_link, $string);
        $find_at = "/(?<![A-z0-9_])[@]([A-z0-9_]+)*/";
        $at_linked ="<a href=\"http://www.twitter.com/$1\">$0</a>";
        $atlink_txt = preg_replace($find_at, $at_linked, $linked_txt);
        $text = $atlink_txt;

        $ago = $searchtweets->getTimeAgo($tweet['created_at']);

        $tweetArray = array_merge($tweet,array(
            'username' => $tweet['user']['screen_name'],
            'userid' => $tweet['user_id'],
            'profileImageUrl' => $tweet['user']['profile_image_url'],
            'profileImageWidth' => 48,
            'profileImageHeight' => 48,
            'createdon' => $tweet['created_at'],
            'text' => $text,
            'id' => $tweet['id'],
            'idx' => $idx,
            'ago' => $ago,
            'source' => $tweet['source'],
        ));
        $output .= $searchtweets->getChunk($tpl,$tweetArray);
        $idx++;
    }
} else {
    $output = $noResults;
}

$modx->cacheManager->set($cacheKey, $output, $ttl);
return $output;