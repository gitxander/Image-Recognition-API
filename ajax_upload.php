<?php
	/**==================================
	* PROJECT   IMAGE RECOGNITION API
	* FILE      ajax_uplaod.php
	* VERSION   1.0
	* AUTHOR    Mark Kirshner Chico
	* EMAIL     zhacarias.snizer@gmail.com
	* DATE      2016-01-06 UTC
	* ==================================
	* You can copy this source code for your project
	* This is free to share
	* Just give credits to the author.
	* All rights reserved.
	*/

	header('Access-Control-Allow-Origin: *');

	require_once('config.php');

	class SearchImage
	{
		/**
		*  =======================
		*	File Name
		*  =======================
		*/
		private $filename;

		/**
		*  =======================
		*	Actual File Source / Path
		*  =======================
		*/
		private $source;

		/**
		*  =======================
		*	File Destination
		*	Remote folder
		*  =======================
		*/
		private $destination;

		/**
		*  =======================
		*	FTP HOST / SERVER
		*  =======================
		*/
		private $ftp_server;

		/**
		*  =======================
		*	FTP USERNAME
		*  =======================
		*/
		private $ftp_username;

		/**
		*  =======================
		*	FTP PASSWORD
		*  =======================
		*/
		private $ftp_password;

		/**
		*  =======================
		*	FTP CONNECTION ID
		*  =======================
		*/
		private $conn_id;



		/**
		*  =======================
		*	Constructor
		*  =======================
		*/
		public function __construct()
		{
			if (isset($_FILES['file']['type'])) {
				// File Information
				$this->filename = $_FILES['file']['name'];
				$this->source = $_FILES['file']['tmp_name'];
				$this->destination = IMAGEPATH . $this->filename;

				// FTP Information
				$this->ftp_server = FTP_SERVER;
				$this->ftp_username = FTP_USER;
				$this->ftp_password = FTP_PASS;

				// Start Connection
				return $this->ftp_con();
			}
		}

		/**
		*  =======================
		*	Start Connection
		*  =======================
		*/
		private function ftp_con()
		{
			$this->conn_id = ftp_connect($this->ftp_server) or die("Couldn\'t connect to $this->ftp_server");

			if (ftp_login($this->conn_id, $this->ftp_username, $this->ftp_password)) {
				
				if (ftp_put($this->conn_id, $this->destination, $this->source, FTP_BINARY)) {
					$data = array(
						'message' => 'Successfully uploaded',
						'status' => 'completed'
					);

					if (file_exists($this->destination)) {
						return $this->camfindconfig($this->filename);
					}
				} else {
					$data = array(
						'message' => 'There was a problem while uploading',
						'status' => 'not completed'
					);
					return $data;
				}
				
			} else {
				$data = array(
					'message' => 'Can\'t login to the server ',
					'status' => 'not completed'
				);
				return $data;
			}

			// close the connection
	 		ftp_close($this->conn_id);
		}

		/**
		*  ==============================
		*	Camfind Configuration Method
		*	@param [$data] - array
		*	@param [$imagename] - string
		*  ==============================
		*/
		private function camfindconfig($imagename)
		{
			$url = CAMFIND_REQUEST;
			$imageurl = BASEURL . IMAGEPATH . $imagename;
			$headers = array(
				"X-Mashape-Key : " .API_KEY ." ",
				"Content-Type : application/x-www-form-urlencoded",
				"Accept	: application/json"
			);
			
			$fields = array(
				"focus[x]" => "480",
				"focus[y]" => "640",
				"image_request[altitude]" => "27.912109375",
				"image_request[language]" => "en",
				"image_request[latitude]" => "35.8714220766008",
				"image_request[locale]" => "en_US",
				"image_request[longitude]" => "14.3583203002251",
				"image_request[remote_image_url]" => $imageurl
			);
						
			//url-ify the data for the POST
			$fields_string = "";
			foreach ($fields as $key=>$value) {
				$fields_string .= $key .'='. $value .'&';
			}
			rtrim($fields_string, '&');

			return $this->camfindrequest($url, $headers, $fields, $fields_string);
		}

		private function camfindrequest($url, $headers, $fields, $fields_string)
		{
			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, count($fields));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			//execute post
			$result = curl_exec($ch);

			//close connection
			curl_close($ch);

			$result = json_decode($result, true);

			if($result['token'] != ""){
				return $this->camfindresponse($result);
			} else {
				$data = array(
					'message' => 'Token not found.. Please try again !',
					'status' => 'not completed',
					'imagename' => $imagename
				);
				return json_encode($data);
			}
		}

		private function camfindresponse($data, $result)
		{	
			$data = [];
			$url = CAMFIND_RESPONSE . $result['token'];

			$headers = array(
				'X-Mashape-Key: '. API_KEY .' ',
				'Accept: application/json'
			);

			$start = microtime(true);
			
			$ch = curl_init();
			do {
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 30);

				$result = json_decode(curl_exec($ch), true); 

			} while( (number_format((microtime(true) - $start), 2) < 20) && $result['status'] == 'not completed' );
			
			$data['objectname'] = $result['name'];
			$data['objectstatus'] = $result['status'];
          
  		 	curl_close($ch);

			return json_encode($data);
		}

	}

	// Start App
	$app = new SearchImage();