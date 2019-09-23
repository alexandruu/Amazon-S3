<?php

	class Amazon
	{
		private $AccessKey, $SecretKey, $AwsRegion, $ServiceName, $AwsSignatureVersion, $Data, $Endpoint;
		
		private $CurlResponse, $CurlError, $CurlHeader, $CurlUrl, $Operation, $PathFile, $SignedHeaders, $CanonicalHeaders, $Scope;
		
		public function __construct($arg)
		{
			try
			{
				$NumeFunctie = __METHOD__ .": ";
				
				if ($InfPrd) 			$this->InfPrd = $InfPrd;
				if ($UserAutentificat) 	$this->UserAutentificat = $UserAutentificat;
				
				$AccessKey 		= $arg['AccessKey'];
				$SecretKey 		= $arg['SecretKey'];
				$AwsRegion 		= $arg['AwsRegion'];
				$ServiceName 	= $arg['ServiceName'];
				
				$this->setParametri($AccessKey, $SecretKey, $AwsRegion, $ServiceName);
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
		
		
		public function setParametri($AccessKey, $SecretKey, $AwsRegion, $ServiceName)
		{
			try
			{
				$NumeFunctie = __METHOD__ .": ";
				
				$this->AccessKey 			= ($AccessKey ? $AccessKey : 'DEFAULT_AccessKey');			
				$this->SecretKey 			= ($SecretKey ? $SecretKey : 'DEFAULT_SecretKey');			
				$this->AwsRegion 			= ($AwsRegion ? $AwsRegion : 'DEFAULT_AwsRegion');			 
				$this->ServiceName 			= ($ServiceName ? $ServiceName : 's3');				
				$this->AwsSignatureVersion 	= 'AWS4-HMAC-SHA256';
				$this->Data 				= gmdate('Ymd');
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
		
		
		private function setEndpoint($Bucket)
		{
			try
			{
				$NumeFunctie = __METHOD__ .": ";
				
				if (!$this->Operation)	throw new \EP(\EP::L($NumeFunctie.'\n"Operation" este gol!'));
				if (!$this->AwsRegion)	throw new \EP(\EP::L($NumeFunctie.'\n"AwsRegion" este gol!'));
				if (!$this->ServiceName)throw new \EP(\EP::L($NumeFunctie.'\n"ServiceName" este gol!'));
				if (!$Bucket)			throw new \EP(\EP::L($NumeFunctie.'\n"Bucket" este gol!'));
				
				switch($this->Operation)
				{
					case 'GeneratePresignedUrl': $this->Endpoint = "https://{$Bucket}.{$this->ServiceName}.{$this->AwsRegion}.amazonaws.com/"; break;
					case 'DeleteFile': $this->Endpoint = "https://{$Bucket}.{$this->ServiceName}.{$this->AwsRegion}.amazonaws.com/"; break;
					case 'UploadFile': $this->Endpoint = "https://{$Bucket}.{$this->ServiceName}.{$this->AwsRegion}.amazonaws.com/"; break;
					case 'DownloadFile': $this->Endpoint = "https://{$Bucket}.{$this->ServiceName}.{$this->AwsRegion}.amazonaws.com/"; break;
				}
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
	
		
		private function getSignatureAuthorizationHeader()
		{
			try
			{
				switch($this->Operation)
				{
					case "UploadFile": 				$RequestMethod = "PUT"; break;
					case "DownloadFile": 			$RequestMethod = "GET"; break;
					case "DeleteFile": 				$RequestMethod = "DELETE"; break;
				}
				
				$Timestamp 		= gmdate('Ymd\THis\Z');
				$Date 			= gmdate('Ymd');
				$FileContent 	= file_get_contents($this->PathFile);
				
				$this->CanonicalHeaders 						= array();
				$this->CanonicalHeaders['X-Amz-Date'] 			= $Timestamp;
				$this->CanonicalHeaders['Host'] 				= rtrim(str_replace("https://", "", $this->Endpoint),"/");
				$this->CanonicalHeaders['X-Amz-Content-Sha256'] = hash('sha256', $FileContent);				
				
				ksort($this->CanonicalHeaders);				
				
				// Canonical headers
				$CanonicalHeadersTmp = array();
				foreach($this->CanonicalHeaders as $key => $value)
					$CanonicalHeadersTmp[] = strtolower($key) . ":" . $value;
					
				// Signed headers
				$SignedHeaders = array();
				foreach($this->CanonicalHeaders as $key => $value)
					$SignedHeaders[] = strtolower($key);
					
				$this->SignedHeaders	= implode(";", $SignedHeaders);
				
				// Cannonical request 
				$CanonicalRequest 	= array();
				$CanonicalRequest[] = $RequestMethod;
				$CanonicalRequest[] = "/" . $this->PathFile;
				$CanonicalRequest[] = "";
				$CanonicalRequest[] = implode("\n", $CanonicalHeadersTmp);
				$CanonicalRequest[] = "";
				$CanonicalRequest[] = $this->SignedHeaders;
				$CanonicalRequest[] = hash('sha256', $FileContent);
				
				$CanonicalRequest = implode("\n", $CanonicalRequest);
				
				// AWS Scope
				$this->Scope 	= array();
				$this->Scope[] 	= $Date;
				$this->Scope[] 	= $this->AwsRegion;
				$this->Scope[] 	= $this->ServiceName;
				$this->Scope[] 	= "aws4_request";

				// String to sign
				$StringToSign 	= array();
				$StringToSign[] = "AWS4-HMAC-SHA256"; 
				$StringToSign[] = $Timestamp; 
				$StringToSign[] = implode('/', $this->Scope);
				$StringToSign[] = hash('sha256', $CanonicalRequest);
				$StringToSign 	= implode("\n", $StringToSign);
				
				// Signing key				
				$SigningKey = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', $this->ServiceName, hash_hmac('sha256', $this->AwsRegion, hash_hmac('sha256', $Date, 'AWS4' . $this->SecretKey, true), true), true), true);
				
				// Signature
				$Signature = hash_hmac('sha256', $StringToSign, $SigningKey);
				
				return $Signature;
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
				
		
		private function getAuthorizationHeader()
		{
			try
			{
				$Signature = $this->getSignatureAuthorizationHeader();
				
				if (!$this->SignedHeaders) 		throw new \EP(\EP::L($NumeFunctie.'\n"SignedHeaders" este gol!'));
				if (!$this->CanonicalHeaders) 	throw new \EP(\EP::L($NumeFunctie.'\n"CanonicalHeaders" este gol!'));
				if (!$this->Scope) 				throw new \EP(\EP::L($NumeFunctie.'\n"Scope" este gol!'));
				
				// Authorization
				$Authorization = array(
					'Credential='. $this->AccessKey . '/' . implode('/', $this->Scope),
					'SignedHeaders='. $this->SignedHeaders,
					'Signature='. $Signature
				);
				$Authorization = $this->AwsSignatureVersion . ' ' . implode( ',', $Authorization);

				// Curl headers
				$CurlHeader = array();
				$CurlHeader[] = 'Accept: */*';
				$CurlHeader[] = 'Accept-Encoding: gzip, deflate';
				$CurlHeader[] = 'Authorization: ' . $Authorization;
				$CurlHeader[] = 'Cache-Control: no-cache';
				$CurlHeader[] = 'Connection: keep-alive';
				
				foreach($this->CanonicalHeaders as $key => $value)
					$CurlHeader[] = $key . ": " . $value;
					
				$CurlHeader[] = 'cache-control: no-cache';
				
				return $CurlHeader;
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
		
		
		private function getSignatureQueryParameters($ExpirationTime)
		{
			try
			{
				$Timestamp 		= gmdate('Ymd\THis\Z');
				$Date 			= gmdate('Ymd');
				
				$CanonicalHeaders = array();
				$CanonicalHeaders['X-Amz-Algorithm'] 		= 'AWS4-HMAC-SHA256';
				$CanonicalHeaders['X-Amz-Credential'] 		= $this->AccessKey .'%2F'. $Date .'%2F'. $this->AwsRegion .'%2F'. $this->ServiceName .'%2Faws4_request';
				$CanonicalHeaders['X-Amz-Date'] 			= $Timestamp;
				$CanonicalHeaders['X-Amz-Expires'] 			= $ExpirationTime;
				$CanonicalHeaders['X-Amz-SignedHeaders'] 	= 'host';
				
				ksort($CanonicalHeaders);
				
				$CanonicalHeadersTmp = array();
				foreach($CanonicalHeaders as $key => $value)
					$CanonicalHeadersTmp[] = $key . "=" . $value;
					
				$CanonicalHeaders = implode("&", $CanonicalHeadersTmp);
					
				// Cannonical request 
				$CanonicalRequest 	= array();
				$CanonicalRequest[] = "GET";
				$CanonicalRequest[] = "/" . $this->PathFile;					
				$CanonicalRequest[] = $CanonicalHeaders;
				$CanonicalRequest[] = "host:daune-live.s3.eu-central-1.amazonaws.com";
				$CanonicalRequest[] = "";
				$CanonicalRequest[] = "host";
				$CanonicalRequest[] = "UNSIGNED-PAYLOAD";					
				$CanonicalRequest = implode("\n", $CanonicalRequest);
				
				// AWS Scope
				$Scope = array();
				$Scope[] = $Date;
				$Scope[] = $this->AwsRegion;
				$Scope[] = $this->ServiceName;
				$Scope[] = "aws4_request";

				// String to sign
				$StringToSign 	= array();
				$StringToSign[] = "AWS4-HMAC-SHA256"; 
				$StringToSign[] = $Timestamp; 
				$StringToSign[] = implode('/', $Scope);
				$StringToSign[] = hash('sha256', $CanonicalRequest);
				$StringToSign 	= implode("\n", $StringToSign);
				
				// Signing key				
				$SigningKey = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', $this->ServiceName, hash_hmac('sha256', $this->AwsRegion, hash_hmac('sha256', $Date, 'AWS4' . $this->SecretKey, true), true), true), true);
				
				// Signature
				$Signature = hash_hmac('sha256', $StringToSign, $SigningKey);

				return $Signature;
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
		
		
		public function deleteFile($Bucket,$PathFile)
		{
			try
			{
				$NumeFunctie = __METHOD__ .": ";
				
				$this->Operation 	= "DeleteFile";
				$this->PathFile 	= $PathFile;
				$this->setEndpoint($Bucket);
				
				if (!$this->Endpoint)	throw new \EP(\EP::L($NumeFunctie.'\n"Endpoint" nu a putut fi stabilit!'));
				
				$curl = curl_init();
				
				$this->CurlUrl					= $this->Endpoint . $PathFile;
				$this->CurlHeader 				= $this->getAuthorizationHeader($PathFile, $this->Operation);

				curl_setopt_array($curl, array(
					CURLOPT_URL 				=> $this->CurlUrl,
					CURLOPT_HEADER 				=> true,
					CURLOPT_RETURNTRANSFER 		=> true,
					CURLOPT_ENCODING	 		=> "",
					CURLOPT_MAXREDIRS 			=> 10,
					CURLOPT_TIMEOUT 			=> 30,
					CURLOPT_HTTP_VERSION 		=> CURL_HTTP_VERSION_1_1,
					CURLOPT_SSL_VERIFYPEER 		=> false,
					CURLOPT_CUSTOMREQUEST 		=> "DELETE",
					CURLOPT_POSTFIELDS 			=> file_get_contents($PathFile),
					CURLOPT_HTTPHEADER 			=> $this->CurlHeader,
				));

				$this->CurlResponse				= curl_exec($curl);
				$this->CurlError   				= curl_error($curl);
				
				curl_close($curl);
				
				$this->parseCurlReponse();
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
		
		
		public function uploadFile($Bucket,$PathFile)
		{
			try
			{
				$NumeFunctie = __METHOD__ .": ";
				
				$this->Operation	= "UploadFile";
				$this->PathFile 	= $PathFile;
				$this->setEndpoint($Bucket);
				
				if (!$this->Endpoint)	throw new \EP(\EP::L($NumeFunctie.'\n"Endpoint" nu a putut fi stabilit!'));
				
				$curl 							= curl_init();
				$this->CurlUrl					= $this->Endpoint . $PathFile;
				$this->CurlHeader 				= $this->getAuthorizationHeader($PathFile, $this->Operation);
				
				curl_setopt_array($curl, array(
					CURLOPT_URL 				=> $this->CurlUrl,
					CURLOPT_HEADER 				=> true,
					CURLOPT_RETURNTRANSFER 		=> true,
					CURLOPT_ENCODING	 		=> "",
					CURLOPT_MAXREDIRS 			=> 10,
					CURLOPT_TIMEOUT 			=> 30,
					CURLOPT_HTTP_VERSION 		=> CURL_HTTP_VERSION_1_1,
					CURLOPT_SSL_VERIFYPEER 		=> false,
					CURLOPT_CUSTOMREQUEST 		=> "PUT",
					CURLOPT_POSTFIELDS 			=> file_get_contents($PathFile),
					CURLOPT_HTTPHEADER 			=> $this->CurlHeader,
				));

				$this->CurlResponse				= curl_exec($curl);
				$this->CurlError   				= curl_error($curl);
				
				curl_close($curl);
				
				$this->parseCurlReponse();
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
		
		
		public function downloadFile($Bucket,$PathFile)
		{
			try
			{
				$NumeFunctie = __METHOD__ .": ";
				
				$this->Operation 	= "DownloadFile";
				$this->PathFile 	= $PathFile;
				$this->setEndpoint($Bucket);
				
				if (!$this->Endpoint)	throw new \EP(\EP::L($NumeFunctie.'\n"Endpoint" nu a putut fi stabilit!'));
				
				$curl 							= curl_init();
				$this->CurlUrl					= $this->Endpoint . $PathFile;
				$this->CurlHeader 				= $this->getAuthorizationHeader($PathFile, $this->Operation);
				
				curl_setopt_array($curl, array(
					CURLOPT_URL 				=> $this->CurlUrl,
					CURLOPT_HEADER 				=> true,
					CURLOPT_RETURNTRANSFER 		=> true,
					CURLOPT_ENCODING	 		=> "",
					CURLOPT_MAXREDIRS 			=> 10,
					CURLOPT_TIMEOUT 			=> 30,
					CURLOPT_HTTP_VERSION 		=> CURL_HTTP_VERSION_1_1,
					CURLOPT_SSL_VERIFYPEER 		=> false,
					CURLOPT_CUSTOMREQUEST 		=> "GET",
					CURLOPT_POSTFIELDS 			=> "",
					CURLOPT_HTTPHEADER 			=> $this->CurlHeader,
				));

				$this->CurlResponse				= curl_exec($curl);
				$this->CurlError   				= curl_error($curl);
				
				curl_close($curl);
				
				$Response = $this->parseCurlReponse();
				
				if (count($Response))
					return $Response;
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
		
		
		private function parseCurlReponse()
		{
			try
			{
				$NumeFunctie = __METHOD__ .": ";
				
				if ($this->CurlError)
					throw new \Exception("La momentul apelarii serviciului <b>Amazon {$this->ServiceName}</b> a intervenit o eroare: \"{$EroareCurl}\".");
				
				list($Header, $Body)			= explode("\r\n\r\n", $this->CurlResponse, 2);
				
				$HttpCodeHeader                 = preg_match("/HTTP\/\d\.\d\s*\K[\d]+/", substr($Header,0,30),$matches); 	$HttpCodeHeader = $matches[0];
				$HttpCodeBody                 	= preg_match("/HTTP\/\d\.\d\s*\K[\d]+/", substr($Body,0,30),$matches); 		$HttpCodeBody = $matches[0];
				
				if (
					($this->Operation == 'UploadFile' && $HttpCodeHeader != 100) || 
					($this->Operation == 'DownloadFile' && $HttpCodeHeader != 200)
				)
				{
					require_once($_SERVER['DOCUMENT_ROOT'].'/include/XML_Parser.php');
					
					$Body = xml2array($Body);
					
					throw new \EP(\EP::L("Serviciul <b>Amazon {$this->ServiceName}</b> a raspuns cu codul {$HttpCodeHeader} (". \F\HttpCode($HttpCodeHeader).")!\n\n". print_r($Body,true) ."\n\nEndpoint: ". $this->CurlUrl ."\n\nHeader: ". print_r($this->CurlHeader, true)));
				}
				else
				{				
					if ($this->Operation == 'DownloadFile' && $HttpCodeHeader == 200)
					{
						$DownloadPathFile = "/tmp_files/". \F\HashMe($this->PathFile . date('Ymdhsi')) .'.'. pathinfo($this->PathFile, PATHINFO_EXTENSION);
						
						$Fisier = fopen($_SERVER['DOCUMENT_ROOT'] . $DownloadPathFile, "wa+");
						
						if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $DownloadPathFile))
							throw new \EP(\EP::L($NumeFunctie."\nNu se poate scrie in ". $_SERVER['DOCUMENT_ROOT'] . $DownloadPathFile));
						
						fwrite($Fisier, $Body);
						fclose($Fisier);
						
						return array(
							"PathFile" => "..". $DownloadPathFile
						);
					}						
				}
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
	
	
		public function generatePresignedUrl($Bucket, $PathFile, $ExpirationTime)
		{
			try
			{
				$ExpirationTime		= ($ExpirationTime ? $ExpirationTime : 60);
				$this->Operation 	= "GeneratePresignedUrl";
				$this->setEndpoint($Bucket);
				$this->PathFile 	= $PathFile;
				$Signature 			= $this->getSignatureQueryParameters($ExpirationTime);
				$Timestamp 			= gmdate('Ymd\THis\Z');
				$Date 				= gmdate('Ymd');
				
				echo $this->Endpoint . $PathFile . '?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential='. $this->AccessKey .'%2F'. $Date .'%2F'. $this->AwsRegion .'%2F'. $this->ServiceName .'%2Faws4_request&X-Amz-Date='. $Timestamp .'&X-Amz-Expires='. $ExpirationTime .'&X-Amz-SignedHeaders=host&X-Amz-Signature='. $Signature.'<br>'; 
			}
			catch (\EP $e) { throw new \EP($e->getMessage()); } 	// Output erori neanticipate
			catch (\Exception $e) { throw new \Exception($e->getMessage()); }	
		}
	}

?>