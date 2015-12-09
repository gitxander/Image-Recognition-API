<?php 
	header('Access-Control-Allow-Origin: *');


  if(isset($_FILES['file']['type'])) {
      $imageurl = $_FILES['file']['name'];
      $file = $_FILES['file']['tmp_name'];
      $remote_file = 'images/'.$_FILES['file']['name'];

      //ftp
      $ftp_server = 'raveteam.net';
      $ftp_user_name = 'zhacarias@raveteam.net';
      $ftp_user_pass = '#Tp(?ZWB3d;K';
      // set up basic connection
      $conn_id = ftp_connect($ftp_server) or die("Couldn't connect to $ftp_server");
      // login with username and password
      if(@$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass)) {
		  //if (ftp_chmod($conn_id, 664 , $file) !== false){
			  // upload a file
			  if (ftp_put($conn_id, $remote_file, $file, FTP_BINARY)) {
			   //echo "successfully uploaded'.$file\n";
			   $data = array('message' => 'Successfully uploaded','status' => 'completed');
				if(file_exists($remote_file)) {
					imageapi($data,$imageurl);
				}
			  } else {
			   $data = array('message' => 'There was a problem while uploading','status' => 'not completed');
			  }
		  //}else{ 
			 //$data = array('message' => 'Can\'t set permission','status' => 'not completed');
			 //echo json_encode($data);
		  //}
			  
        }
	  else{
		  $data = array('message' => 'Can\'t login to the server ','status' => 'not completed');
		  echo json_encode($data);
	  }
	  
	  // close the connection
	  ftp_close($conn_id);
    }
	
	
	
	
	
	
	function imageapi($data,$imagename){
      $url = 'https://camfind.p.mashape.com/image_requests';
  		$imageurl = 'http://raveteam.net/camfind/images/'.$imagename;
  		$headers =
  			array(
  			"X-Mashape-Key : YWSjOzwzSdmshDNJFburlOu7cRr0p1GmXaIjsn8zu7PH28372G ",
  			"Content-Type : application/x-www-form-urlencoded",
  			"Accept : application/json");
  		$fields =
  			array(
  			"focus[x]" => "480",
  			"focus[y]" => "640",
  			"image_request[altitude]" => "27.912109375",
  			"image_request[language]" => "en",
  			"image_request[latitude]" => "35.8714220766008",
  			"image_request[locale]" => "en_US",
  			"image_request[longitude]" => "14.3583203002251",
  			"image_request[remote_image_url]" => $imageurl);

  		//url-ify the data for the POST
  		$fields_string = "";
  		foreach($fields as $key=>$value) {
  			$fields_string .= $key.'='.$value.'&';
  		}
  		rtrim($fields_string, '&');

  		//open connection
  		$ch = curl_init();

  		//set the url, number of POST vars, POST data
  		curl_setopt($ch,CURLOPT_URL, $url);
  		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  		curl_setopt($ch,CURLOPT_POST, count($fields));
  		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
  		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
  		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  		//execute post
  		$result = curl_exec($ch);

  		//close connection
  		curl_close($ch);

  		$result = json_decode($result, true);
  		//echo '<pre>',print_r($result),'</pre>';
  		if($result['token'] != ""){
  				//echo $result['token'] . '<br />';

  				///////////////////////////////////
  				// SECOND REQUEST
  				///////////////////////////////////

  				//set POST variables
  				$url = "https://camfind.p.mashape.com/image_responses/" . $result['token'];
  				//echo $url;

  				$headers = array(
  				    'X-Mashape-Key: YWSjOzwzSdmshDNJFburlOu7cRr0p1GmXaIjsn8zu7PH28372G',
  				    'Accept: application/json'
  				); 

  				$start = microtime(true);
  				//open connection
  				$ch = curl_init();
  				do {
  				    //set the url
  				    curl_setopt($ch, CURLOPT_URL, $url);
  				    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  				    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  				    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  				    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
  				    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  				    curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 30);
 
  				    $result = json_decode(curl_exec($ch), true); 

  				} while((number_format((microtime(true) - $start), 2) < 20) && $result['status'] == 'not completed');
          $data['fruitname'] = $result['name'];
          $data['fruitstatus'] = $result['status'];
          
  				//echo '<pre>',print_r($result),'</pre> <br />';
  				//echo '<label> Fruit Name: </label> <code>'. $result['name'] . '</code>';
  		  curl_close($ch);

  		}
  		else {
			$data = array('message' => 'Token not found.. Please try again !','status' => 'not completed','imagename' => $imagename);
  		}
		echo json_encode($data);
    }
	