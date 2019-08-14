<?php
class Deskero_Deskero_Adminhtml_DeskeroController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Deskero Settings"));
	   $this->renderLayout();
    }
    
    public function launchAction()
    {
		       
		$token = Mage::getStoreConfig('deskero_settings/deskero_sso_settings/deskero_sso_token');
		$domain = Mage::getStoreConfig('deskero_settings/deskero_general_settings/deskero_subdomain');
		$email =  Mage::getStoreConfig('deskero_settings/deskero_sso_settings/deskero_sso_email');
		
		if (!filter_var($domain, FILTER_VALIDATE_URL) || $token == "" || $domain == "" || $email == "") {
			
			$notifyText = $this->__('Oops! ');
			$notifyText .= "<br/>".$this->__('To launch Deskero, set YOUR DESKERO DOMAIN, TOKEN SSO and AGENT EMAIL!');
			
			Mage::getSingleton('adminhtml/session')->addError($notifyText);
			
			$this->_redirect('adminhtml/system_config/edit/section/deskero_settings');	
			
		} else {
			
			$url = $domain."/?email=".$email."&hash=".hash('md5',$email.$token);
		
			$this->_redirectUrl($url);
			
		}
		
		

    }
    
    public function createTicketAction() {
    	
    	$apiToken = Mage::getStoreConfig('deskero_settings/deskero_general_settings/deskero_api_token');
		$clientID = Mage::getStoreConfig('deskero_settings/deskero_general_settings/deskero_clientid');
		
		if ($apiToken == "" || $clientID == "") {
			
			$notifyText = $this->__('Oops! ');
			$notifyText .= "<br/>".$this->__('To create a new ticket, set CLIENT ID and API TOKEN!');
			
			Mage::getSingleton('adminhtml/session')->addError($notifyText);
			
			$this->_redirect('adminhtml/system_config/edit/section/deskero_settings');	
			
		} else {
			
			$this->loadLayout();
			$this->_title($this->__("Create Ticket"));
			$this->_addContent($this->getLayout()->createBlock('deskero/adminhtml_ticket_create_create'));
	        $this->_addLeft($this->getLayout()->createBlock('deskero/adminhtml_menu'));
			$this->renderLayout();
		
		}
    	

    }
    
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
			
			$errorOccurred = false;
			
            $customerEmail = trim($data['customer_email']);
            $customerName = trim($data['customer_name']);
            
            $type = trim($data['type']);
            $subject = trim($data['subject']);
            $description = trim($data['description']);
			
			//validazione campi
			if ($customerEmail=="") {
				
				$errorOccurred = true;
				$errorText .= "<br/>".$this->__("Customer Email is required!");

			}
			
			if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
				
				$errorOccurred = true;
				$errorText .= "<br/>".$this->__("Customer Email is not a valid address!");

			}
			
			if ($customerName=="") {
				
				$errorOccurred = true;
				$errorText .= "<br/>".$this->__("Customer Name is required!");

			}
			
			if ($type=="") {
				
				$errorOccurred = true;
				$errorText .= "<br/>".$this->__("Type is required!");

			}
			
			if ($subject=="") {
				
				$errorOccurred = true;
				$errorText .= "<br/>".$this->__("Subject is required!");

			}
			
			if ($description=="") {
				
				$errorOccurred = true;
				$errorText .= "<br/>".$this->__("Description is required!");

			}
			
			if (!$errorOccurred) {
			
				$customerJSON = '{"name":"'.$customerName.'", "email":"'.$customerEmail.'"}';
				
				$deskeroUrl = 'https://api.deskero.com/';
	        
		        $apiToken = Mage::getStoreConfig('deskero_settings/deskero_general_settings/deskero_api_token');
		        $clientID = Mage::getStoreConfig('deskero_settings/deskero_general_settings/deskero_clientid');
		        
				$authorizationCall = $deskeroUrl.'oauth/token?grant_type=client_credentials';
				
				$authCurl = curl_init($authorizationCall);
				curl_setopt($authCurl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$apiToken));
				curl_setopt($authCurl, CURLOPT_RETURNTRANSFER, true);
				$authResponse = curl_exec($authCurl);
				
				curl_close($authCurl);
				
				if ($authResponse) {
				    
				    $authData = json_decode($authResponse);
				    $accessToken = $authData->access_token;
				    
				    $customerCall = $deskeroUrl.'customer/list?page=1&email='.$customerEmail;
			    
				    $customerCurl = curl_init($customerCall);
					curl_setopt($customerCurl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,
																		 'clientId: '.$clientID,
																		 'Accept: application/json'));
					
					curl_setopt($customerCurl, CURLOPT_RETURNTRANSFER, true);
					$customerResponse = curl_exec($customerCurl);
				    
				    curl_close($customerCurl);
				    
				    if ($customerResponse) {
					    
					    $customerData = json_decode($customerResponse);
					    
					    $customerId = $customerData->customer->records[0]->id;
					    
					} else {
						
						$newCustomerCall = $deskeroUrl.'customer/insert';
						
						$newCustomerCurl = curl_init($newCustomerCall);
						curl_setopt($newCustomerCurl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,
																			 'clientId: '.$clientID,
																			 'Accept: application/json',
																			 "Content-type: application/json"));
						
						curl_setopt($newCustomerCurl, CURLOPT_POST, 1 );
						curl_setopt($newCustomerCurl, CURLOPT_POSTFIELDS, $customerJSON);
						
						curl_setopt($newCustomerCurl, CURLOPT_RETURNTRANSFER, true);
						$newCustomerResponse = curl_exec($newCustomerCurl);
					    
					    curl_close($newCustomerResponse);
						
						if ($newCustomerResponse) {
							
							$newCustomerData = json_decode($newCustomerResponse);
					    	$customerId = $newCustomerData->id;
					    
					    }
	
					}
					
					if ($customerId) {
						
						$openedBy = array('id'=>$customerId);
						$type = array('id'=>$data['type']);
						$newTicket = array(
	                        'openedBy' => $openedBy,
	                        'subject' => $data['subject'],
							'description' => $data['description'],
							'type' => $type,
							'tags' => array('magento')
	                    );
	                    
	                    $newTicketJSON = json_encode($newTicket);
	                    
	                    $newTicketCall = $deskeroUrl.'ticket/insert';
	                    
						$newTicketCurl = curl_init($newTicketCall);
						curl_setopt($newTicketCurl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,
																			 'clientId: '.$clientID,
																			 'Accept: application/json',
																			 "Content-type: application/json"));
						
						curl_setopt($newTicketCurl, CURLOPT_POST, 1 );
						curl_setopt($newTicketCurl, CURLOPT_POSTFIELDS, $newTicketJSON);
						
						curl_setopt($newTicketCurl, CURLOPT_RETURNTRANSFER, true);
						$newTicketResponse = curl_exec($newTicketCurl);
					    
					    curl_close($newTicketResponse);
						
						if ($newTicketResponse) {
							
							$newTicketData = json_decode($newTicketResponse);
							
							$getTicketCall = $deskeroUrl.'ticket/'.$newTicketData->id;
	                    
							$getTicketCurl = curl_init($getTicketCall);
							curl_setopt($getTicketCurl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,
																				 'clientId: '.$clientID,
																				 'Accept: application/json'));
							
							curl_setopt($getTicketCurl, CURLOPT_RETURNTRANSFER, true);
							$getTicketResponse = curl_exec($getTicketCurl);
	
							$getTicketData = json_decode($getTicketResponse);
	
							
							$subdomain = Mage::getStoreConfig('deskero_settings/deskero_general_settings/deskero_subdomain');
							
							$notifyText = $this->__('Ticket <b>#%s</b> has been created!',$getTicketData->number);
			                $notifyText .= ' <a href="https://' . $subdomain . '.deskero.com/agent/ticket/view/' . $newTicketData->id . '" target="_blank">';
			                $notifyText .= $this->__('View ticket in Deskero');
			                $notifyText .= '</a>';
							
							Mage::getSingleton('adminhtml/session')->addSuccess($notifyText);
			                		    
					    } else {
					    
						    $errorOccurred = true;
						    $errorText = $this->__('Error in ticket creation!');
					    }
				
					} else {
					
						$errorOccurred = true;
						$errorText = $this->__('Customer not found!');
					
					}
			    
				} else {
					
					$errorOccurred = true;
					$errorText = $this->__("Can't connect to Deskero API!");
	
				}
			}
		
		} else {
			
			$errorOccurred = true;
			$errorText = $this->__("No data sended!");	
		}
		
		if ($errorOccurred) {
			
			$notifyText = $this->__('Oops! ');
			$notifyText .= $errorText;
			
			Mage::getSingleton('adminhtml/session')->addError($notifyText);
			Mage::getSingleton('adminhtml/session' )->setData('createTicket_form', $data);
		
		}
		
		
		$this->_redirect('*/*/createTicket');	
     }
}