<?php

class Unl_Plist
{
	static public function plistToArray($xml)
	{
		$dom = DOMDocument::loadXML($xml);
		$plist = $dom->documentElement;
		if ($plist->nodeName == 'plist') {
			$root = $plist->childNodes->item(0);
		} else {
            $xPath = new DOMXPath($dom);
            $root = $xPath->query('plist/*')->item(0);
		}
		
		return self::_parseNode($root);
	}
	
	static public function arrayToPlist($array)
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElement('plist');
		$root->setAttribute('version', '1.0');
		$dom->appendChild($root);
		
		self::_generateNode($dom, $root, $array);
		return $dom->saveXML();
	}
	
	static protected function _parseNode($node)
	{
		switch ($node->nodeName) {
			case 'integer':
				return self::_parseInt($node);
				break;
			case 'string':
			case 'key':
				return self::_parseString($node);
				break;
			case 'date':
				return self::_parseDate($node);
				break;
			case 'true':
			case 'false':
				return self::_parseBool($node);
				break;
			case 'dict':
				return self::_parseDict($node);
				break;
			case 'array':
				return self::_parseArray($node);
				break;
			default:
				return null;
		}
	}

    static protected function _parseInt($node)
    {
        return intval($node->textContent);
    }
    static protected function _parseString($node)
    {
        return $node->textContent;
    }
    static protected function _parseDate($node)
    {
        
    }
    static protected function _parseBool($node)
    {
        return (bool) ($node->nodeName == 'true');
    }
    static protected function _parseDict($node)
    {
    	$dict = array();
    	$childNodes = array();
    	foreach ($node->childNodes as $childNode) {
    		if ($childNode->nodeName == '#text') {
    			continue;
    		}
    		$childNodes[] = $childNode;
    	}
        for ($i = 0; $childNodes[$i]; $i += 2) {
        	$key = self::_parseNode($childNodes[$i]);
        	$value = self::_parseNode($childNodes[$i+1]);
            $dict[$key] = $value;
        }
        return $dict;
    }
    static protected function _parseArray($node)
    {
        $array = array();
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeName == '#text') {
                continue;
            }
            $array[] = self::_parseNode($childNode);
        }
        return $array;
    }
    
    
    
    
    static protected function _generateNode(DOMDocument $dom, DOMNode $parentNode, $node)
    {
    	if (is_array($node)) {
    		$type = 'array';
    		$keys = array_keys($node);
    		for($i = 0; $i < count($keys); $i++) {
                if ($i !== $keys[$i]) {
                    $type = 'dict';	
                }
    		}
    		if ($type == 'array') {
    			self::_generateArray($dom, $parentNode, $node);
    		} else {
    			self::_generateDict($dom, $parentNode, $node);
    		}
    	} else if (is_string($node)) {
    		self::_generateString($dom, $parentNode, $node);
    	} else if (is_int($node)) {
    		self::_generateInt($dom, $parentNode, $node);
    	} else if (is_bool($node)) {
    		self::_generateBool($dom, $parentNode, $node);
    	}
    }
    
    static protected function _generateArray(DOMDocument $dom, DOMNode $parentNode, $node)
    {
    	$array = $dom->createElement('array');
    	$parentNode->appendChild($array);
    	foreach ($node as $aNode) {
    		self::_generateNode($dom, $array, $aNode);
    	}
    }
    
    static protected function _generateDict(DOMDocument $dom, DOMNode $parentNode, $node)
    {
        $dict = $dom->createElement('dict');
        $parentNode->appendChild($dict);
        foreach ($node as $key => $aNode) {
        	$dict->appendChild($dom->createElement('key', $key));
            self::_generateNode($dom, $dict, $aNode);
        }
    }

    static protected function _generateInt(DOMDocument $dom, DOMNode $parentNode, $node)
    {
        $parentNode->appendChild($dom->createElement('integer', $node));
    }
    
    static protected function _generateString(DOMDocument $dom, DOMNode $parentNode, $node)
    {
        $parentNode->appendChild($dom->createElement('string', $node));
    }
    
    static protected function _generateBool(DOMDocument $dom, DOMNode $parentNode, $node)
    {
    	if ($node) {
            $parentNode->appendChild($dom->createElement('true'));
    	} else {
    		$parentNode->appendChild($dom->createElement('false'));
    	}
    }
}
