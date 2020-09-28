<?php

class Unl_View_Helper_HtmlDiff extends Zend_View_Helper_Abstract
{
	static protected $_diffRenderer;
	
	public function htmlDiff($from, $to, $delimiter = " \r\n")
	{
	    /* First we need to do a bit of filtering to be sure that HTML Tags are
	     * treated as atomic elements (and aren't split up or grouped with other text)
	     */ 
	    do {
            $from = preg_replace('/(<[^> ]*) ([^>]*>)/', '$1#TAG_SPACE#$2', $from, -1, $count);
	    } while ($count > 0);
	    do {
            $to =   preg_replace('/(<[^> ]*) ([^>]*>)/', '$1#TAG_SPACE#$2', $to,   -1 , $count);
	    } while ($count > 0);
	    
	    
        $from = strtr($from, array('<' => ' <', '>' => '> '));
        $to =   strtr($to,   array('<' => ' <', '>' => '> '));
	    
        // Now the real work.

        Zend_Loader_Autoloader::getInstance()->registerNamespace('Horde_');
        if (!self::$_diffRenderer) {
            self::$_diffRenderer = new Horde_Text_Diff_Renderer_Inline();
        }

        $currentArray = array();
        for($tok = strtok($from, $delimiter); $tok !== false; $tok = strtok($delimiter)) {
            $currentArray[] = $tok;
        }

        $proposedArray = array();
        for($tok = strtok($to, $delimiter); $tok !== false; $tok = strtok($delimiter)) {
            $proposedArray[] = $tok;
        }

        $diff = new Horde_Text_Diff('auto', array($currentArray, $proposedArray));
        $diffHtml = ($diff->isEmpty() ? $from : self::$_diffRenderer->render($diff));

        // Undo the filtering.
        $diffHtml = strtr($diffHtml, array('#TAG_SPACE#' => ' '));
        
        $tidyConfig = array('show-body-only' => true);
        $diffHtml = tidy_repair_string($diffHtml, $tidyConfig, 'utf8');
        return $diffHtml;
	}
}
