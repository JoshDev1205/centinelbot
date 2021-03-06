<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;	

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
		
		
		$client = new \GuzzleHttp\Client;  
		$res = $client->request('POST', 'https://webhook.site/8e94ea7b-254c-42a8-a8dc-5c6258f7a068',["hola"=>2]);
		
		return response($res->getBody(), 200); 	   
				
		   

		
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
		
		$reply = ["text" =>'holla'];
							$this->sendToFbMessenger($sender,$reply);  
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
}
