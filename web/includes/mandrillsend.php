<?
// generic mail sender
	
	
function sendmailmandrill ($mandrill,$subject,$message2,$recipient,$msg_tags){ 
	
		global $apt_debug_mode; // check if running in global debug
	 
		// recipient is array of addresses
	
		$from_email = 'tom@apptitude.media';
		$from_name = 'Paper Plane';
		$from_website = 'www.throwtestapps.com';	  
		$recipient_array = array();  
		
		// debug
		
		if ($apt_debug_mode == 1){
			// debug mode, send to me
			array_push($recipient_array,array('email' => 'tom@apptitudemedia.co.uk','type' => 'to'));
			$from_name = 'Appthenticate DEBUG MODE';
			foreach($recipient as $theemail){
			    $message2 = ' <p>ORIG_TO '.$theemail.'</p>'.$message2; //debug
		    };
		}
		else {
			// live mode, add real recipients
			foreach($recipient as $theemail){ 
			    array_push($recipient_array,array('email' => $theemail,'type' => 'to'));
		    };
		};
		
		// end debug provision
				
	    $message = array(
        'html' => $message2,
        'subject' => $subject,
        'from_email' => $from_email, 
        'from_name' => $from_name,
        'headers' => array('Reply-To' => $from_email),
        'important' => false,
        'track_opens' => false,
        'track_clicks' => false,
        'auto_text' => true,
        'auto_html' => null,
        'inline_css' => null,
        'url_strip_qs' => null,
        'preserve_recipients' => null,
        'view_content_link' => null,
        'tracking_domain' => null,
        'signing_domain' => null,
        'return_path_domain' => null,
        'merge' => true,
        'merge_language' => 'mailchimp',
        'tags' => array($msg_tags),
        'metadata' => array('website' => $from_website)
	    ); 
  
	    $message['to'] =  $recipient_array;    
	    $async = false;
	    $result = $mandrill->messages->send($message, $async, $ip_pool, $send_at);
	    
		// success marker
	    if ($result[0]['status'] == "sent"){
		    $mailresult = 1;
	    }
	    else {
		    $mailresult = 0;
	    };
	return($mailresult);
};

?>