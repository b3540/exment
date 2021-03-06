<?php

namespace Exceedone\Exment\Model;


class CustomViewColumn extends ModelBase
{
    protected $guarded = ['id'];

    public function custom_view(){
        return $this->belongsTo(CustomView::class, 'custom_view_id');
    }
    
    public function custom_column(){
        return $this->belongsTo(CustomColumn::class, 'view_column_target')
            //->where('view_column_type', Define::VIEW_COLUMN_TYPE_COLUMN)
            ;
    }
}
