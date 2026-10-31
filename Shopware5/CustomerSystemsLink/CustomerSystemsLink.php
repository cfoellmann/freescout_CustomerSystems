<?php

namespace CustomerSystemsLink;

use Shopware\Components\Plugin;

class CustomerSystemsLink extends Plugin
{
    public static function getSubscribedEvents()
    {
        return ['Enlight_Controller_Action_PostDispatchSecure_Backend_Index' => 'extendBackend'];
    }

    public function extendBackend(\Enlight_Event_EventArgs $args)
    {
        $controller = $args->getSubject();
        if ($controller->Request()->getActionName() !== 'index') {
            return;
        }
        $controller->View()->addTemplateDir($this->getPath().'/Resources/views');
        $controller->View()->extendsTemplate('backend/customer_systems/header.tpl');
    }
}
