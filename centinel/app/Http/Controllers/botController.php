<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

use App\Cliente; 	

class botController extends Controller
{
    public function getWebhook(Request $request)
    {
		if ($request->get('hub_mode') == 'subscribe' and $request->get('hub_verify_token') === env('HUB_VERIFY_TOKEN')) {
		   return response($request->get('hub_challenge'));
	    }else{
			return response('Error', 400);    
		} 
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response  
     */
    public function postWebhook(Request $request) 
    {
		
		/*
		$client = new \GuzzleHttp\Client;  
		$res = $client->request('POST', 'https://webhook.site/8e94ea7b-254c-42a8-a8dc-5c6258f7a068',["hola"=>"asdasd"]);
		
		*/

		
		$has_message = false;
		$is_echo = true; 
		$reply=null;  
					
		$content = json_decode($request->getContent(),true);
		
		//Id de la pagina facebook   
		$page_id = $content['entry'][0]['id']; 
		
		//Verificamos si el contenido contiene alguna propiedad en el mensaje, si no existe seteamos null
		$postArray = isset($content['entry'][0]['messaging']) ? $content['entry'][0]['messaging'] : null;
		$postback = isset($content['entry'][0]['messaging'][0]['postback']['payload']) ? $content['entry'][0]['messaging'][0]['postback']['payload']: null;
		$quick_reply=isset($content['entry'][0]['messaging'][0]['message']['quick_reply']['payload']) ? $content['entry'][0]['messaging'][0]['message']['quick_reply']['payload']:null;
		$attachments=isset($content['entry'][0]['messaging'][0]['message']['attachments']) ? $content['entry'][0]['messaging'][0]['message']['attachments']:null;
		$nlp=isset($content['entry'][0]['messaging'][0]['message']['nlp']['entities']) ? $content['entry'][0]['messaging'][0]['message']['nlp']['entities']:null;
		$time=isset($content['entry'][0]['time']) ? $content['entry'][0]['time']:null;   
		
		//Comprobamos que el mensaje no sea null
		if (!is_null($postArray)){ 
			$sender = $postArray[0]['sender']['id'];  
			$has_message = isset($postArray[0]['message']['text']);
			 
			$is_echo = isset($postArray[0]['message']['is_echo']);
		} 
		
		 
		$client_est = DB::select("SELECT estado FROM clientes WHERE sender='".$sender."'") ; 
		
		if(!empty($client_est) && $client_est[0]->estado===1){
			//evento is postback
			if($postback){ 
				$reply=null;		
				switch($postback) {
					CASE 'EMER':
						$reply=$this->printIncidentes();
						
						break;
					CASE 'SUGER':
						$reply = ["text" =>'Comentanos...'];
						
						$client = DB::select("SELECT id FROM clientes WHERE sender='".$sender."'") ;  
						$cliente=Cliente::find($client[0]->id);
						$cliente->estado=0;											
						$cliente->save(); 					
						break;
					CASE 'ROBO':	
						$reply = ["text" =>'esto es un asalto!! XD'];
						break;
				} 	
				$this->sendToFbMessenger($sender,$reply);  
			}else{
				
				$client = DB::select("SELECT primer_nombre,segundo_nombre FROM clientes WHERE sender='".$sender."'") ;  
				
				if(empty($client)){
					
					$newCliente=$this->getData($sender);
												
					$first_name=$newCliente->first_name;
					$second_name=$newCliente->last_name;
				
					$cliente = new Cliente;     
					$cliente->sender=$sender;  
					$cliente->primer_nombre=$first_name;
					$cliente->segundo_nombre=$second_name;
					$cliente->estado=1;  
					$cliente->save();  
				}else{
					$first_name=$client[0]->primer_nombre;	  
				}
				
				
				
				
				
									
				$reply = ["text" =>'Hola '.$first_name.', soy CentinelBot ¿Como te ayudamos hoy?'];
				$this->sendToFbMessenger($sender,$reply);  
				
				$this->sendToFbMessenger($sender,$this->printOptions());  	
			}
		}//end if estado
	}	
	
	protected function printOptions(){
		$servicios = DB::select('SELECT nombre,descripcion,foto,payload FROM servicio LIMIT 8;') ;
		$reply =[
				"attachment"=>[
					"type"=>"template",	
					"payload"=>[
						"template_type"=>"generic",
						"elements"=>[
						]
					] 
				]	
			];
						   
			foreach($servicios as $servicio){  						
				$el=[	
					"title"=>$servicio->nombre,	
					"image_url"=>"https://centinelbot.com/imagenes/".$servicio->foto, 		
					"subtitle"=>$servicio->descripcion, 	
					"buttons"=>[	
						[ 
						"type"=> "postback",		
						"title"=> "SELECCIONAR", 			         		 
						"payload"=> $servicio->payload,  
						]    
					]     		 						
				];
   
									
				array_push($reply["attachment"]["payload"]["elements"],$el);
				
			}
			return $reply;
			
	}
	
	protected function printIncidentes(){	
		$incidentes = DB::select("SELECT nombre,descripcion,imagen,payload FROM incidentes ORDER BY orden ASC LIMIT 8;") ;
		$reply =[
				"attachment"=>[
					"type"=>"template",	
					"payload"=>[
						"template_type"=>"generic",
						"elements"=>[
						]
					] 
				]	
			]; 
						   
			foreach($incidentes as $incidente){  						
				$el=[	
					"title"=>$incidente->nombre,	
					"image_url"=>"https://centinelbot.com/imagenes/".$incidente->imagen, 		
					"subtitle"=>$incidente->descripcion, 	
					"buttons"=>[	  
						[ 
						"type"=> "postback",		
						"title"=> "SELECCIONAR", 			           		 
						"payload"=> $incidente->payload,  
						]    
					]     		 						
				];
   
									
				array_push($reply["attachment"]["payload"]["elements"],$el);
				
			}
			return $reply;
	}
	
	//Envia peticion con el mensaje  
	protected function sendToFbMessenger($sender, $message)
	{						
		$data = ['json' => [ 
				'recipient' =>  ['id' => $sender],     
				'message'  	=>  $message,
			]
		];
				 
		$client = new \GuzzleHttp\Client; 
		$res = $client->request('POST', 'https://graph.facebook.com/v2.6/me/messages?access_token='.env('FB_TOKEN'),$data); 
		
		return response($res->getBody(), 200);  	
	}
	
	//Obtener perfil del usuario apartir de su id
	protected function getData($idUser){ 		
		$userFb = new \GuzzleHttp\Client; 
		$response = $userFb->request('GET','https://graph.facebook.com/'.$idUser.'?fields=first_name,last_name&access_token='.env('FB_TOKEN'));
		
		$data=json_decode($response->getBody()->getContents());
		
		return $data;						
	}
}
