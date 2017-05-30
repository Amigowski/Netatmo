<?
// Modul fu"r Netatmo Welcome, später auch weitere

class NetatmoSecurity extends IPSModule
{
		
    public function Create()
    {
		//Never delete this line!
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		
        $this->RegisterPropertyString("Devicetype", "");
		$this->RegisterPropertyString("User", "");
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
		
		//$this->ValidateConfiguration();	
	
    }

		/**
        * Die folgenden Funktionen stehen automatisch zur Verfuegung, wenn das Modul ueber die "Module Control" eingefuegt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfuegung gestellt:
        *
        *
        */
	
	private function ValidateConfiguration()
	{
		$change = false;
				
		$devicetype = $this->ReadPropertyString('Devicetype');
		$user = $this->ReadPropertyString('User');
		$password = $this->ReadPropertyString('Password');
		
		if ($devicetype == "")
		{
			$this->SetStatus(201); // Devicetype darf nicht leer sein
		}
		else if ($user == "")
		{
			$this->SetStatus(202); // Devicetype darf nicht leer sein
		}
		else if ($password == "")
		{
			$this->SetStatus(203); // Devicetype darf nicht leer sein
		}



        //$this->EnableAction("EchoTuneInRemote_".$devicenumber);
        //$this->SetStatus(102);
		
	}

	 /************************** Schnittstelle Netatmo *******************************/
	/*
	public function getAccessToken () 
	{
    $app_id = '592d2d6c6b0affb65e8b741f';
    $app_secret = 'lf2bKTh1WpeUkgJe4offqCbHtKzQ48TYG41pYy5ISIlA';
    $username = 'YOUR_USERNAME';
    $password = 'YOUR_PASSWORD';
    $scope = 'read_station read_thermostat write_thermostat'; // all scopes are selected.
    $token_url = "https://api.netatmo.com/oauth2/token";
    $postdata = http_build_query(
        array(
            'grant_type' => "password",
            'client_id' => $app_id,
            'client_secret' => $app_secret,
            'username' => $username,
            'password' => $password,
            'scope' => $scope
        )
    );

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

    $api_url = "https://api.netatmo.com/api/getuser?access_token="
    . $params['access_token'];

    $user = json_decode(file_get_contents($api_url);
    echo("It worked. Hello <".$user->body->mail.">\n");

    }
	*/
} 
?>