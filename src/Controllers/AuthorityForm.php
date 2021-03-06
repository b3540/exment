<?php

namespace Exceedone\Exment\Controllers;

use Exceedone\Exment\Model\System;
use Exceedone\Exment\Model\Define;
use Exceedone\Exment\Model\Authority;
use Encore\Admin\Form;

trait AuthorityForm
{
    /**
     * add authority to form.
     * @param mixed $form
     */
    protected function addAuthorityForm($form, $authority_type)
    {
        // if system doesn't use authority, return true
        if(!System::authority_available()){
            return;
        }

        // authority setting --------------------------------------------------
        $form->header(exmtrans('authority.header'))->hr();
        switch($authority_type){
            case Define::AUTHORITY_TYPE_VALUE:
                $form->description(exmtrans(System::organization_available() ? 'authority.description_form.custom_value' : 'authority.description_form.custom_value_disableorg'));
                break;
                
            case Define::AUTHORITY_TYPE_TABLE:
            $form->description(exmtrans(System::organization_available() ? 'authority.description_form.custom_table' : 'authority.description_form.custom_table_disableorg'));
            break;
            
            case Define::AUTHORITY_TYPE_SYSTEM:
                $form->description(exmtrans(System::organization_available() ? 'authority.description_form.system' : 'authority.description_form.system_disableorg'));
                break;
            
                case Define::AUTHORITY_TYPE_PLUGIN:
                $form->description(exmtrans(System::organization_available() ? 'authority.description_form.plugin' : 'authority.description_form.plugin_disableorg'));
                break;
            
        }

        // Add Authority --------------------------------------------------
        authorityLoop($authority_type, function($authority, $related_type) use($form){
            switch($related_type){
                case Define::SYSTEM_TABLE_NAME_USER:
                $related_types = ['column_name' => 'user_name', 'view_name' => exmtrans('user.default_table_name'), 'suffix' => 'userable'];
                break;
                default:
                $related_types = ['column_name' => 'organization_name', 'view_name' => exmtrans('organization.default_table_name'), 'suffix' => 'organizationable'];
                break;                
            }
            $form->pivotMultiSelect(getAuthorityName($authority, $related_type), "{$authority->authority_view_name}(".array_get($related_types, 'view_name').")")
                    ->options(function($options) use($related_type, $related_types){
                        return getOptions($related_type, $options);
                    })
                    ->pivot(['authority_id' => $authority->id, 'related_type' => $related_type])
                    //->help($authority->description)
                    ;
        });
    }
}
