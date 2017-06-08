<?
function doTheHook ($jsonData) {
    //IPS_LogMessage("Netatmo DoTheHook RAW", $jsonData);

    $notif = json_decode($jsonData, TRUE);
    
    $message = '';
    $eventype = '';


    if(isset($notif['message']))
    {
        $message = $notif['message'];
    }
    
    if(isset($notif['event_type']))
    {
        $eventtype = $notif['event_type'];
        if ($eventtype == 'person') {
            Netatmo_getPersons(getParent());
        }
    }
    
    if(isset($notif['camera_id']))
    {
        SetValueString(get_VID_CamId(),$notif['camera_id']);
    }
    
    if(isset($notif['home_id']))
    {
        SetValueString(get_VID_HomeId(),$notif['home_id']);
    }
    
    if(isset($notif['home_name']))
    {
        SetValueString(get_VID_HomeName(),$notif['home_name']);
    }
}

function getParent () {
    return IPS_GetParent($_IPS['SELF']);
}
function get_VID_HomeId() {
    return IPS_GetVariableIDByName ( 'HomeId', getParent());
}
function get_VID_HomeName() {
    return IPS_GetVariableIDByName ( 'Home', getParent());
}
function get_VID_CamId() {
    return IPS_GetVariableIDByName ( 'CamId', getParent());
}
?>