<?
// Modul fu"r Netatmo Welcome, später auch weitere

class NetatmoSecurity extends IPSModule
{
	private $VID_AccessToken ='';
	private $VID_Usermail ='';

    public function Create()
    {
		//Never delete this line!
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
		//Never delete this line!
        parent::ApplyChanges();
		/*
		$echoremoteass =  Array(
					Array(1, "Rewind 30s",  "HollowDoubleArrowLeft", -1),
					Array(2, "Previous",  "HollowLargeArrowLeft", -1),
					Array(3, "Pause/Stop",  "Sleep", -1),
					Array(4, "Play",  "Script", -1),
					Array(5, "Next",  "HollowLargeArrowRight", -1),
					Array(6, "Forward 30s",  "HollowDoubleArrowRight", -1)
				);
						
		$this->RegisterProfileIntegerAss("Echo.Remote", "Move", "", "", 1, 6, 0, 0, $echoremoteass);
		$this->RegisterVariableInteger("EchoRemote", "Echo Remote", "Echo.Remote", 1);
		$this->EnableAction("EchoRemote");
		$this->RegisterVariableBoolean("EchoShuffle", "Echo Shuffle", "~Switch", 2);
		IPS_SetIcon($this->GetIDForIdent("EchoShuffle"), "Shuffle");
		$this->EnableAction("EchoShuffle");
		$this->RegisterVariableBoolean("EchoRepeat", "Echo Repeat", "~Switch", 3);
		IPS_SetIcon($this->GetIDForIdent("EchoRepeat"), "Repeat");
		$this->EnableAction("EchoRepeat");
		$this->RegisterVariableFloat("EchoVolume", "Volume", "~Intensity.1", 4);
		$this->EnableAction("EchoVolume");
		
		*/
		$this->VID_AccessToken = $this->RegisterVariableString("AccessToken", "Token");
		$this->VID_Usermail = $this->RegisterVariableString("Usermail", "Mail");
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


        //$this->EnableAction("EchoTuneInRemote_".$devicenumber);
        else {
			SetValueString($this->VID_AccessToken, $this->getAccessToken());
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
		$scope = 'read_camera'; // write_camera access_camera'; // all scopes are selected.
		$token_url = "https://api.netatmo.com/oauth2/token";
		$postdata = http_build_query(
			array(
				'grant_type' => "password",
				'client_id' => $clientId,
				'client_secret' => $clientSecret,
				'username' => $username,
				'password' => $password,
				'scope' => $scope
			)
		);

			var_dump($postdata);

		$opts = array('http' =>
		array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata
		)
		);

		$context  = stream_context_create($opts);

		$response = file_get_contents($token_url, false, $context);
		$params = null;
		$params = json_decode($response, true);

		$api_url = "https://api.netatmo.com/api/getuser?access_token=".$params['access_token'];

		$user = json_decode(file_get_contents($api_url));
		SetValueString($this->VID_Usermail, $user->body->mail);
		echo("It worked. Hello <".$user->body->mail.">\n");

    }
	
} 
?>