<?php
/**
 * @package searchtweets
 */
class SearchTweets {
    /**
     * Constructs the SearchTweets object
     *
     * @param modX &$modx A reference to the modX object
     * @param array $config An array of configuration options
     */
    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;

        $basePath = $this->modx->getOption('searchtweets.core_path',$config,$this->modx->getOption('core_path').'components/searchtweets/');
        $assetsUrl = $this->modx->getOption('searchtweets.assets_url',$config,$this->modx->getOption('assets_url').'components/searchtweets/');
        $this->config = array_merge(array(
            'basePath' => $basePath,
            'corePath' => $basePath,
            'modelPath' => $basePath.'model/',
            'processorsPath' => $basePath.'processors/',
            'chunksPath' => $basePath.'elements/chunks/',
            'jsUrl' => $assetsUrl.'js/',
            'cssUrl' => $assetsUrl.'css/',
            'assetsUrl' => $assetsUrl,
        ),$config);

        $this->modx->addPackage('searchtweets',$this->config['modelPath']);
        $this->modx->lexicon->load('searchtweets:default');
    }

    public function getApi() {
        if (!$this->modx->loadClass('searchtweets.twitterapi',$this->config['modelPath'],true,true)) {
            return 'Could not load API class.';
        }
        $this->api= new TwitterApi($this->modx);
        return $this->api;
    }

    /**
     * Gets a Chunk and caches it; also falls back to file-based templates
     * for easier debugging.
     *
     * @access public
     * @param string $name The name of the Chunk
     * @param array $properties The properties for the Chunk
     * @return string The processed content of the Chunk
     */
    public function getChunk($name,$properties = array()) {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
            $chunk = $this->_getTplChunk($name);
            if (empty($chunk)) {
                $chunk = $this->modx->getObject('modChunk',array('name' => $name),true);
                if ($chunk == false) return false;
            }
            $this->chunks[$name] = $chunk->getContent();
        } else {
            $o = $this->chunks[$name];
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setContent($o);
        }
        $chunk->setCacheable(false);
        return $chunk->process($properties);
    }
    /**
     * Returns a modChunk object from a template file.
     *
     * @access private
     * @param string $name The name of the Chunk. Will parse to name.chunk.tpl
     * @return modChunk/boolean Returns the modChunk object if found, otherwise
     * false.
     */
    private function _getTplChunk($name) {
        $chunk = false;
        $f = $this->config['chunksPath'].strtolower($name).'.chunk.tpl';
        if (file_exists($f)) {
            $o = file_get_contents($f);
            $chunk = $this->modx->newObject('modChunk');
            $chunk->set('name',$name);
            $chunk->setContent($o);
        }
        return $chunk;
    }



    /**
     * Gets a properly formatted "time ago" from a specified timestamp
     */
    public function getTimeAgo($time = '') {
        if (empty($time)) return false;

        $agoTS = $this->timesince($time);
        $ago = array();
        if (!empty($agoTS['days'])) {
            $ago[] = $this->modx->lexicon('searchtweets.ago_days',$agoTS);
        }
        if (!empty($agoTS['hours'])) {
            $ago[] = $this->modx->lexicon('searchtweets.ago_hours',$agoTS);
        }
        if (!empty($agoTS['minutes']) && empty($agoTS['days'])) {
            $ago[] = $this->modx->lexicon('searchtweets.ago_minutes',$agoTS);
        }
        if (empty($ago)) { /* handle <1 min */
            $ago[] = $this->modx->lexicon('searchtweets.ago_seconds',$agoTS);
        }
        return implode(', ',$ago).' '.$this->modx->lexicon('searchtweets.ago');
    }

    /**
     * Gets a proper array of time since a timestamp
     */
    public function timesince($input) {
        $output = '';
        $uts['start'] = strtotime($input);
        $uts['end'] = time();
        if( $uts['start']!==-1 && $uts['end']!==-1 ) {
            if( $uts['end'] >= $uts['start'] ) {
                $diff = $uts['end'] - $uts['start'];
                $days = intval((floor($diff/86400)));
                if ($days) $diff = $diff % 86400;
                $hours = intval((floor($diff/3600)));
                if ($hours) $diff = $diff % 3600;
                $minutes = intval((floor($diff/60)));
                if ($minutes) $diff = $diff % 60;

                $diff = intval($diff);
                $output = array(
                    'days' => $days
                    ,'hours' => $hours
                    ,'minutes' => $minutes
                    ,'seconds' => $diff
                );
            }
        }
        return $output;
    }
}