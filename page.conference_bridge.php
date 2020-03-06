<!--
	Author: Lasantha Aluthge & Supun Dewaraja
	PBXCU Implementation Phase
	Zone24x7  
-->
<?php
if(isset($_REQUEST['remote']) && $_REQUEST['remote']=="conf"){
	/* Construct and send conferrence xml */
	sendRemoteConferrence();	
}
else if(isset($_REQUEST['remote']) && $_REQUEST['remote']=="users"){
	/* Construct and send conferrence users xml */
	sendRemoteConferrenceUsers();
}	
else{
	
	$default = false;
	 
	if(!isset($_REQUEST['server_list'])){
		$default = true;
	}
	
	$fbx_local = getUrl_conf();
	/* Get the local server and selected server */
	$local_server = getLocalhost_conf($_SERVER['SERVER_NAME'],$fbx_local);
	$selected_server = getServerByIP_conf($_REQUEST['server_list'],$fbx_local);
	$local_path = $local_server['freepbx_url']."admin/config.php";
	
	/* Display the header of the page*/
	displayHeader_conf();
	
	/* Display the dropdown*/
	displayServers_conf($selected_server,$default,$default);
	
	/* Perform operations before rendering of conferences to solve update issue */
	doOperations_conf();
	
	/* Perform remote operations before rendering of conferences to solve update issue */
	$input = doRemoteOperations_conf($selected_server, $local_server);
		
	/* Display conference rooms for a particular server */
	displayRooms_conf($selected_server,$local_server,$default);
			
	if($local_server['server_ip'] == $_REQUEST['server_list']){		
		/* Display users in a conference room for a particular server */
		displayUsers_conf();		
	}
	else{		
		/* Send request to remote servers to process the request for users*/
		displayUsersRemote_conf($selected_server, $local_server,$input);
	}
}
?>
<br/><br/>

<table id="confHelp" border="0">
	<tr>
		<td colspan="2">
		<table style="border:1px solid #EA5D16;">
			<tr>
				<td>
					<pre>Note: To enter passwords and extension number when initiating a call please use the following format:
XXXXXXX(#T)NNN [ e.g. 2111111(#3)1234(#3)5858 ]</pre>
				</td>
			</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td style="width:70px">
			XXXXXXX
		</td>
		<td>
			Phone number (e.g. 2111111)
		</td>
	</tr>
	<tr>
		<td style="width:70px">
			(#T)
		</td>
		<td>
			Wait time in seconds (e.g. 3)
		</td>
	</tr>
	<tr>
		<td style="width:70px">
			NNN 
		</td>
		<td>
			Extension to be dialed or password to be entered (e.g. 1234, 5858)
		</td>
	</tr>
</table>