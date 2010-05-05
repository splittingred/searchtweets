<?php
/*
 *  Snippet: SearchTweets
 *  Grabs Tweets based on comma delimited list of keywords.
 *  
 *  @version 1.0 
 *  @author Jay Gilmore (collabpad.com)
 *  @description 
 * 
 *  @example [[!SearchTweets? &terms=`chrisbrogan` &tpl=`tweetItems`]]
 *
 */


/* setup default properties */
$namespace = $modx->getOption('namespace',$scriptProperties,'stream');
$ttl = $modx->getOption('ttl',$scriptProperties,30);

// Location of the snippet files.
$assetPath = 'assets/snippets/searchtweets/searchtweets.inc.php';
// Cache Key
$cacheKey = $namespace.$modx->resource->id.'searchtweets';

// Retrieves the Cache File
$o = $modx->cacheManager->get($cacheKey);
$o = false;

// If it returns no value or its expired run the script
if(empty($o)) {
    $searchtweets = $modx->getService('SearchTweets','SearchTweets',$modx->getOption('searchtweets.core_path',null,$modx->getOption('core_path').'components/searchtweets/').'model/searchtweets/',$scriptProperties);
    if (!($searchtweets instanceof SearchTweets)) return '';

    $api = $searchtweets->getApi();

    $output = '';

    /* setup default properties */
    $terms = $modx->getOption('terms',$scriptProperties,'');   
    $filter = $modx->getOption('filter',$scriptProperties,'');
    $display = $modx->getOption('display',$scriptProperties,10);
    $noResults = $modx->getOption('noResults',$scriptProperties,'<p>There are currently no results or twitter search may be down. Please try again.</p>');    
    $tpl = $modx->getOption('tpl',$scriptProperties,'sttweetsmall');
    $highlight = $modx->getOption('highlight',$scriptProperties,true);
    $highlightStartTag = $modx->getOption('highlightStartTag',$scriptProperties,'<strong>');
    $highlightEndTag = $modx->getOption('highlightEndTag',$scriptProperties,'</strong>');

    /* Create and Format Search Term String */
    $t = explode(',',$terms);
    $q = implode(' OR ',$t);
    $findinterm = implode('|',$t);

    /* Create and Format Filter String */
    $f = explode(',',$filter);
    $n = implode(' -', $f);
    $nq = ' -' . $n;

    /* Run the Query */
    $query = $q.$nq;
    $r = $api->search($query,'',$display);
    if (!empty($r->results) && is_array($r->results)) {
        $idx = 0;
        foreach($r->results as $k => $tweet) {
            $string = $tweet->text;
            $find_url = "/(http:\/\/|https:\/\/|ftp:\/\/)[^0-9][A-z0-9_]+([.][A-z0-9_]+)*([\/][A-z0-9_]*)*/";
            $make_link = "<a href=\"$0\" target=\"_blank\">$0</a>";
            $linked_txt = preg_replace($find_url, $make_link, $string);

            $find_at = "/(?<![A-z0-9_])[@]([A-z0-9_]+)*/";
            $at_linked ="<a href=\"http://www.twitter.com/$1\">$0</a>";
            $atlink_txt = preg_replace($find_at, $at_linked, $linked_txt);

            if (!empty($highlight)) {
                $find_name = "/(?<![A-z0-9_])($findinterm)(?![A-z0-9_])/i";
                $mk_strong = $highlightStartTag.'$0.'.$highlightEndTag;
                $text = preg_replace($find_name, $mk_strong, $atlink_txt);
            } else {
                $text = $atlink_txt;
            }
            $ago = $searchtweets->getTimeAgo($tweet->created_at);

            $output .= $searchtweets->getChunk($tpl,array(
                'username' => $tweet->from_user,
                'userid' => $tweet->from_user_id,
                'language' => $tweet->iso_language_code,
                'profileImageUrl' => $tweet->profile_image_url,
                'profileImageWidth' => 48,
                'profileImageHeight' => 48,
                'createdon' => $tweet->created_at,
                'text' => $text,
                'id' => $tweet->id,
                'idx' => $idx,
                'ago' => $ago,
            ));
            $idx++;
        }
    } else {
        $output = $noResults;
    }

    $modx->cacheManager->set($ckey, $output, $ttl);
}
return $output;