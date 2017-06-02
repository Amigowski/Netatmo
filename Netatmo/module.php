<?
// Modul fu"r Netatmo Welcome, später auch weitere

class NetatmoSecurity extends IPSModule
{
	private $VID_AccessToken ='';
	private $VID_RefreshToken ='';
	private $VID_Scope ='';
	private $VID_Expires ='';
	private $VID_Expire ='';
	private $VID_Usermail ='';
	
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
	}
	
    public function ApplyChanges()
    {
		
        parent::ApplyChanges();
	
		$this->VID_AccessToken = $this->RegisterVariableString("AccessToken", "AccessToken");
		$this->VID_Usermail = $this->RegisterVariableString("Usermail", "Mail");
		$this->VID_RefreshToken = $this->RegisterVariableString("RefreshToken", "RefreshToken");
		$this->VID_Scope = $this->RegisterVariableString("Scope", "Scope");
		$this->VID_Expires = $this->RegisterVariableString("Expires", "Expires");
		$this->VID_Expire = $this->RegisterVariableString("Expire", "Expire");
	
		$this->ValidateConfiguration();	
	
    }

	
	private function ValidateConfiguration()
	{
		$change = false;
				
		$devicetype = $this->ReadPropertyString('Devicetype');
		$username = $this->ReadPropertyString('Username');
		$password = $this->ReadPropertyString('Password');
		$clientId = $this->ReadPropertyString('ClientId');
		$clientSecret = $this->ReadPropertyString('ClientSecret');
		
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
				$this->SetStatus(102); // OK
		}
		
	}

	 /************************** Schnittstelle Netatmo *******************************/

	public function getAccessToken () 
	{
		$clientId = $this->ReadPropertyString('ClientId');
		$clientSecret = $this->ReadPropertyString('ClientSecret');
		$username = $this->ReadPropertyString('Username');
		$password = $this->ReadPropertyString('Password');
		$scope = "read_camera write_camera access_camera";

        $token_url = "https://api.netatmo.com/oauth2/token";

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
			SetValueString($this->VID_AccessToken,$jsonDatas['access_token']);
			SetValueString($this->VID_RefreshToken,$jsonDatas['refresh_token']);
			SetValueString($this->VID_Scope,$jsonDatas['scope']);
			SetValueString($this->VID_Expires,$jsonDatas['expires_in']);
			SetValueString($this->VID_Expire,$jsonDatas['expire_in']);

            return true;
        }
        else
        {
            $this->SetStatus(208);
            return false;
        }
        return true;
    }
	
} 
?>