<?php

class Unl_Service_PodcastProducer_Workflow
{
	protected $_name;
	protected $_title;
	protected $_description;
	protected $_uuid;
	protected $_userRequirements = array();

	static public function fromXML($xml)
	{
		$dom = DOMDocument::loadXML($xml);
		$xPath = new DOMXPath($dom);
		if ($xPath->query('/podcast_producer_result/status')->item(0)->textContent != 'success') {
			return;
		}
		$plist = Unl_Plist::plistToArray($xml);
		$workflows = array();
		foreach ($plist['workflows'] as $data) {
			$workflow = new self();
			$workflow->_name = $data['name'];
			$workflow->_title = $data['title'];
			$workflow->_description = $data['description'];
			$workflow->_uuid = $data['uuid'];
			$workflow->_userRequirements = $data['user_requirements'];
			$workflows[] = $workflow;
		}
		return $workflows;
	}
	
	protected function __construct() {}
	
	public function getName()
	{
		return $this->_name;
	}
	
	public function getTitle()
	{
		return $this->_title;
	}
	
	public function getDescription()
	{
		return $this->_description;
	}
	
	public function getUuid()
	{
		return $this->_uuid;
	}
	
	public function getUserRequirements()
	{
		return $this->_userRequirements;
	}
}