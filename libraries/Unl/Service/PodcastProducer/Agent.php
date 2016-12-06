<?php

class Unl_Service_PodcastProducer_Agent
{
	protected $_serverUuid;
	protected $_tunnelHost;
	protected $_tunnelPort;
    protected $_protocol;
    protected $_sharedSecret;
    protected $_camera;
    protected $_socket;
    protected $_mcrypt;
    protected $_recordStatus;
    protected $_startTime;
    protected $_endTime;
    
    static public function fromXML(Unl_Service_PodcastProducer $pcast, $xml)
    {
        $dom = DOMDocument::loadXML($xml);
        $xPath = new DOMXPath($dom);
        if ($xPath->query('/podcast_producer_result/status')->item(0)->textContent != 'success') {
            return;
        }
        
        $agent = new self();
        $agent->_serverUuid = $xPath->query('/podcast_producer_result/server_uuid')->item(0)->textContent;
        $agent->_tunnelHost = $xPath->query('/podcast_producer_result/tunnel_host')->item(0)->textContent;
        $agent->_tunnelPort = $xPath->query('/podcast_producer_result/tunnel_port')->item(0)->textContent;
        $agent->_camera = $pcast->getCamera($xPath->query('/podcast_producer_result/camera_uuid')->item(0)->textContent);
        
        return $agent;
    }
    
    protected function __construct()
    {	
        $this->_mcrypt = mcrypt_module_open('rijndael-128', '', 'cbc', '');
        $this->_recordStatus = self::RECORD_STATUS_READY;
        $this->_startTime = 0;
    }
    
    public function setSharedSecret($sharedSecret)
    {
    	$this->_sharedSecret = $sharedSecret;
    }
    
    public function connect()
    {
    	$iv = hash('md5', microtime(true) . rand(0, getrandmax()), true);
    	mcrypt_generic_init($this->_mcrypt, $this->_sharedSecret, $iv);	
    	$errno = 0;
    	$errstr = '';
    	$this->_socket = fsockopen('tcp://' . $this->_tunnelHost, $this->_tunnelPort, $errno, $errstr);
    	$message = 'AGENT protocol=1,agent_uuid=' . $this->_camera->getUuid() . ',iv=' . base64_encode($iv) . ',version=75';
    	echo $message . "\n";
    	fwrite($this->_socket, $message . "\n");
    	$pid = pcntl_fork();
    	if ($pid == 0) {
    		$this->_daemon();
    	}
    }
    
    protected function _getMessage()
    {
    	$message = '';
    	while (!$message) {
            $message = fgets($this->_socket);
    	}
    	
		$message = mdecrypt_generic($this->_mcrypt, base64_decode($message));
		/*
		for ($i = 0; $i < strlen($message); $i++) {
			if ($i == 32) {
				echo "\n";
			}
			if ($i % 4 == 0) {
				echo "\n";
			}
			printf("%02x ", ord($message[$i]));
		}
		echo "\n";
        */
		$message = substr($message, 32);
		$message = strtr($message, array(chr(0x05) => '', chr(0x0a) => '', chr(0x0b) => '', chr(0x0c) => ''));
		return $message;
    }
    
    protected function _serializeParameters($parameters)
    {
    	$serial = array();
    	foreach ($parameters as $key => $value)
    	{
    		$serial[] = $key . '=' . $value;
    	}
    	$serial = implode(',', $serial);
    	return $serial;
    }
    
    protected function _sendMessage($message)
    {
    	echo substr($message, 0, 100) . "\n";
        $nonce = hash('sha256', microtime(true) . rand(0, getrandmax()), true);
        echo 'Nonce length: ' . strlen($nonce) . "\n";
        $message = $nonce . $message;
        echo 'Message length: ' . strlen($message) . "\n";
        while (strlen($message) % 16 != 0) {
        	$message .= chr(0x05);
        }
        echo 'Message length: ' . strlen($message) . "\n";
        $message = mcrypt_generic($this->_mcrypt, $message);
        $message = base64_encode($message);
        fwrite($this->_socket, $message . "\n");
    }
    
    protected function _daemon()
    {
        while(true) {
            $message = $this->_getMessage();
            
            $cmdEnd = strpos($message, ' ');
            if ($cmdEnd === FALSE) {
            	$command = $message;
            	$params = array();
            } else {
                $command = substr($message, 0, $cmdEnd);
	            $params = substr($message, $cmdEnd + 1);
	            $params = explode(',', $params);
            }
            $parameters = array();
            foreach ($params as $param) {
            	$keyEnd = strpos($param, '=');
            	$key = substr($param, 0, $keyEnd);
            	$value = substr($param, $keyEnd + 1);
            	$parameters[$key] = trim($value);
            }
            
            echo $command . "\n";
            print_r($parameters);
            $this->_doCommand($command, $parameters);
        }
    }
    
    protected function _doCommand($command, $parameters = array())
    {
    	switch ($command) {
    		case 'SERVER':
    			$this->_doServer($parameters);
    			break;
    		case 'STATUS':
    			$this->_doStatus($parameters);
    			break;
    		case 'START':
    			$this->_doStart($parameters);
    			break;
    		case 'PAUSE':
    			$this->_doPause($parameters);
    			break;
    		case 'STOP':
    			$this->_doStop($parameters);
    			break;
    		case 'CANCEL':
    			$this->_doCancel($parameters);
    			break;
    		default:
    			break;
    	}
    }
    
    protected function _doServer($parameters)
    {
    	
    }
    
    const RECORD_STATUS_READY     = 0;
    const RECORD_STATUS_PAUSED    = 1;
    const RECORD_STATUS_RECORDING = 2;
    
    protected function _doStatus($parameters)
    {
    	var_dump($parameters);
    	$status = array(
            'cmdStatus' =>     0,
    	    'recordStatus' =>  $this->_recordStatus,
    	    'start' =>         $this->_getStartTime(),
    	    'elapsedTime' =>   $this->_getElapsedTime(),
    	    'stop' =>          $this->_getEndTime(),
    	    'hard_stop' =>     0,
    	    'lastError' =>     0,
    	    'availDiskInMB' => '-26815622355363595601659544849062032869829237996999576376827051738916941540340353331752992578407331193284881852690388450712033975077640599757568336933683200',
    	    'totalDiskInMB' => 0,
    	);
    	if (true || $parameters['include_preview'] == 'true') {
	        $preview = file_get_contents('./preview.jpg');
	        $preview = base64_encode($preview);
	        $status['preview'] = $preview;
    	}
    	$status = $this->_serializeParameters($status);
    	$this->_sendMessage($status);
    }
    
    protected function _getStartTime()
    {
    	switch ($this->_recordStatus) {
    		case self::RECORD_STATUS_READY:
    		default:
                return time();
                break;
                
    		case self::RECORD_STATUS_PAUSED:
    		case self::RECORD_STATUS_RECORDING:
    			return $this->_startTime;
    			break;
    	}
    }
    
    protected function _getEndTime()
    {
    	switch ($this->_recordStatus) {
    		case self::RECORD_STATUS_READY:
    		default:
    			return time()+1;
    			break;
    			
    		case self::RECORD_STATUS_PAUSED:
    			return $this->_endTime;
    			break;
    			
            case self::RECORD_STATUS_RECORDING:
            	return 0;
            	break;
    	}
    }
    
    protected function _getElapsedTime()
    {
    	switch ($this->_recordStatus) {
    		case self::RECORD_STATUS_READY:
    		default: 
    			return 1;
    			break;

            case self::RECORD_STATUS_PAUSED:
                return $this->_getEndTime() - $this->_getStartTime();
                break;

            case self::RECORD_STATUS_RECORDING:
                return time() - $this->_getStartTime();
                break;
    	}
    }
    
    protected function _doStart($parameters)
    {
    	$this->_recordStatus = self::RECORD_STATUS_RECORDING;
    	$this->_startTime = time();
    	$status = array(
            'cmdStatus' => 0
    	);
    	$status = $this->_serializeParameters($status);
    	$this->_sendMessage($status);
    }
    
    protected function _doPause($parameters)
    {
    	$this->_recordStatus = self::RECORD_STATUS_PAUSED;
    	$this->_endTime = time();
        $status = array(
            'cmdStatus' => 0
        );
        $status = $this->_serializeParameters($status);
        $this->_sendMessage($status);
    }
    
    protected function _doStop($parameters)
    {
    	$this->_recordStatus = self::RECORD_STATUS_READY;
        $status = array(
            'cmdStatus' => 0
        );
        $status = $this->_serializeParameters($status);
        $this->_sendMessage($status);
    }
    
    protected function _doCancel($parameters)
    {
        $this->_recordStatus = self::RECORD_STATUS_READY;
        $status = array(
            'cmdStatus' => 0
        );
        $status = $this->_serializeParameters($status);
        $this->_sendMessage($status);
    }
    
}