<?
// Modul fu"r Netatmo Welcome, später auch weitere

class Netatmo extends IPSModule
{
		
    public function Create()
    {
		//Never delete this line!
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		
        //$this->RegisterPropertyString("Devicetype", "");
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
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        *
        */
	
	private function ValidateConfiguration()
	{
		return;
		$change = false;
				
		$devicetype = $this->ReadPropertyString('Devicetype');
		
		if ($devicetype == "")
		{
			$this->SetStatus(210); // Devicetype darf nicht leer sein
		}
        //$this->EnableAction("EchoTuneInRemote_".$devicenumber);
        //$this->SetStatus(102);
	}
} 
?>