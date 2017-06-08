<?
// Modul fu"r Netatmo Welcome, später auch weitere

class NetatmoSecurity extends IPSModule
{
	private $logSource = 'Netatmo';
    //home:
    public $_home;
    public $_timezone;
    //API:
    public $_scope;
    public $error;
    public $_homeID = 0; //will support several homes later
    //devices:
    public $_cameras; //both Presences and Welcome
    public $_persons;
    //datas:
    protected $_camerasDatas;
    protected $_weatherDatas;
    protected $_apiurl = 'https://api.netatmo.net/';

	
    public function Create()
    {
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		
        $this->RegisterPropertyString("Devicetype", "");
		$this->RegisterPropertyString("ClientId", "");
		$this->RegisterPropertyString("ClientSecret", "");
		$this->RegisterPropertyString("Username", "");
		$this->RegisterPropertyString("Password", "");
		$this->RegisterPropertyString("Url", "");
	}
	
    public function ApplyChanges()
    {
		
        parent::ApplyChanges();
	
		//$this->VID_AccessToken = 
		$this->RegisterVariableString("AccessToken", "AccessToken");
		$this->RegisterVariableString("RefreshToken", "RefreshToken");
		
		//$this->VID_Expires = 
		$this->RegisterVariableString("Expires", "Expires");
		$this->RegisterVariableString("CamId","CamId");
		$this->RegisterVariableString("Home","Home");
		$this->RegisterVariableString("HomeId","HomeId");
        $this->RegisterVariableBoolean("HomeEmpty","HomeEmpty");
		
		
		//Kategorien
		if (@IPS_GetCategoryIDByName('Persons', $this->InstanceID) ==false) {
			$cid = IPS_CreateCategory();
			IPS_SetName($cid, 'Persons');
			IPS_SetParent($cid, $this->InstanceID);
		}
        if (@IPS_GetCategoryIDByName('Persons at Home', $this->InstanceID) ==false) {
			$cid = IPS_CreateCategory();
			IPS_SetName($cid, 'Persons at Home');
			IPS_SetParent($cid, $this->InstanceID);
		}
        if (@IPS_GetCategoryIDByName('Persons away', $this->InstanceID) ==false) {
			$cid = IPS_CreateCategory();
			IPS_SetName($cid, 'Persons away');
			IPS_SetParent($cid, $this->InstanceID);
		}
		
		// WebHook
		$content = '<?


IPS_LogMessage("Netatmo WebHook GET", print_r($_GET, true));
IPS_LogMessage("Netatmo WebHook POST", print_r($_POST, true));
IPS_LogMessage("Netatmo WebHook IPS", print_r($_IPS, true));
IPS_LogMessage("Netatmo WebHook RAW", file_get_contents("php://input"));

require_once "../modules/Netatmo/Netatmo/doHook.php";
doTheHook(file_get_contents("php://input"));


?>';

        $scriptId = $this->RegisterScript("WebHookNetatmo", "WebHookNetatmo", $content,0);
        //PS_SetHidden($scriptId, true);
        if (IPS_GetKernelRunlevel() == 10103)
		{
			$this->ValidateConfiguration();	
			// Webhoook in ips anlegen
		    $this->RegisterHook('/hook/Netatmo'.$this->InstanceID, $scriptId);
			
            $this->dropWebhook();
            // webhook bei Netatmo anmelden
			$this->setWebhook();
			
		}
	
    }

	
	private function ValidateConfiguration()
	{
		$change = false;
				
		$devicetype = $this->ReadPropertyString('Devicetype');
		$username = $this->ReadPropertyString('Username');
		$password = $this->ReadPropertyString('Password');
		$clientId = $this->ReadPropertyString('ClientId');
		$clientSecret = $this->ReadPropertyString('ClientSecret');
		$url = $this->ReadPropertyString("Url");
		
		if ($devicetype == "")
		{
			$this->SetStatus(201); // Devicetype darf nicht leer sein
		}else if ($devicetype == "Presence")
		{
			$this->SetStatus(206); // Gerät noch nicht unterstützt
		}
		else if ($username == "")
		{
			$this->SetStatus(202); // username darf nicht leer sein
		}
		else if ($password == "")
		{
			$this->SetStatus(203); // ^password darf nicht leer sein
		}
		else if ($clientId == "") {
			$this->SetStatus(204);
		}
		else if ($clientSecret == "") {
			$this->SetStatus(205);
		}

        else {
			if ($this->getAccessToken())
				IPS_LogMessage($this->logSource, "Connected");
				$this->SetStatus(102); // OK
		}
		
	}



	 /************************** Schnittstelle Netatmo *******************************/
	private function refreshToken () 
	{

	}
	
	private function get_VID_AccessToken() {
		return IPS_GetVariableIDByName ( 'AccessToken', $this->InstanceID);
	}

    private function get_VID_RefreshToken() {
        return IPS_GetVariableIDByName ( 'RefreshToken', $this->InstanceID);
    }
	
	private function get_VID_Expires() {
		return IPS_GetVariableIDByName ( 'Expires', $this->InstanceID);
	}
	private function get_VID_HomeEmpty() {
		return IPS_GetVariableIDByName ( 'HomeEmpty', $this->InstanceID);
	}
	
	private function get_CID_Person($name) {
        $cid = IPS_GetCategoryIDByName ('Persons', $this->InstanceID);
        $pid= @IPS_GetCategoryIDByName($name, $cid);
		return $pid;
	}
	private function get_CID($name) {
        $cid = IPS_GetCategoryIDByName ($name, $this->InstanceID);
        
		return $cid;
	}
	private function getAccessToken () 
	{
		if (GetValueString($this->get_VID_AccessToken())) {
				// Token haben wir schon ist es auch gültig
			$expiresIn = DateTime::createFromFormat('Y-m-d H:i:s', GetValueString($this->get_VID_Expires()));
			if (new DateTime() > $expiresIn) {
				//return $this->refreshToken();
			}else{
				return GetValueString($this->get_VID_AccessToken());
			}
		}
		
		$clientId = $this->ReadPropertyString('ClientId');
		$clientSecret = $this->ReadPropertyString('ClientSecret');
		$username = $this->ReadPropertyString('Username');
		$password = $this->ReadPropertyString('Password');
		$scope = "read_camera write_camera access_camera";

        $token_url = $this->_apiurl.'/oauth2/token';

        $postdata = http_build_query(
                                    array(
                                        'grant_type' => 'password',
                                        'client_id' => $clientId,
                                        'client_secret' => $clientSecret,
                                        'username' => $username,
                                        'password' => $password,
                                        'scope' =>$scope	//</1scope>'read_station read_thermostat write_thermostat read_camera write_camera access_camera read_presence access_presence write_presence read_homecoach'
                )
            );
        $opts = array('http' =>
                            array(
                                'method'  => 'POST',
                                'header'  => 'Content-type: application/x-www-form-urlencoded;charset=UTF-8'."\r\n".
                                            'User-Agent: netatmoclient',
                                'content' => $postdata
                )
            );
        $context  = stream_context_create($opts);
        $response = @file_get_contents($token_url, false, $context);
        //netatmo server sometimes give 500, always works second time:
        if ($response === false) {
            $response = @file_get_contents($token_url, false, $context);
            if ($response === false) {
                $this->SetStatus(207);
                return false;
            }
        }
        $jsonDatas = json_decode($response, true);
        if (isset($jsonDatas['access_token']))
        {
			SetValueString($this->get_VID_AccessToken(),$jsonDatas['access_token']);
			SetValueString($this->get_VID_RefreshToken(),$jsonDatas['refresh_token']);
			$expiresIn = new DateTime('+'.$jsonDatas['expires_in'].' seconds');
			
			SetValueString($this->get_VID_Expires(),$expiresIn->format('Y-m-d H:i:s'));

			$api_url = "https://api.netatmo.com/api/getuser?access_token=".$jsonDatas['access_token'];
    		$user = json_decode(file_get_contents($api_url));
    		
			return GetValueString($this->get_VID_AccessToken());
        }
        else
        {
            $this->SetStatus(208);
            return false;
        }
		return GetValueString($this->get_VID_AccessToken());
    }
	

    //TODO
	private function getIndoorEvents($num=5)
    {
        if (is_null($this->_camerasDatas)) $this->getCamerasDatas(10);
        if (is_null($this->_cameras)) $this->getCameras();
        $cameraEvents = $this->_camerasDatas['body']['homes'][$this->_homeID]['events'];
        $returnEvents = array();
        for ($i=0; $i <= $num ;$i++)
        {
            //avoid iterating more than there is!
            if (isset($cameraEvents[$i])) $thisEvent = $cameraEvents[$i];
            else break;
            $camId = $thisEvent['camera_id'];
            foreach ($this->_cameras as $cam)
                {
                    if ($cam['id'] == $camId)
                    {
                        $camName = $cam['name'];
                        $camType = $cam['type'];
                        break;
                    }
                }
            //get only indoor events:
            if ($camType != 'Welcome')
            {
                continue;
            }
            $id = $thisEvent['id'];
            $type = $thisEvent['type'];
            $time = $thisEvent['time'];
            $date = date('d-m-Y H:i:s', $time);
            $message = $thisEvent['message'];
            $returnThis = array();
            $returnThis['title'] = $message . ' | '.$date.' | '.$camName;
            $returnThis['type'] = $type;
            $returnThis['time'] = $thisEvent['time'];
            $returnThis['date'] = $date;
            if (isset($thisEvent['person_id'])) $returnThis['person_id'] = $thisEvent['person_id'];
            if (isset($thisEvent['snapshot']))
            {
                $snapshot = $thisEvent['snapshot'];
                $snapshotID = $snapshot['id'];
                $snapshotKEY = $snapshot['key'];
                $snapshotURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$snapshotID.'&key='.$snapshotKEY;
                $returnThis['snapshotURL'] = $snapshotURL;
            }
            if (isset($thisEvent['is_arrival'])) $returnThis['is_arrival'] = $thisEvent['is_arrival'];
            $returnThis['camera_id'] = $camId;
            $returnThis['event_id'] = $id;
            array_push($returnEvents, $returnThis);
        }
        return $returnEvents;
    }
    public function getPerson($name) //Welcome
    {
        if ( is_string($name) ) return get_CID_Person($name);
    }
    public function getPersonsAtHome() //Welcome
    {
         return get_CID('Persons at Home');
    }
    public function isHomeEmpty() //Welcome
    {
        $atHome = $this->getPersonsAtHome();
        if (count($atHome)==0) return true;
        return false;
    }
    public function setPersonAway($person) //Welcome
    {
        if ( is_string($person) ) $person = $this->getPersonByName($person);
        if ( isset($person['error']) ) return $person;
        $personID = $person['id'];
        $homeID = $this->_camerasDatas['body']['homes'][$this->_homeID]['id'];
        $api_url = $this->_apiurl.'/api/setpersonsaway?access_token=' . $this->_accesstoken .'&home_id='.$homeID.'&person_id='.$personID .'&size=2';
        $response = file_get_contents($api_url, false);
        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }
    public function setHomeEmpty() //Welcome
    {
        $homeID = $this->_camerasDatas['body']['homes'][$this->_homeID]['id'];
        $api_url = $this->_apiurl.'/api/setpersonsaway?access_token=' . $this->_accesstoken .'&home_id='.$homeID.'&size=2';
        $response = file_get_contents($api_url, false);
        $jsonDatas = json_decode($response, true);
        return $jsonDatas;
    }
    //for sake of retro-compatibility:
    public function getPresenceCameras()
    {
        foreach ($this->_cameras as $camera) {
            if ($camera['type'] == 'Presence') $camArray[$camera['name']] = $camera;;
        }
        return $camArray;
    }
    public function getWelcomeCameras()
    {
        $camArray = array();
        foreach ($this->_cameras as $camera) {
            if ($camera['type'] == 'Welcome') $camArray[$camera['name']] = $camera;;
        }
        return $camArray;
    }
    //WEBHOOK:
    public function setWebhook()
    {
		$endpoint = $this->ReadPropertyString("Url").'/hook/Netatmo'.$this->InstanceID;
        $api_url = $this->_apiurl.'/api/addwebhook?access_token=' . $this->getAccessToken() . '&url='.$endpoint.'&app_type=app_security';
        $requete = @file_get_contents($api_url);
        $jsonDatas = json_decode($requete,true);
        if ($jsonDatas['status']== 'ok') {
			IPS_LogMessage($this->logSource, "Webhook successfull ".$endpoint);
			return true;			
		}
		IPS_LogMessage($this->logSource, "Webhook status:".$jsonDatas['status']);
		return false;
    }
    public function dropWebhook()
    {
        $api_url = $this->_apiurl.'/api/dropwebhook?access_token=' . $this->getAccessToken() .'&app_type=app_security';
        $requete = @file_get_contents($api_url);
        $jsonDatas = json_decode($requete,true);
         if ($jsonDatas['status']== 'ok') {
			IPS_LogMessage($this->logSource, "Unhook successfull ");
			return true;			
		}
        IPS_LogMessage($this->logSource, "Unhook status:".$jsonDatas['status']);
		return false;
    }






	 //internal functions==================================================
    protected function getCamerasDatas($eventNum=50) //request full Presence/Welcome datas
    {
		$token = $this->getAccessToken();
		
        $api_url = $this->_apiurl.'/api/gethomedata?access_token=' . $token .'&size='.$eventNum;
		IPS_LogMessage("Netatmo getCamerasDatas url", $api_url);
		$response = file_get_contents($api_url, false);
		IPS_LogMessage("Netatmo getCamerasDatas Response", $response);
        $jsonDatas = json_decode($response, true);
        $this->_camerasDatas = $jsonDatas;
        $this->_home = $jsonDatas['body']['homes'][$this->_homeID]['name'];
        if( isset($jsonDatas['body']['homes'][$this->_homeID]['place']['timezone']) ) $this->_timezone = $jsonDatas['body']['homes'][$this->_homeID]['place']['timezone'];
        return $jsonDatas;
    }

	protected function getCameras()
    {
        if (is_null($this->_camerasDatas)) $this->getCamerasDatas();
        $allCameras = array();
        foreach ($this->_camerasDatas['body']['homes'][$this->_homeID]['cameras'] as $thisCamera)
        {
            //live and snapshots:
            $cameraVPN = (isset($thisCamera['vpn_url']) ? $thisCamera['vpn_url'] : null);
            $isLocal = (isset($thisCamera['is_local']) ? $thisCamera['is_local'] : false);
            $cameraSnapshot = null;
            $cameraLive = null;
            if ($cameraVPN != null)
            {
                $cameraLive = ($isLocal == false ? $cameraVPN.'/live/index.m3u8' : $cameraVPN.'/live/index_local.m3u8');
                $cameraSnapshot = $cameraVPN.'/live/snapshot_720.jpg';
            }
            //which camera model:
            if ($thisCamera['type'] == 'NOC') //Presence
            {
                $camera = array('name' => $thisCamera['name'],
                                'id' => $thisCamera['id'],
                                'vpn' => $cameraVPN,
                                'snapshot' => $cameraSnapshot,
                                'live' => $cameraLive,
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'light_mode_status' => $thisCamera['light_mode_status'],
                                'is_local' => $isLocal,
                                'type' => 'Presence'
                                );
                array_push($allCameras, $camera);
            }
            elseif ($thisCamera['type'] == 'NACamera') //Welcome:
            {
                $camera = array('name' => $thisCamera['name'],
                                'id' => $thisCamera['id'],
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'type' => 'Welcome'
                                );
                array_push($allCameras, $camera);
            }
        }
        $this->_cameras = $allCameras;
    }
    public function getPersons() //Welcome
    {	
        if (is_null($this->_camerasDatas)) $this->getCamerasDatas();
        $homeDatas = $this->_camerasDatas;
      
        if ( isset($homeDatas['body']['homes'][$this->_homeID]['persons']) )
        {
            $persons = $homeDatas['body']['homes'][$this->_homeID]['persons'];
			$cid = IPS_GetCategoryIDByName ('Persons', $this->InstanceID);
            $cahid = IPS_GetCategoryIDByName ('Persons at Home', $this->InstanceID);
            $cawid = IPS_GetCategoryIDByName ('Persons away', $this->InstanceID);
            foreach ($persons as $person)
            {
                //echo "<pre>person:<br>".json_encode($person, JSON_PRETTY_PRINT)."</pre><br>";
                if ( isset($person['pseudo']) ) {
					$pid= @IPS_GetCategoryIDByName($person['pseudo'], $cid);
					if(  $pid == false) {
						$pid = IPS_CreateCategory();
						IPS_Setname ($pid, $person['pseudo']);
						IPS_SetParent($pid, $cid);
					}
						
					$iid = @IPS_GetVariableIDByName('Id', $pid);
					if ($iid == false) {
						$iid = IPS_CreateVariable(3);
						IPS_SetName($iid, 'Id');
						IPS_SetParent ($iid, $pid);
					}
					SetValueString($iid, $person['id']);
					
					$lid = @IPS_GetVariableIDByName('Lastseen', $pid);
					if ($lid == false) {
						$lid = IPS_CreateVariable(3);
						IPS_SetName($lid, 'Lastseen');
						IPS_SetParent ($lid, $pid);
					}
					$lastseen = $person['last_seen'];
					if ($lastseen == 0) SetValueString($lid, 'Been long');
					else SetValueString($lid,  date("d-m-Y H:i:s", $person['last_seen']));
					
					$oid = @IPS_GetVariableIDByName('out_of_sight', $pid);
					if ($oid == false) {
						$oid = IPS_CreateVariable(0);
						IPS_SetName($oid, 'out_of_sight');
						IPS_SetParent ($oid, $pid);
					}
					SetValueBoolean($oid, $person['out_of_sight']);
					
					$aid = @IPS_GetVariableIDByName('is_arrival', $pid);
					if ($aid == false) {
						$aid = IPS_CreateVariable(3);
						IPS_SetName($aid, 'is_arrival');
						IPS_SetParent ($aid, $pid);
					}
					if ( isset($person['is_arrival']) ) 
						SetValueString($aid, $person['is_arrival']);

                    //De/Verlinken
                    if ($person['out_of_sight']) {
                        //away
                        if (@IPS_GetLinkIDByName($person['pseudo'],$cahid)) {
                            IPS_DeleteLink(@IPS_GetLinkIDByName($person['pseudo'],$cahid));
                        }
                        if (@IPS_GetLinkIDByName($person['pseudo'],$cawid) == false) {
                            $linkid = IPS_CreateLink();
                            IPS_SetLinkTargetID ($linkid, $pid);
                            IPS_SetName($linkid, $person['pseudo']);
                            IPS_SetParent($linkid, $cawid);
                        }
                    }else{
                              //at home
                        if (@IPS_GetLinkIDByName($person['pseudo'],$cawid)) {
                            IPS_DeleteLink(@IPS_GetLinkIDByName($person['pseudo'],$cawid));
                        }
                        if (@IPS_GetLinkIDByName($person['pseudo'],$cahid) == false) {
                            $linkid = IPS_CreateLink();
                            IPS_SetLinkTargetID ($linkid, $pid);
                            IPS_SetName($linkid, $person['pseudo']);
                            IPS_SetParent($linkid, $cahid);
                        }
                    }

				}
            }
            //home Empty
            IPS_LogMessage("Netatmo CatOId", $cahid);
            IPS_LogMessage("Netatmo Children", IPS_HasChildren($cahid));

            SetValueBoolean($this->get_VID_HomeEmpty (),IPS_HasChildren($cahid));
        }
    }
    protected function getPersonByName($name) //Welcome
    {
        if (empty($this->_persons)) return array('result'=>null, 'error' => 'No person defined in this home.');
        foreach ($this->_persons as $thisPerson)
        {
            if ($thisPerson['pseudo'] == $name) return $thisPerson;
        }
        return array('result'=>null, 'error' => 'Unfound person');
    }


	 private function RegisterHook($WebHook, $TargetID)
    {
        $ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
        if (sizeof($ids) > 0)
        {
            $hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
            $found = false;
            foreach ($hooks as $index => $hook)
            {
                if ($hook['Hook'] == $WebHook)
                {
                    if ($hook['TargetID'] == $TargetID)
                        return;
                    $hooks[$index]['TargetID'] = $TargetID;
                    $found = true;
                }
            }
            if (!$found)
            {
                $hooks[] = Array("Hook" => $WebHook, "TargetID" => $TargetID);
            }
            IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }
} 
?>