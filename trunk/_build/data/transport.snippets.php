<?php
/**
 * @package searchtweets
 * @subpackage build
 */
$snippets = array();

$snippets[1]= $modx->newObject('modSnippet');
$snippets[1]->fromArray(array(
    'id' => 1,
    'name' => 'SearchTweets',
    'description' => 'Displays a list of tweets.',
    'snippet' => getSnippetContent($sources['elements'].'snippets/snippet.searchtweets.php'),
),'',true,true);
$properties = include $sources['data'].'properties/properties.searchtweets.php';
$snippets[1]->setProperties($properties);
unset($properties);

return $snippets;