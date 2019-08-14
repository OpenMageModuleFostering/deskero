<?php

class Deskero_Deskero_Block_Adminhtml_Ticket_Create_Create extends Mage_Adminhtml_Block_Widget_Form_Container {

	 protected function _preparelayout() {
	 
        $this->removeButton('delete');
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('save');
		
		$this->_addButton('save', array(
                'label'     => $this->__('Create Ticket'),
                'onclick'   => 'createTicket_form.submit();',
                'class'     => 'save',
            ), 1);
            
        $this->setChild('form', $this->getLayout()->createBlock('deskero/adminhtml_ticket_create_form'));
        return parent::_prepareLayout();
    }
    
    public function getFormHtml()
    {
        $formHtml = parent::getFormHtml();
        return $formHtml;
    }
    
    public function getHeaderText()
    {
        return $this->__('New Ticket');
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current' => true, 'back' => null));
    }
    
}