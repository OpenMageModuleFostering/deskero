<?php

class Deskero_Deskero_Block_Adminhtml_Ticket_Create_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id' => 'createTicket_form',
            'action' => $this->getData('action'),
            'method' => 'post'
        ));

        $fieldset = $form->addFieldset('base', array(
            'legend'=>$this->__('New Ticket'),
            'class'=>'fieldset-wide'
        ));

        $fieldset->addField('customer_email', 'text', array(
            'name' => 'customer_email',
            'label' => $this->__('Customer Email'),
            'title' => $this->__('Customer Email'),
           
            'required' => true
        ));

        $fieldset->addField('customer_name', 'text', array(
            'name'     => 'customer_name',
            'label'    => $this->__('Customer Name'),
            'title'    => $this->__('Customer Name'),
            'required' => true
        ));

        $fieldset->addField('subject', 'text', array(
            'name'     => 'subject',
            'label'    => $this->__('Subject'),
            'title'    => $this->__('Subject'),
            'required' => true
        ));
        
        
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
		    
		    $typeListCall = $deskeroUrl.'ticketType/list?page=1';
		    
		    $typeListCurl = curl_init($typeListCall);
			curl_setopt($typeListCurl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,
																 'clientId: '.$clientID,
																 'Accept: application/json'));
			
			curl_setopt($typeListCurl, CURLOPT_RETURNTRANSFER, true);
			$typeListResponse = curl_exec($typeListCurl);
		    
		    curl_close($typeListCurl);
		    
		    if ($typeListResponse) {
			    
			    $typeListData = json_decode($typeListResponse);
				$locale = Mage::app()->getLocale()->getLocaleCode();
				$locale = explode("_", $locale);
				$locale = $locale[0];
				
				foreach ($typeListData->type->records as $type) {
					
					if (isset($type->labels->$locale)) {
						$label = $type->labels->$locale;
					} else {
						$label = $type->labels->en;
					}
						
					$value = array('label' => $label, 'value' => $type->id);
					$values[]=$value;
				}
				
				if ($values) {				
							    
			    $fieldset->addField('type', 'select', array(
		            'name'     => 'type',
		            'label'    => $this->__('Type'),
		            'title'    => $this->__('Type'),
		            'required' => true,
		            'values'   => $values
		            
		        ));
	        
	        }
	        
		    }
		    
		    
		}

        $fieldset->addField('description', 'textarea', array(
            'name'     => 'description',
            'label'    => $this->__('Description'),
            'title'    => $this->__('Description'),
            'required' => true
        ));
		
		$form->setValues(Mage::getSingleton('adminhtml/session')->getData('createTicket_form'));
        
        Mage::getSingleton('adminhtml/session')->setData('createTicket_form','');
        
        $form->setUseContainer(true);
        $form->setMethod('post');
        $this->setForm($form);
        return parent::_prepareForm();
    }

}
