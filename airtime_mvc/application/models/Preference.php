<?php

class Application_Model_Preference
{

    public static function SetValue($key, $value, $isUserValue = false){
        global $CC_CONFIG, $CC_DBC;

        //called from a daemon process
        if(!Zend_Auth::getInstance()->hasIdentity()) {
            $id = NULL;
        }
        else {
            $auth = Zend_Auth::getInstance();
            $id = $auth->getIdentity()->id;
        }

        $key = pg_escape_string($key);
        $value = pg_escape_string($value);

        //Check if key already exists
        $sql = "SELECT COUNT(*) FROM cc_pref"
        ." WHERE keystr = '$key'";
        
        //For user specific preference, check if id matches as well
        if($isUserValue) {
        	$sql .= " AND subjid = '$id'";
        }
        
        $result = $CC_DBC->GetOne($sql);

        if($result == 1) {
        	// result found
	        if(is_null($id) || !$isUserValue) {
	        	// system pref
	            $sql = "UPDATE cc_pref"
	            ." SET subjid = NULL, valstr = '$value'"
	            ." WHERE keystr = '$key'";
	        } else {
	        	// user pref
	            $sql = "UPDATE cc_pref" 
	            . " SET valstr = '$value'" 
	            . " WHERE keystr = '$key' AND subjid = $id";
	        }
        } else {
        	// result not found
	        if(is_null($id) || !$isUserValue) {
	        	// system pref
	            $sql = "INSERT INTO cc_pref (keystr, valstr)"
	            ." VALUES ('$key', '$value')";
	        } else {
	        	// user pref
	            $sql = "INSERT INTO cc_pref (subjid, keystr, valstr)"
	            ." VALUES ($id, '$key', '$value')";
	        }
        }
        
        return $CC_DBC->query($sql);
    }

    public static function GetValue($key, $isUserValue = false){
        global $CC_CONFIG, $CC_DBC;
        //Check if key already exists
        $sql = "SELECT COUNT(*) FROM cc_pref"
        ." WHERE keystr = '$key'";
        
    	//For user specific preference, check if id matches as well
        if($isUserValue) {
	        $auth = Zend_Auth::getInstance();
	        if($auth->hasIdentity()) {
		        $id = $auth->getIdentity()->id;
	        	$sql .= " AND subjid = '$id'";
		    }
        }
        
        $result = $CC_DBC->GetOne($sql);

        if ($result == 0)
            return "";
        else {
            $sql = "SELECT valstr FROM cc_pref"
            ." WHERE keystr = '$key'";
            
	        //For user specific preference, check if id matches as well
	        if($isUserValue && $auth->hasIdentity()) {
	        	$sql .= " AND subjid = '$id'";
	        }
	        
            $result = $CC_DBC->GetOne($sql);
            return $result;
        }
    }

    public static function GetHeadTitle(){
        /* Caches the title name as a session variable so we dont access
         * the database on every page load. */
        $defaultNamespace = new Zend_Session_Namespace('title_name');
        if (isset($defaultNamespace->title)) {
            $title = $defaultNamespace->title;
        } else {
            $title = self::GetValue("station_name");
            $defaultNamespace->title = $title;
        }
        if (strlen($title) > 0)
            $title .= " - ";

        return $title."Airtime";
    }

    public static function SetHeadTitle($title, $view){
        self::SetValue("station_name", $title);
        $defaultNamespace = new Zend_Session_Namespace('title_name');
        $defaultNamespace->title = $title;
        Application_Model_RabbitMq::PushSchedule();

        //set session variable to new station name so that html title is updated.
        //should probably do this in a view helper to keep this controller as minimal as possible.
        $view->headTitle()->exchangeArray(array()); //clear headTitle ArrayObject
        $view->headTitle(self::GetHeadTitle());
    }

    public static function SetShowsPopulatedUntil($timestamp) {
        self::SetValue("shows_populated_until", $timestamp);
    }

    public static function GetShowsPopulatedUntil() {
        return self::GetValue("shows_populated_until");
    }

    public static function SetDefaultFade($fade) {
        self::SetValue("default_fade", $fade);
    }

    public static function GetDefaultFade() {
        return self::GetValue("default_fade");
    }

    public static function SetStreamLabelFormat($type){
        self::SetValue("stream_label_format", $type);
        Application_Model_RabbitMq::PushSchedule();
    }

    public static function GetStreamLabelFormat(){
        return self::getValue("stream_label_format");
    }

    public static function GetStationName(){
        return self::getValue("station_name");
    }

    public static function SetAutoUploadRecordedShowToSoundcloud($upload) {
        self::SetValue("soundcloud_auto_upload_recorded_show", $upload);
    }

    public static function GetAutoUploadRecordedShowToSoundcloud() {
        return self::GetValue("soundcloud_auto_upload_recorded_show");
    }

    public static function SetSoundCloudUser($user) {
        self::SetValue("soundcloud_user", $user);
    }

    public static function GetSoundCloudUser() {
        return self::GetValue("soundcloud_user");
    }

    public static function SetSoundCloudPassword($password) {
        if (strlen($password) > 0)
            self::SetValue("soundcloud_password", $password);
    }

    public static function GetSoundCloudPassword() {
        return self::GetValue("soundcloud_password");
    }

    public static function SetSoundCloudTags($tags) {
        self::SetValue("soundcloud_tags", $tags);
    }

    public static function GetSoundCloudTags() {
        return self::GetValue("soundcloud_tags");
    }

    public static function SetSoundCloudGenre($genre) {
        self::SetValue("soundcloud_genre", $genre);
    }

    public static function GetSoundCloudGenre() {
        return self::GetValue("soundcloud_genre");
    }

    public static function SetSoundCloudTrackType($track_type) {
        self::SetValue("soundcloud_tracktype", $track_type);
    }

    public static function GetSoundCloudTrackType() {
        return self::GetValue("soundcloud_tracktype");
    }

    public static function SetSoundCloudLicense($license) {
        self::SetValue("soundcloud_license", $license);
    }

    public static function GetSoundCloudLicense() {
        return self::GetValue("soundcloud_license");
    }

    public static function SetAllow3rdPartyApi($bool) {
        self::SetValue("third_party_api", $bool);
    }

    public static function GetAllow3rdPartyApi() {
        $val = self::GetValue("third_party_api");
        if (strlen($val) == 0){
            return "0";
        } else {
            return $val;
        }
    }

    public static function SetPhone($phone){
    	self::SetValue("phone", $phone);
    }

    public static function GetPhone(){
    	return self::GetValue("phone");
    }

	public static function SetEmail($email){
    	self::SetValue("email", $email);
    }

    public static function GetEmail(){
    	return self::GetValue("email");
    }

	public static function SetStationWebSite($site){
    	self::SetValue("station_website", $site);
    }

    public static function GetStationWebSite(){
    	return self::GetValue("station_website");
    }

	public static function SetSupportFeedback($feedback){
    	self::SetValue("support_feedback", $feedback);
    }

    public static function GetSupportFeedback(){
    	return self::GetValue("support_feedback");
    }

	public static function SetPublicise($publicise){
    	self::SetValue("publicise", $publicise);
    }

    public static function GetPublicise(){
    	return self::GetValue("publicise");
    }

	public static function SetRegistered($registered){
    	self::SetValue("registered", $registered);
    }

    public static function GetRegistered(){
    	return self::GetValue("registered");
    }

	public static function SetStationCountry($country){
    	self::SetValue("country", $country);
    }

    public static function GetStationCountry(){
    	return self::GetValue("country");
    }

	public static function SetStationCity($city){
    	self::SetValue("city", $city);
    }

	public static function GetStationCity(){
    	return self::GetValue("city");
    }

	public static function SetStationDescription($description){
    	self::SetValue("description", $description);
    }

	public static function GetStationDescription(){
    	return self::GetValue("description");
    }

    public static function SetTimezone($timezone){
        self::SetValue("timezone", $timezone);
        date_default_timezone_set($timezone);
        $md = array("timezone" => $timezone);
    }

    public static function GetTimezone(){
        return self::GetValue("timezone");
    }

    public static function SetStationLogo($imagePath){
    	if(!empty($imagePath)){
	    	$image = @file_get_contents($imagePath);
	    	$image = base64_encode($image);
	    	self::SetValue("logoImage", $image);
    	}
    }

	public static function GetStationLogo(){
    	return self::GetValue("logoImage");
    }

    public static function GetUniqueId(){
    	return self::GetValue("uniqueId");
    }

    public static function GetCountryList(){
    	global $CC_DBC;
    	$sql = "SELECT * FROM cc_country";
    	$res =  $CC_DBC->GetAll($sql);
    	$out = array();
    	$out[""] = "Select Country";
    	foreach($res as $r){
    		$out[$r["isocode"]] = $r["name"];
    	}
    	return $out;
    }

    public static function GetSystemInfo($returnArray=false){
    	exec('/usr/bin/airtime-check-system', $output);

    	$output = preg_replace('/\s+/', ' ', $output);

    	$systemInfoArray = array();
    	foreach( $output as $key => &$out){
    		$info = explode('=', $out);
    		if(isset($info[1])){
    			$key = str_replace(' ', '_', trim($info[0]));
    			$key = strtoupper($key);
    			$systemInfoArray[$key] = $info[1];
    		}
    	}

    	$outputArray = array();

    	$outputArray['STATION_NAME'] = self::GetStationName();
    	$outputArray['PHONE'] = self::GetPhone();
    	$outputArray['EMAIL'] = self::GetEmail();
    	$outputArray['STATION_WEB_SITE'] = self::GetStationWebSite();
    	$outputArray['STATION_COUNTRY'] = self::GetStationCountry();
    	$outputArray['STATION_CITY'] = self::GetStationCity();
    	$outputArray['STATION_DESCRIPTION'] = self::GetStationDescription();

    	// get web server info
    	if(isset($systemInfoArray["AIRTIME_VERSION_URL"])){
    	   $url = $systemInfoArray["AIRTIME_VERSION_URL"];
           $index = strpos($url,'/api/');
           $url = substr($url, 0, $index);

           $headerInfo = get_headers(trim($url),1);
           $outputArray['WEB_SERVER'] = $headerInfo['Server'][0];
    	}

    	$outputArray['NUM_OF_USERS'] = Application_Model_User::getUserCount();
    	$outputArray['NUM_OF_SONGS'] = Application_Model_StoredFile::getFileCount();
    	$outputArray['NUM_OF_PLAYLISTS'] = Application_Model_Playlist::getPlaylistCount();
    	$outputArray['NUM_OF_SCHEDULED_PLAYLISTS'] = Application_Model_Schedule::getSchduledPlaylistCount();
    	$outputArray['NUM_OF_PAST_SHOWS'] = Application_Model_ShowInstance::GetShowInstanceCount(date("Y-m-d H:i:s"));
    	$outputArray['UNIQUE_ID'] = self::GetUniqueId();

    	$outputArray = array_merge($systemInfoArray, $outputArray);

    	$outputString = "\n";
    	foreach($outputArray as $key => $out){
    	    if($out != ''){
    		    $outputString .= $key.' : '.$out."\n";
    	    }
    	}
    	if($returnArray){
    	    $outputArray['PROMOTE'] = self::GetPublicise();
    		$outputArray['LOGOIMG'] = self::GetStationLogo();
    	    return $outputArray;
    	}else{
    	    return $outputString;
    	}
    }

    public static function SetRemindMeDate($now){
    	$weekAfter = mktime(0, 0, 0, date("m")  , date("d")+7, date("Y"));
   		self::SetValue("remindme", $weekAfter);
    }

    public static function GetRemindMeDate(){
        return self::GetValue("remindme");
    }

    public static function SetImportTimestamp(){
        $now = time();
        if(self::GetImportTimestamp()+5 < $now){
            self::SetValue("import_timestamp", $now);
        }
    }

    public static function GetImportTimestamp(){
        return self::GetValue("import_timestamp");
    }

    public static function GetStreamType(){
        $st = self::GetValue("stream_type");
        return explode(',', $st);
    }

    public static function GetStreamBitrate(){
        $sb = self::GetValue("stream_bitrate");
        return explode(',', $sb);
    }

    public static function SetPrivacyPolicyCheck($flag){
        self::SetValue("privacy_policy", $flag);
    }

    public static function GetPrivacyPolicyCheck(){
        return self::GetValue("privacy_policy");
    }

    public static function SetNumOfStreams($num){
        self::SetValue("num_of_streams", intval($num));
    }

    public static function GetNumOfStreams(){
        return self::GetValue("num_of_streams");
    }

    public static function SetMaxBitrate($bitrate){
        self::SetValue("max_bitrate", intval($bitrate));
    }

    public static function GetMaxBitrate(){
        return self::GetValue("max_bitrate");
    }

    public static function SetPlanLevel($plan){
        self::SetValue("plan_level", $plan);
    }

    public static function GetPlanLevel(){
        return self::GetValue("plan_level");
    }

    public static function SetTrialEndingDate($date){
        self::SetValue("trial_end_date", $date);
    }

    public static function GetTrialEndingDate(){
        return self::GetValue("trial_end_date");
    }

    public static function SetEnableStreamConf($bool){
        self::SetValue("enable_stream_conf", $bool);
    }

    public static function GetEnableStreamConf(){
        if(self::GetValue("enable_stream_conf") == Null){
            return "true";
        }
        return self::GetValue("enable_stream_conf");
    }

    public static function GetAirtimeVersion(){
        return self::GetValue("system_version");
    }

    public static function SetUploadToSoundcloudOption($upload) {
        self::SetValue("soundcloud_upload_option", $upload);
    }

    public static function GetUploadToSoundcloudOption() {
        return self::GetValue("soundcloud_upload_option");
    }

    public static function SetSoundCloudDownloadbleOption($upload) {
        self::SetValue("soundcloud_downloadable", $upload);
    }

    public static function GetSoundCloudDownloadbleOption() {
        return self::GetValue("soundcloud_downloadable");
    }
    
    public static function SetWeekStartDay($day) {
        self::SetValue("week_start_day", $day);
    }

    public static function GetWeekStartDay() {
    	$val = self::GetValue("week_start_day");
        if (strlen($val) == 0){
            return "0";
        } else {
            return $val;
        }
    }

	/* User specific preferences start */

    /**
     * Sets the time scale preference (day/week/month) in Calendar.
     * 
     * @param $timeScale	new time scale
     */
	public static function SetCalendarTimeScale($timeScale) {
        return self::SetValue("calendar_time_scale", $timeScale, true /* user specific */);
    }

    /**
     * Retrieves the time scale preference for the current user.
     */
    public static function GetCalendarTimeScale() {
    	return self::GetValue("calendar_time_scale", true /* user specific */);
    }
    
    /**
     * Sets the number of entries to show preference in library under Playlist Builder.
     * 
     * @param $numEntries	new number of entries to show
     */
    public static function SetLibraryNumEntries($numEntries) {
    	return self::SetValue("library_num_entries", $numEntries, true /* user specific */);
    }
    
    /**
     * Retrieves the number of entries to show preference in library under Playlist Builder.
     */
    public static function GetLibraryNumEntries() {
    	return self::GetValue("library_num_entries", true /* user specific */);
    }
    
    /**
     * Sets the time interval preference in Calendar.
     * 
     * @param $timeInterval		new time interval
     */
	public static function SetCalendarTimeInterval($timeInterval) {
        return self::SetValue("calendar_time_interval", $timeInterval, true /* user specific */);
    }

    /**
     * Retrieves the time interval preference for the current user.
     */
    public static function GetCalendarTimeInterval() {
    	return self::GetValue("calendar_time_interval", true /* user specific */);
    }
    
    /* User specific preferences end */
}
