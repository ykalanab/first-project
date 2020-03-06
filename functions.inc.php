<!--
	Author: Lasantha Aluthge & Supun Dewaraja
	PBXCU Implementation Phase
	Zone24x7  
-->
<?php
	include "../../../meetme/phpagi/phpagi-asmanager.php";
	
	/* A function to display the header of the page*/
	function displayHeader_conf(){
	?>	
		<html>
			<head>
				<title>Conference Bridge</title>			
			</head>
			<body>
	<?php 
	}
	
	/* A function to display the dropdown*/
	function displayServers_conf($selected_server,$default){
		?>
		<br><br>
		<h2>Conference Bridge</h2>
		
		<form action="<?php echo $local_path; ?>" method="get">
			<input type="hidden" name="type" value="tool" />
 			<input type="hidden" name="display" value="conference_bridge" />
 	
 			<!-- Populate server combo -->
			<div>
	  			<b>Select server</b>
	  			<select name="server_list" id="server_list" onChange="this.form.submit()">
	  					<?php
						/* Give an option for the default server*/
	  					if($default){
	  					 ?> 
	  			 			<option <?php echo "selected='selected'";?> value=<?php echo $_SERVER['SERVER_NAME'];?> >localhost</option>
					  	<?php  
					  	}else{
			  			?> 
			  			 	<option value=<?php echo $_SERVER['SERVER_NAME'];?> >localhost</option>
			  			 <?php  
	  					}
					  		
						$serverList = getServerList();
						foreach($serverList as $server){
							if(isset($_REQUEST['server_list']) && $_REQUEST['server_list']==$server['server_ip']){
							?>	
								<option selected='selected' value='<?php echo $server['server_ip']; ?>'><?php echo $server['server_name']; ?> </option>
							<?php
							}else{
							?>		
								<option value='<?php echo $server['server_ip'];?>'><?php echo $server['server_name']; ?></option>
							<?php
							}				
						}
					?>
	  			</select>
			</div>
			<!-- Populate server combo-->
			<br/>
			</form>
			<?php		
	}
	
	/* A function to display conference rooms for a particular server */
	function displayRooms_conf($selected_server,$local_server, $default){
	?>	
		
		<!-- Display conference rooms of a given server-->
			<div id="confList">
			<?php
				if(isset($_REQUEST['server_list']) || $default)
				{
					//Decide local or remote request		
					if($_REQUEST['server_list']==$_SERVER['SERVER_NAME'] || $default){
						//Get local conference list
						getLocalConferenceList($selected_server,$local_server);		
					}else{
						//Get remote conference list
						getRemoteConferenceList($selected_server,$local_server);	
					}					
				} // end - isset($_REQUEST['server_id'] 	
	 		?>
 			</div>
 			<!-- Display conference rooms of a given server --> 	
 	 		<br/>
	<?php		
	}	
	
	/* A function to display users in a conference room for the local server */
	function displayUsers_conf(){
		//Display Users in the Conference -->
		if (isset($_REQUEST['connect_to_conf']) && isset($_REQUEST['connect_to_num']) && $_REQUEST['connect_to_num']!=""){
			getLocalConferenceUsers($_REQUEST['connect_to_conf']);									
		}
		else if (isset($_REQUEST['conf_num']) && isset($_REQUEST['display_users']) && !isset($_REQUEST['operation'])){
			//Decide local or remote request		
			getLocalConferenceUsers($_REQUEST['conf_num']);		
		}
		else if(isset($_REQUEST['operation']) ){
			getLocalConferenceUsers($_REQUEST['conf_num']);									
		}
		
		/* The tail part of the page*/
		?>
				<!-- </form>-->
			</body>
		</html>
		<?php
	}

	
	/* A function to do the operations */
	function doOperations_conf(){
		//Display Users in the Conference -->
		if (isset($_REQUEST['connect_to_conf']) && isset($_REQUEST['connect_to_num']) && $_REQUEST['connect_to_num']!=""){
			//Connect user to the Conference
			connectUser($_REQUEST['connect_to_conf'], $_REQUEST['connect_to_num']);
			sleep(4);											
		}		
		else if(isset($_REQUEST['operation']) ){
			//Perform User related operations in the Conference
			performOperation($_REQUEST['conf_num'], $_REQUEST['operation'], $_REQUEST['user_id']);
			sleep(2);										
		}
	}	
	/* A function to do the remote operations */
	function doRemoteOperations_conf($selected_server, $local_server){
		/* Constructing a POST request */			
		$path = $selected_server['freepbx_url']."admin/config.php";
		
		if (isset($_REQUEST['connect_to_conf']) && isset($_REQUEST['connect_to_num']) && $_REQUEST['connect_to_num']!=""){
			$postfields = http_build_query( array ('type' => 'tool', 'display' => 'conference_bridge', 'remote' => 'users', 'connect_to_conf' => $_REQUEST['connect_to_conf'], 'connect_to_num' => $_REQUEST['connect_to_num'], 'conf_num' => $_REQUEST['conf_num'], 'display_users' => $_REQUEST['display_users'], 'username' => $selected_server['freepbx_username'],'pwd' => $selected_server['freepbx_password']));	
		}
		else if (isset($_REQUEST['conf_num']) && isset($_REQUEST['display_users']) && !isset($_REQUEST['operation'])){
			$postfields = http_build_query( array ('type' => 'tool', 'display' => 'conference_bridge', 'remote' => 'users','conf_num' => $_REQUEST['conf_num'], 'display_users' => $_REQUEST['display_users'], 'username' => $selected_server['freepbx_username'], 'pwd' => $selected_server['freepbx_password']));
		}
		else if(isset($_REQUEST['operation']) ){
			$postfields = http_build_query( array ('type' => 'tool', 'display' => 'conference_bridge', 'remote' => 'users','conf_num' => $_REQUEST['conf_num'],'user_id' => $_REQUEST['user_id'] ,'display_users' => $_REQUEST['display_users'], 'operation' => $_REQUEST['operation'], 'username' => $selected_server['freepbx_username'],'pwd' => $selected_server['freepbx_password']));							
		}
		
		$opts = array('http' =>  
			array(  
					'method'  => 'POST',  
					'content' => $postfields,  
				)
			);  
		$context  = stream_context_create($opts);  
		$input = file_get_contents($path,false,$context);
		return $input;
	}
	
	/* A function to display users in a conference room for remoteparticular server */
	function displayUsersRemote_conf($selected_server, $local_server,$input){
		//Display Users in the Conference -->
		getRemoteConferenceUsers($selected_server, $local_server,$input);		

		/* The tail part of the page*/
		?>
				<!--</form>-->
			</body>
		</html>
		<?php
	}
	
	/* A function to get remote conference room users*/
	function getRemoteConferenceUsers($selected_server,$local_server,$input){
		/* Constructing a POST request */			
		$path = $selected_server['freepbx_url']."admin/config.php";
		$postfields = null;
		
		if (isset($_REQUEST['connect_to_conf']) && isset($_REQUEST['connect_to_num']) && $_REQUEST['connect_to_num']!=""){
			$postfields = "sent";
		}
		else if (isset($_REQUEST['conf_num']) && isset($_REQUEST['display_users']) && !isset($_REQUEST['operation'])){
			$postfields = "sent";
		}
		else if(isset($_REQUEST['operation']) ){
			$postfields = "sent";				
		}
		
		if($postfields!=null){
			if(strstr($input,"USERS")){	
				/* Check if there are any records */
				if(strstr($input,"CLID"))
				{
					$xmldata = xmlDecodeConferrence($input);
					formatRemoteConferrenceUsers($xmldata,$selected_server, $local_server);
				}else{
					?>
		            	<div id="pbxcu_notify">
		            		<?php echo  "There are no users in this conference!"; ?>
		            	</div>
		           	<?php 
				}	
			}else{
				?>
		            <div id="pbxcu_notify">
		            	<?php echo  "This conference has not reponded. Please try again later, or select another conference!"; ?>
		            </div>
		        <?php 
			}
		}	
	}
	
	/* Format the remote server users  for conferrence module */
	function formatRemoteConferrenceUsers($xmldata,$selected_server, $local_server){
		$path= $local_server['freepbx_url']."admin/config.php?type=tool&display=conference_bridge";
		?>
			<h5>Users List</h5>
			<table class="pbxcu_conf_tbl">
				<tr>
					<th>Phone/Extension</th>
					<th>Caller Id</th>
					<th>Mode</th>
					<th>Mute/UnMute</th>
					<th>Disconnect</th>
			  </tr>	
		<?php
		
		/* If there is more than one record */
		if(!($xmldata['USER']['EXT'])){
   			foreach ($xmldata['USER'] as $user){
   				remoteUsers($user,$path,$selected_server, $local_server);
   			}
		}	  
   		else{
   			remoteUsers($xmldata['USER'],$path,$selected_server, $local_server);
		}   	
	}
	
	/* A function to display the online users */
	function remoteUsers($user,$path,$selected_server,$local_server){
		$path_local = $local_server['freepbx_url']."admin/config.php?type=tool&display=conference_bridge";
		?>			
			<tr align="center">
   				<td align="center"><?php echo $user['EXT']; //Phone/Ext ?></td> 
   				<td align="center"><?php echo $user['CLID']; //Caller Id ?></td>
   				<td align="center"><?php echo $user['MODE']; //Mode - Muted/UnMuted ?></td> 
   				<?php
   				if($user['MODE']=="Muted"){ //Muted - So display UnMute link
   					?>
   					<td align="center"><a href="<?php echo $path."&server_list=". $selected_server["server_ip"] ."&display_users=true&conf_num=".$user['CNUM'] ."&operation=unmute&user_id=".$user['CLID']; ?>" >UnMute</a></td>
   					<?php	
   				}else if($user['MODE']=="UnMuted"){ //UnMuted - So display Mute link
	   			?>
	   				<td align="center"><a href="<?php echo $path."&server_list=". $selected_server["server_ip"] ."&display_users=true&conf_num=".$user['CNUM'] ."&operation=mute&user_id=".$user['CLID']; ?>" >Mute</a></td>
	   				<?php
	   			}
	   			?>
   				<td align="center"><a href="<?php echo $path."&server_list=".$selected_server["server_ip"] ."&display_users=true&conf_num=".$user['CNUM'] ."&operation=kick&user_id=".$user['CLID']; ?>" >Disconnect</a></td>
   			</tr>
		<?php 	
	}
		
	/* A function to construct and send users xml */	
	function sendRemoteConferrenceUsers(){
		if (isset($_REQUEST['connect_to_conf']) && isset($_REQUEST['connect_to_num']) && $_REQUEST['connect_to_num']!=""){
			connectUser($_REQUEST['connect_to_conf'], $_REQUEST['connect_to_num']);
			sleep(4);
			makeUserXML($_REQUEST['connect_to_conf']);
		}
		else if (isset($_REQUEST['conf_num']) && isset($_REQUEST['display_users']) && !isset($_REQUEST['operation'])){
			makeUserXML($_REQUEST['conf_num']);
		}
		else if(isset($_REQUEST['operation']) ){
			performOperation($_REQUEST['conf_num'],$_REQUEST['operation'], $_REQUEST['user_id']);
			sleep(2);
			makeUserXML($_REQUEST['conf_num']);			
		}
	}
	
	/* A function to make user xml */
	function makeUserXML($num){
		$result  = getUsers($num);
		?>
   			<USERS>
			<?php   
   			foreach ($result as $key => $value){
			?>
   				<USER>
   					<CLID><?php echo  $result[$key][0]; ?></CLID>
   					<CNUM><?php echo  $result[$key][2]; ?></CNUM>
   					<MODE><?php echo  $result[$key][3]; ?></MODE>
   					<EXT><?php echo  $result[$key][5]; ?></EXT>   					
  				</USER>
   			<?php
  			}	
			?>
			</USERS>
			<?php
	        exit;
	}
	
	/* A function to construct and send xml */	
	function sendRemoteConferrence(){
		$db_result = mysql_query("select exten, description from meetme order by exten");	
		$i=0;
		while ($row = mysql_fetch_array($db_result)) {
			$result[$i]= $row;
			$i++;
		}
		//Generate HTML conference room table
		if($db_result != null){ 
			
			?>
   			<CONFERRENCES>
			<?php   
   			foreach ($result as $key => $value){
			?>
   				<CONFERRENCE>
   					<EXT><?php echo  $result[$key][0]; ?></EXT>
   					<STATUS><?php echo getConfRoomStatus($result[$key][0]); ?></STATUS>
   					<DESC><?php echo $result[$key][1]; ?></DESC>
  				</CONFERRENCE>
   			<?php
  			}	
			?>
			</CONFERRENCES>
			<?php
	        exit;
		} 
	}
		
	/* A function to get server list from DB*/
	function getServerList(){	
		$name = mysql_query("select * from servers where server_type='iPax Server'");
		$i=0;
		while($entry = mysql_fetch_assoc($name)){
		 $result[$i] = $entry;
		 $i++;
		}
		return $result;	
	}
	
	/* A function to return the current freepbx approot */
	function getUrl_conf(){
		$url_array = explode("admin/config.php",curPageURL());
		$fbx_local = $url_array[0];
		return $fbx_local;
	}	
	
	/* A function to  get the local server */
	function getHost_conf($server_ip,$fbx_local){
		 $name = mysql_query("select * from servers where server_type='iPax Server' and server_ip='".$server_ip."';");
		 $i = 0;
		 while($entry = mysql_fetch_assoc($name)){
		 	$result[$i] = $entry;
		 	$i++;
		 }
		 
		 /*If the server is not found in the table*/
		 if($result[0] == null){	 	
		 	$result[0]['freepbx_url'] = $fbx_local;
		 	$result[0]['server_ip'] = $_SERVER['SERVER_NAME'];
		 }	
		return $result[0];	
	}
		
	/* A function to get local conference rooms */
	function getLocalConferenceList($selected_server,$local_server) {
		$db_result = mysql_query("select exten, description from meetme order by exten");	
		$i = 0;
		while ($row = mysql_fetch_array($db_result)) {
			$result[$i]= $row;
			$i++;
		}
		//Generate HTML conference room table
		if($db_result != null){ 
			// At least one conference exist
			?>
				<table class="pbxcu_conf_tbl">
					<th>Conference Room</th><th>Status</th><th>Phone/Extension Number</th><th>Connect</th>
			<?php
		
			$path_local = $local_server['freepbx_url']."admin/config.php?type=tool&display=conference_bridge";
			foreach ( $result as $key => $value ) {
			?>	
									
					<tr align='center'>
						<form action='<?php echo $path_local; ?>' method='post'>
						<input type='hidden' name='connect_to_conf' value='<?php echo $result[$key][0]; ?>' >	
						<input type='hidden' name='server_list' value='<?php echo $selected_server['server_ip']; ?>' >
						<td align='center'>
								<a href="<?php echo $path_local."&server_list=".$selected_server['server_ip']."&display_users=true&conf_num=". $result[$key][0]; ?>" > <?php echo $result[$key][0] .":". $result[$key][1]; ?></a>
						</td>
						  <td align='center'><?php echo  getConfRoomStatus($result[$key][0]); ?></td>	
						<td align='center'><input type='text' name='connect_to_num' /><font class="pbxcu_conf_err">*</font></td>
						<td align='center'><input type='submit' value='Connect' /></td>
						</form>
					</tr>
					
			<?php
			}
			?>	
				</table>
			<?php
		}else{
			//No Conference defined in this server
			?>
				<font color='#EA5D16' size='2pt'>No conferences defined in this server!</font>
			<?php
		}			
	}
	
	/* A function to get remote conference rooms */
	function getRemoteConferenceList($selected_server,$local_server) {
		/* Constructing a POST request */			
		$path = $selected_server['freepbx_url']."admin/config.php";
		$postfields = http_build_query( array ('type' => 'tool', 'display' => 'conference_bridge', 'remote' => 'conf', 'username' => $selected_server['freepbx_username'],'pwd' => $selected_server['freepbx_password']));  
		$opts = array('http' =>  
			array(  
					'method'  => 'POST',  
					'content' => $postfields,  
				)
				  
			);  
		$context  = stream_context_create($opts);  
		$input = file_get_contents($path,false,$context);
		
		if(strstr($input,"CONFERRENCES")){	
			/* Check if there are any records */
			if(strstr($input,"EXT"))
			{
				$xmldata = xmlDecodeConferrence($input);
				formatRemoteConferrence($xmldata,$selected_server,$local_server);
			}else{
				?>
	            	<div id="pbxcu_notify">
	            		<?php echo  "There are no conferences on this server!"; ?>
	            	</div>
	           	<?php 
			}	
		}else{
			?>
	            <div id="pbxcu_notify">
	            	<?php echo  "This server has not reponded. Please try again later, or select another server!"; ?>
	            </div>
	        <?php 
		}		
	}
	
	/* Format the remote server for conferrence module */
	function formatRemoteConferrence($xmldata,$selected_server,$local_server){
		// At least one conference exist
		?>
		<table class="pbxcu_conf_tbl">
			<th>Conference Room</th><th>Status</th><th>Phone/Extension Number</th><th>Connect</th>
		<?php
			$path = $local_server['freepbx_url']."admin/config.php?type=tool&display=conference_bridge";
		
		
		/* If there is more than one record */
		if(!($xmldata['CONFERRENCE']['EXT'])){
   			foreach ($xmldata['CONFERRENCE'] as $conf){
   				remoteRooms($conf,$path,$selected_server);
   			}
		}	  
   		else{
   			remoteRooms($xmldata['CONFERRENCE'],$path,$selected_server);   			
		}   						
		?>
		</table>
		<?php		 
	}
	
	/* A function to display the avallablble conferrences */
	function remoteRooms($conf,$path,$selected_server){		
		?>	
		<form action='<?php echo $path; ?>' method='post'>
			<tr align='center'>
				<td>
					<input type='hidden' name='server_list' value='<?php echo $selected_server['server_ip']; ?>' >
					<input type='hidden' name='connect_to_conf' value='<?php echo $conf['EXT']; ?>' >
					<a href="<?php echo $path."&server_list=". $selected_server['server_ip'] ."&display_users=true&type=tool&display=conference_bridge&conf_num=". $conf['EXT']; ?>" > <?php echo $conf['EXT'] .":". $conf['DESC']; ?> </a>
				</td>
				<td><?php echo  $conf['STATUS']; ?></td>
				<td><input type='text' name='connect_to_num' /><font class="pbxcu_conf_err">*</font></td>
				<td><input type='button' value='Connect' onClick='this.form.submit()'/></td>
			</tr>
		</form>
		<?php
	}	
	
	/* A function to un serialize the xml sent by remote servers*/
	function xmlDecodeConferrence($input){
		require_once('XML_Parser.php');
		require_once('XML_Unserializer.php');
	
		$xml = new xml_unserializer;
		$xml->unserialize($input);
		$xmldata = $xml->getUnserializedData();
		return $xmldata;	
	}
	
	/* A function to get conference room status */
	function getConfRoomStatus($room){
		$as = new AGI_AsteriskManager();
		$res = $as->connect();
		//$res = $as->Command('meetme list '.$room);
		$res = $as->Command('confbridge list '.$room);
			
		$res = explode("\n",$res['data']);
		unset($res[0]); //remove the Priviledge Command line
		$res = implode("\n",$res);
		$res = explode(" ",$res);
		$as->disconnect();	
		if($res[0]=="User"){
			return "In-Use";
		}else if($res[0]=="No"){
			return "Available";	
		}else {
			return "In-Use";	
		}


	}
	
	/* A function to get local users of a given conference */
	function getLocalConferenceUsers($conf_num){
		
		$userList = getUsers($conf_num);	
		if(count($userList)==0){ 		
			//No users in this conference
		?>	
			
			<font class="pbxcu_conf_err">There are no users in this conference! </font>	
		<?php
		}else{
			?>
			<h5>Users List</h5>
			<table class="pbxcu_conf_tbl">
				<tr>
					<th>Phone/Extension</th>
					<!--<th>Caller Id</th>--!>
					<th>Mode</th>
					<th>Mute/UnMute</th>
					<th>Disconnect</th>
				</tr>	
			<?php
			foreach ( $userList as $key => $value ) {					
   				?>
   				<tr align="center">
   				<td align="center"><?php echo $userList[$key][5]; //Phone/Ext ?></td> 
   				<!--<td align="center"><?php echo $userList[$key][0]; //Caller Id ?></td> --!>
   				<td align="center"><?php echo $userList[$key][3]; //Mode - Muted/UnMuted ?></td> 
   				<?php
   				if($userList[$key][3]=="Muted"){ //Muted - So display UnMute link
   					?>
   					<td align="center"><a href="<?php echo $PHP_SELF ."?server_list=". $_REQUEST["server_list"] ."&display_users=true&type=tool&display=conference_bridge&conf_num=". $userList[$key][2] ."&operation=unmute&user_id=". $userList[$key][0]; ?>" >UnMute</a></td>
   				<?php	
   				}else if($userList[$key][3]=="UnMuted"){ //UnMuted - So display Mute link
   					?>
   					<td align="center"><a href="<?php echo $PHP_SELF ."?server_list=". $_REQUEST["server_list"] ."&display_users=true&type=tool&display=conference_bridge&conf_num=". $userList[$key][2] ."&operation=mute&user_id=". $userList[$key][0]; ?>" >Mute</a></td>
   				<?php
   				}
   				?>
   				<td align="center"><a href="<?php echo $PHP_SELF ."?server_list=". $_REQUEST["server_list"] ."&display_users=true&type=tool&display=conference_bridge&conf_num=". $userList[$key][2] ."&operation=kick&user_id=". $userList[$key][0]; ?>" >Disconnect</a></td>
   				</tr>
			<?php
			}
			?>
			</table>
			<?php
		} 			
	}
	
	/* A function to get the list of users in a given conference */
	function getUsers($confno) {
		
		$as = new AGI_AsteriskManager();
		$res = $as->connect();
		//$res = $as->Command('meetme list '.$confno);
		  $res = $as->Command('confbridge list '.$confno);
		$line= split("\n", $res['data']);	
		$nbuser=0;		
		//foreach ($line as $myline){

		for ($lineNumber=3;$lineNumber<count($line)-1;$lineNumber++){
			$myline=$line[$lineNumber];
			$linevalue= preg_split("/[\s,]+/", $myline);
								
			$meetmechannel [$nbuser][0] = $linevalue[0];
			$meetmechannel [$nbuser][1] = $linevalue[6];
			$meetmechannel [$nbuser][2] = $confno;
			$meetmechannel [$nbuser][5] = trim(substr($myline,82,15));//$linevalue[3]; //$linevalue[4]." ".$linevalue[3];
			$pos = strpos($myline, 'Muted');
			$muteStatus=trim(substr($myline,99,5));
						
			if ($muteStatus==="No") $meetmechannel [$nbuser][3] = 'UnMuted';
			else $meetmechannel [$nbuser][3] = 'Muted';			
				
			$pos = strpos($myline, 'Admin');
			if ($pos===false) $meetmechannel [$nbuser][4] = 'User';
			else $meetmechannel [$nbuser][4] = 'Admin';			
				
			$nbuser++;
		}
		
		/*
			[0] => User
		    [1] => #:
		    [2] => 1
		    [3] => 
		    [4] => Channel:
		    [5] => IAX2/areskiax@areskiax-2
		    [6] => 
		    [7] => 
		    [8] => (Admn
		    [9] => Muted)
		    [10] => (unmonitored)
			
			</br>Array
			(
				[0] => User #: 1  Channel: IAX2/areskiax@areskiax-2   (Admn Muted) (unmonitored)
				[1] => User #: 2  Channel: SIP/kphone-b15c    (unmonitored)
				[2] => 2 users in that conference.
				[3] => 
			)
		*/
		
		$as->disconnect();
		return $meetmechannel;
		
	}
	
	/* A function to perform mute/unmute/disconnect users from a given conference */
	function performOperation($confno, $operation, $userId){
		$as = new AGI_AsteriskManager();
		$res = $as->connect();
		//$res = $as->Command('meetme '.$operation.' '.$confno.' '.$userId);
		$res = $as->Command('confbridge '.$operation.' '.$confno.' '.$userId);
 		$as->disconnect();
		sleep(1);
	}
	
	/* A function to connect user to a given conference */
	function connectUser($conf, $invite_num) {
		
		//Outcall defaults
		/*		
		define ("CHAN_TYPE", "Local"); //Use Local to let dialplan decide which chan
		define ("OUT_CONTEXT", "from-internal"); //Select a context to place the call from
		define ("OUT_PEER", ""); // Use this if not using CHAN_TYPE Local
		define ("OUT_CALL_CID", "Conference <1010>"); // Caller ID for Invites
		*/

		$as = new AGI_AsteriskManager();

		$res = $as->connect();
		if (!$res){ echo 'Error connecting to the manager!'; exit();}
		
		//Setting conference params
		$context = "from-internal";
		//$callerid = "Welcome <".$conf.">";	
		$callerid = "0773598730";
		$timeout = 60000; //60 seconds
		$account = '';
		//$application = MeetMe;
		$application = "ConfBridge";
		//$data = $conf."|";	//set in order to aviod asking conference number to connect
                $data = $conf;
		

		//Get the conference options from database
		$opts = '';
		$db_result = mysql_query("select options from meetme where exten='".$conf."'");	
		while ($row = mysql_fetch_array($db_result)) {
			$opts .= $row[0];
		}
		$new_opts = str_replace("i","I",$opts);
		//$data .= $new_opts;
		//$data .= $new_opts;
		
		$priority = 1;	
		
		//Setting variables - Phone number format => 554653(#4)837(#6)8362
        	$numArray = preg_split("/(\(#\d*\))/", $invite_num, -1, PREG_SPLIT_DELIM_CAPTURE);
        
		//print_r($numArray);
		
		$number = $numArray[0];
		$number = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $number);
		
		//This function has been added by Bathiya Tennakoon to differentiate dial patern from normal outbound route
		//print_r($number);
		//-------------------------------------------
		if (strlen($number)==11){
 		//print_r($number);
		$number="7".$number;
		}
		//print_r($number);
		//-------------------------------------------

        	//setting the channel
        
        //$channel = "SIP/".$number;
        $channel = "Local/".$number."@from-internal";

	 $exten = $number;
        		//Optional Variables
        $EXT1 = "";
		$EXT2 = "";
		$EXT3 = "";
		$EXT4 = "";
		$EXT5 = "";
		$EXT6 = "";
		$EXT7 = "";

		$EXT1W = "";
		$EXT2W = "";
		$EXT3W = "";
		$EXT4W = "";
		$EXT5W = "";
		$EXT6W = "";
		$EXT7W = "";
		
		//$EXT1W
        if (count($numArray) > 1){
			 $waitTime1 = substr($numArray[1], 2, strlen($numArray[1])-3);
        	 if($waitTime1 != ""){
        	 	$EXT1W = $waitTime1;
        	 }	 
        }
		
        //$EXT1
        if (count($numArray) > 2){
        	$EXT1 = $numArray[2];
        }
		
        //$EXT2W
        if (count($numArray) > 3 ){
        	$waitTime2 = substr($numArray[3], 2, strlen($numArray[3])-3);
        	if($waitTime2 != ""){
        	 	$EXT2W = $waitTime2;
        	 }	
        }  
        
		//$EXT2
        if (count($numArray) > 4){
            $EXT2 = $numArray[4];
		}

		//$EXT3W
		if (count($numArray) > 5 ){
        	$waitTime3 = substr($numArray[5], 2, strlen($numArray[5])-3);
        	if($waitTime3 != ""){
        	 	$EXT3W = $waitTime3;
        	 }	
        }  
        
		//$EXT3
        if (count($numArray) > 6){
            $EXT3 = $numArray[6];
		}
	
		//$EXT4W
		if (count($numArray) > 7 ){
        	$waitTime4 = substr($numArray[7], 2, strlen($numArray[7])-3);
        	if($waitTime4 != ""){
        	 	$EXT4W = $waitTime4;
        	 }	
        }  
        
		//$EXT4
        if (count($numArray) > 8){
            $EXT4 = $numArray[8];
		}
		
		//$EXT5W
		if (count($numArray) > 9 ){
        	$waitTime5 = substr($numArray[9], 2, strlen($numArray[9])-3);
        	if($waitTime5 != ""){
        	 	$EXT5W = $waitTime5;
        	 }	
        }  
        
		//$EXT5
        if (count($numArray) > 10){
            $EXT5 = $numArray[10];
		}

		//$EXT6W
		if (count($numArray) > 11 ){
        	$waitTime6 = substr($numArray[11], 2, strlen($numArray[11])-3);
        	if($waitTime6 != ""){
        	 	$EXT6W = $waitTime6;
        	 }	
        }  
        
		//$EXT6
        if (count($numArray) > 12){
            $EXT6 = $numArray[12];
		}
	
		//$EXT7W
		if (count($numArray) > 13 ){
        	$waitTime7 = substr($numArray[13], 2, strlen($numArray[13])-3);
        	if($waitTime7 != ""){
        	 	$EXT7W = $waitTime7;
        	 }	
        }  
        
		//$EXT7
        if (count($numArray) > 14){
            $EXT7 = $numArray[14];
		}
		
		
		/*
		;This macro will handle IVR menus in the remote end by sending DTMF
		;The variables for the macro will be set by Originate command
		;context.Variable = "ZED_EXT1=123|ZED_EXT1W=2|ZED_EXT2=456|ZED_EXT2W=3 ...";
		
		[macro-zed_ext_handler]
		exten => s,1,Gotoif($["${ARG1}"=""]?22:2)
		exten => s,2,Wait(${ARG2})
		exten => s,3,SendDTMF(${ARG1})
		
		exten => s,4,Gotoif($["${ARG3}"=""]?22:5)
		exten => s,5,Wait(${ARG4})
		exten => s,6,SendDTMF(${ARG3})
		
		exten => s,7,Gotoif($["${ARG5}"=""]?22:8)
		exten => s,8,Wait(${ARG6})
		exten => s,9,SendDTMF(${ARG5})
		
		exten => s,10,Gotoif($["${ARG7}"=""]?22:11)
		exten => s,11,Wait(${ARG8})
		exten => s,12,SendDTMF(${ARG7})
		
		exten => s,13,Gotoif($["${ARG9}"=""]?22:14)
		exten => s,14,Wait(${ARG10})
		exten => s,15,SendDTMF(${ARG9})
		
		exten => s,16,Gotoif($["${ARG11}"=""]?22:17)
		exten => s,17,Wait(${ARG12})
		exten => s,18,SendDTMF(${ARG11})
		
		exten => s,19,Gotoif($["${ARG13}"=""]?22:20)
		exten => s,20,Wait(${ARG14})
		exten => s,21,SendDTMF(${ARG13})
		
		exten => s,22,NoOp()
		
		;Calling the macro
		exten => _XXXXXXX,1,Dial(ZAP/g0/${EXTEN},,wWM(zed_ext_handler^${ZED_EXT1}^${ZED_EXT1W}^${ZED_EXT2}^${ZED_EXT2W}^${ZED_EXT3}^${ZED_EXT3W}^${ZED_EXT4}^${ZED_EXT4W}^${ZED_EXT5}^${ZED_EXT5W}^${ZED_EXT6}^${ZED_EXT6W}^${ZED_EXT7}^${ZED_EXT7W}))
		
		*/
		
        //$variable = "ZED_EXT1=".$EXT1."|ZED_EXT1W=".$EXT1W."|ZED_EXT2=".$EXT2."|ZED_EXT2W=".$EXT2W."|ZED_EXT3=".$EXT3."|ZED_EXT3W=".$EXT3W."|ZED_EXT4=".$EXT4."|ZED_EXT4W=".$EXT4W."|ZED_EXT5=".$EXT5."|ZED_EXT5W=".$EXT5W."|ZED_EXT6=".$EXT6."|ZED_EXT6W=".$EXT6W."|ZED_EXT7=".$EXT7."|ZED_EXT7W=".$EXT7W;
	     $variable = "ZED_EXT1=".$EXT1.",ZED_EXT1W=".$EXT1W.",ZED_EXT2=".$EXT2.",ZED_EXT2W=".$EXT2W.",ZED_EXT3=".$EXT3.",ZED_EXT3W=".$EXT3W.",ZED_EXT4=".$EXT4.",ZED_EXT4W=".$EXT4W.",ZED_EXT5=".$EXT5.",ZED_EXT5W=".$EXT5W.",ZED_EXT6=".$EXT6.",ZED_EXT6W=".$EXT6W.",ZED_EXT7=".$EXT7.",ZED_EXT7W=".$EXT7W;
		
		//Initiating the call
		$res = $as->Originate ($channel, $exten, $context, $priority, $timeout, $number, $variable, $account, $application, $data);
		$as->disconnect();
		
		return $res[0]=="Success"?"success":"failed";
	}

	/* A function to  get the local server */
	function getLocalhost_conf($server_ip,$fbx_local){
		$name = mysql_query("select * from servers where server_type='iPax Server' and server_ip='".$server_ip."';");
		 $i = 0;
		 while($entry = mysql_fetch_assoc($name)){
		 	$result[$i] = $entry;
		 	$i++;
		 }
		 
		 /*If the server is not found in the table*/
		 if($result[0] == null){	 	
		 	$result[0]['freepbx_url'] = $fbx_local;
		 	$result[0]['server_ip'] = $_SERVER['SERVER_NAME'];
		 }	
		return $result[0];	
	}
	
	/* A function to get a server by a given ip*/
	function getServerByIP_conf($server_ip, $fbx_local){
		$name = mysql_query("select * from servers where server_type='iPax Server' and server_ip='".$server_ip."';");
		 $i=0;
		 while($entry = mysql_fetch_assoc($name)){
		 	$result[$i] = $entry;
		 	$i++;
		 }
		 	
		/*If the server is not found in the table*/
		 if($result[0] == null){	 	
		 	$result[0]['freepbx_url'] = $fbx_local;
		 	$result[0]['server_ip'] = $_SERVER['SERVER_NAME'];
		 }	
		return $result[0];
	}
?>
