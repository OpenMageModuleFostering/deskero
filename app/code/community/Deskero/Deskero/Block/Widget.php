<?php

class Deskero_Deskero_Block_Widget extends Mage_Core_Block_Template
{
    protected function _toHtml()
    {
        if(!Mage::getStoreConfig('deskero_settings/deskero_widget_settings/deskero_widget_active')) {
            return '';
        }

        return Mage::getStoreConfig('deskero_settings/deskero_widget_settings/deskero_widget');
    }
}