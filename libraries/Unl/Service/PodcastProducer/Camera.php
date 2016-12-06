<?php

class Unl_Service_PodcastProducer_Camera
{
    protected $_name;
    protected $_uuid;
    protected $_previewUrl;

    static public function fromXML($xml)
    {
        $dom = DOMDocument::loadXML($xml);
        $xPath = new DOMXPath($dom);
        if ($xPath->query('/podcast_producer_result/status')->item(0)->textContent != 'success') {
            return;
        }
        $plist = Unl_Plist::plistToArray($xml);
        $cameras = array();
        foreach ($plist['cameras'] as $data) {
            $camera = new self();
            $camera->_name = $data['name'];
            $camera->_uuid = $data['uuid'];
            $camera->_previewUrl = $data['preview_url'];
            $cameras[] = $camera;
        }
        return $cameras;
    }
    
    protected function __construct() {}
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function getUuid()
    {
        return $this->_uuid;
    }
    
    public function getPreviewUrl()
    {
        return $this->_previewUrl;
    }
}