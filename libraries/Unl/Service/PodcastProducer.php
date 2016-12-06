<?php

class Unl_Service_PodcastProducer extends Zend_Service_Abstract
{
	/**
	 * Base URI for the podcast producer server
	 *
	 * @var Zend_Uri_Http
	 */
	protected $_uri;
	
	protected $_curl;
	
	protected $_username;
	
	protected $_password;
	
	public function __construct($hostname, $port = 8170)
	{
		$this->_uri = Zend_Uri::factory('https');
		$this->_uri->setHost($hostname);
		$this->_uri->setPort($port);
		
		$this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, false);
	}
	
	public function authenticate($username, $password)
	{
		$this->_username = $username;
		$this->_password = $password;
		
		curl_setopt($this->_curl, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($this->_curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
	}

    public function getWorkflows()
    {
        $uri = clone $this->_uri;
        $uri->setPath('/podcastproducer/workflows');
        
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        $response = curl_exec($this->_curl);
        if ($response === false) {
            echo curl_error($this->_curl);
        }
        
        return Unl_Service_PodcastProducer_Workflow::fromXML($response);
    }
    
    public function getWorkflow($uuid)
    {
    	$selectedWorkflow = null;
    	foreach ($this->getWorkflows() as $workflow) {
    		if ($workflow->getUuid() == $uuid) {
    			$selectedWorkflow = $workflow;
    		}
    	}
    	return $selectedWorkflow;
    }
    
    public function getCameras()
    {
        $uri = clone $this->_uri;
        $uri->setPath('/podcastproducer/cameras');
        
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        $response = curl_exec($this->_curl);
        if ($response === false) {
            echo curl_error($this->_curl);
        }
        
        return Unl_Service_PodcastProducer_Camera::fromXML($response);
    }
    
    public function getCamera($uuid)
    {
    	$selectedCamera = null;
    	foreach ($this->getCameras() as $camera) {
    		if ($camera->getUuid() == $uuid) {
    			$selectedCamera = $camera;
    		}
    	}
    	return $selectedCamera;
    }
    
    public function getCameraPreview(Unl_Service_PodcastProducer_Camera $camera)
    {
        curl_setopt($this->_curl, CURLOPT_URL, $camera->getPreviewUrl());
        $response = curl_exec($this->_curl);
        return $response;
    }
    
    public function createForFile(Unl_Service_PodcastProducer_Workflow $workflow, $filePath, $metadata = array())
    {	
    	//$fileContents = file_get_contents($filePath);
    	//$fileSize = strlen($fileContents);
    	$fileSize = 0;
    	$fileName = basename($filePath);
    	
        $uri = clone $this->_uri;
        $uri->setPath('/podcastproducer/submissions/create_for_file');
        
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, 'workflow_name='. urlencode($workflow->getName()) . '&submission_type=file');
        $response = curl_exec($this->_curl);
        if ($response === false) {
            echo curl_error($this->_curl);
        }
        
        //header('Content-type: text/xml');
        echo $response . "\n\n\n";
        
        $r = DOMDocument::loadXML($response);
        $xPath = new DOMXPath($r);
        $recordingUUID = $xPath->query('recording_uuid')->item(0)->textContent;
        $uploadUri = $xPath->query('https_upload_url')->item(0)->textContent;
        $UUID = $xPath->query('uuid')->item(0)->textContent;
        
        $returnVal = null;
        $response = null;
        $cmd = 'wget '
             . '-O - '
             . '--no-check-certificate '
             . '--user=' . $this->_username . ' '
             . '--password=' . $this->_password . ' '
             . "--header 'File-name: $fileName' "
             . "--header 'Recording-UUID: $recordingUUID' " 
             . "--post-file '$filePath' "
             . $uploadUri;
        exec($cmd, $response, $returnVal);
        $response = implode("\n", $response);
        
        /*
        curl_setopt($this->_curl, CURLOPT_URL, $uploadUri);
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array(
            'File-name: ' . $fileName,
            'Recording-UUID: ' . $recordingUUID
        ));
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $fileContents);
        $response = curl_exec($this->_curl);
        */
        
        echo $response . "\n\n\n";
        
        $plist = Unl_Plist::arrayToPlist($metadata);
        $plistLength = strlen($plist);
        
        curl_setopt($this->_curl, CURLOPT_URL, $uploadUri);
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array(
            'File-name: user_metadata.plist',
            'Recording-UUID: ' . $recordingUUID
        ));
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $plist);
        $response = curl_exec($this->_curl);
        
        echo $response . "\n\n\n";
        
        $uri->setPath('/podcastproducer/submissions/complete');
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array());
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, 
              'submission_uuid=' . $UUID
            . '&submitted_files=user_metadata.plist:' . $plistLength . ';' . $fileName . ':' . $fileSize
            . '&primary_content_file=' . $fileName
            . '&recording_uuid=' . $recordingUUID
        );
        $response = curl_exec($this->_curl);
        
        echo $response;
    }
    
    public function startCamera(Unl_Service_PodcastProducer_Camera $camera, $delay = 3)
    {
        $uri = clone $this->_uri;
        $uri->setPath('/podcastproducer/cameras/start');
        
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array());
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, 'delay='. intval($delay) . '&camera_name=' . urlencode($camera->getName()));
        $response = curl_exec($this->_curl);
        if ($response === false) {
            echo curl_error($this->_curl);
        }
    }
    
    public function pauseCamera(Unl_Service_PodcastProducer_Camera $camera)
    {
        $uri = clone $this->_uri;
        $uri->setPath('/podcastproducer/cameras/pause');
        
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array());
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, 'camera_name=' . urlencode($camera->getName()));
        $response = curl_exec($this->_curl);
        if ($response === false) {
            echo curl_error($this->_curl);
        }
    	
    }
    
    public function stopCamera(Unl_Service_PodcastProducer_Camera $camera, Unl_Service_PodcastProducer_Workflow $workflow, $metadata = array())
    {
        $uri = clone $this->_uri;
        $uri->setPath('/podcastproducer/cameras/stop');
        
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array());
        curl_setopt($this->_curl, CURLOPT_POST, true);
        $postData = 'workflow_name='. urlencode($workflow->getName())
                  . '&camera_name=' . urlencode($camera->getName());
        foreach ($metadata as $key => $value) {
        	$postData .= '&UserMetadata_' . urlencode($key) . '=' . urlencode($value);
        }
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($this->_curl);
        if ($response === false) {
            echo curl_error($this->_curl);
        }
    }

    public function bindCamera($name, $sharedSecret = '')
    {
        if (!$sharedSecret) {
            $sharedSecret = hash('md5', microtime(true) . rand(0, getrandmax()), true);
        }
        
        $uri = clone $this->_uri;
        $uri->setPath('/podcastproducer/cameras/bind');
        
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array());
        curl_setopt($this->_curl, CURLOPT_POST, true);
        $postData = 'camera_name='. urlencode($name)
                  . '&shared_secret=' . urlencode(base64_encode($sharedSecret));
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($this->_curl);
        if ($response === false) {
            echo curl_error($this->_curl);
        }
        
        $agent = Unl_Service_PodcastProducer_Agent::fromXML($this, $response);
        $agent->setSharedSecret($sharedSecret);
        $agent->connect();
        
        return $agent;
    }

    public function unbindCamera($name)
    {   
        $uri = clone $this->_uri;
        $uri->setPath('/podcastproducer/cameras/unbind');
        
        curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array());
        curl_setopt($this->_curl, CURLOPT_POST, true);
        $postData = 'camera_name='. urlencode($name);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($this->_curl);
        if ($response === false) {
            echo curl_error($this->_curl);
        }
    }
}
