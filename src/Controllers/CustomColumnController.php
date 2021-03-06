<?php

namespace Exceedone\Exment\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;
//use Encore\Admin\Widgets\Form;
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request;
use Exceedone\Exment\Model\Define;
use Exceedone\Exment\Model\CustomTable;
use Exceedone\Exment\Model\CustomColumn;
use Exceedone\Exment\Form\Tools;
use Illuminate\Http\RedirectResponse;

class CustomColumnController extends AdminControllerTableBase
{
    use ModelForm;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        
        $this->setPageInfo(exmtrans("custom_column.header"), exmtrans("custom_column.header"), exmtrans("custom_column.description"));
    }

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return $this->AdminContent(function (Content $content) {
            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        if (($response = $this->validateTableAndId(CustomColumn::class, $id, 'column')) instanceof RedirectResponse) {
            return $response;
        }

        return $this->AdminContent(function (Content $content) use ($id) {
            $content->body($this->form($id)->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create(Request $request)
    {
        return $this->AdminContent(function (Content $content) {
            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(CustomColumn::class, function (Grid $grid) {
            $grid->column('custom_table.table_name', exmtrans("custom_table.table_name"))->sortable();
            $grid->column('custom_table.table_view_name', exmtrans("custom_table.table_view_name"))->sortable();
            $grid->column('column_name', exmtrans("custom_column.column_name"))->sortable();
            $grid->column('column_view_name', exmtrans("custom_column.column_view_name"))->sortable();
            $grid->column('column_type', exmtrans("custom_column.column_type"))->sortable()->display(function($val){
                return array_get(getTransArray(Define::TABLE_COLUMN_TYPE, "custom_column.column_type_options"), $val);
            });

            if (isset($this->custom_table)) {
                $grid->model()->where('custom_table_id', $this->custom_table->id);
            }

            //  $grid->disableCreateButton();
            $grid->disableExport();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                if (boolval($actions->row->system_flg)) {
                    $actions->disableDelete();
                }
                $actions->disableView();
            });

            $grid->tools(function (Grid\Tools $tools) {
                $tools->batch(function (Grid\Tools\BatchActions $actions) {
                    $actions->disableDelete();
                });
                $tools->append(new Tools\GridChangePageMenu('column', $this->custom_table, false));
            });

            // filter
            $grid->filter(function($filter){
                // Remove the default id filter
                $filter->disableIdFilter();
                // Add a column filter
                $filter->equal('column_name', exmtrans("custom_column.column_name"));
                $filter->equal('column_view_name', exmtrans("custom_column.column_view_name"));
                $filter->equal('column_type', exmtrans("custom_column.column_type"))->select(function($val){
                    return array_get(getTransArray(Define::TABLE_COLUMN_TYPE, "custom_column.column_type_options"), $val);
                });
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = null)
    {                
        return Admin::form(CustomColumn::class, function (Form $form) use ($id) {
            // set script
            //TODO: call using pjax
            $form->html('<script src="'.asset('vendor/exment/js/customcolumn.js').'"></script>');
    
            $form->hidden('custom_table_id')->default($this->custom_table->id);
            $form->display('custom_table.table_name', exmtrans("custom_table.table_name"))->default($this->custom_table->table_name);
            $form->display('custom_table.table_view_name', exmtrans("custom_table.table_view_name"))->default($this->custom_table->table_view_name);
            
            if (!isset($id)) {
                $classname = CustomColumn::class;
                $form->text('column_name', exmtrans("custom_column.column_name"))
                    ->rules("required|regex:/^[a-zA-Z0-9\-_]*$/|uniqueInTable:{$classname},{$this->custom_table->id}")
                    ->help(exmtrans('common.help_code'));
            } else {
                $form->display('column_name', exmtrans("custom_column.column_name"));
            }

            $form->text('column_view_name', exmtrans("custom_column.column_view_name"))->rules("required");
            $form->select('column_type', exmtrans("custom_column.column_type"))
            ->options(getTransArray(Define::TABLE_COLUMN_TYPE, "custom_column.column_type_options"))
            ->attribute(['data-filtertrigger' =>true])
            ->rules('required');

            if (!isset($id)) {
                $id = $form->model()->id;
            }

            $column_type = isset($id) ? CustomColumn::find($id)->column_type : null;
            $form->embeds('options', exmtrans("custom_column.options.header"), function ($form) use ($column_type, $id) {
                $form->switchbool('required', exmtrans("common.reqired"));
                $form->switchbool('search_enabled', exmtrans("custom_column.options.search_enabled"))
                    ->help(exmtrans("custom_column.help.search_enabled"));
                $form->text('placeholder', exmtrans("custom_column.options.placeholder"));
                $form->text('help', exmtrans("custom_column.options.help"))->help(exmtrans("custom_column.help.help"));
                $form->switchbool('use_label_flg', exmtrans("custom_column.options.use_label_flg"))
                    ->help(exmtrans("custom_column.help.use_label_flg"));

                // setting for each settings of column_type. --------------------------------------------------
 
                // text
                // string length
                $form->number('string_length', exmtrans("custom_column.options.string_length"))
                    ->default(256)
                    ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => ['text','textarea']])]);

                $form->checkbox('available_characters', exmtrans("custom_column.options.available_characters"))
                    ->options(getTransArray(Define::CUSTOM_COLUMN_AVAILABLE_CHARACTERS_OPTIONS, 'custom_column.available_characters'))
                    ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => ['text']])])
                    ->help(exmtrans("custom_column.help.available_characters"))
                    ;
                       
                // number            
                //if(in_array($column_type, ['integer','decimal'])){
                $form->number('number_min', exmtrans("custom_column.options.number_min"))
                    ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => ['integer','decimal']])])
                    ->disableUpdown()
                    ->defaultEmpty();
                $form->number('number_max', exmtrans("custom_column.options.number_max"))
                    ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => ['integer','decimal']])])
                    ->disableUpdown()
                    ->defaultEmpty();
                
                $form->switchbool('number_format', exmtrans("custom_column.options.number_format"))
                        ->help(exmtrans("custom_column.help.number_format"))
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => ['integer','decimal','calc']])]);

                $form->switchbool('updown_button', exmtrans("custom_column.options.updown_button"))
                    ->help(exmtrans("custom_column.help.updown_button"))
                    ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => ['integer']])])
                    ;
                    //}

                // select
                // define select-item
                $form->textarea('select_item', exmtrans("custom_column.options.select_item"))
                        //->rules('required')
                        ->help(exmtrans("custom_column.help.select_item"))
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'select'])]);
                // define select-item
                $form->textarea('select_item_valtext', exmtrans("custom_column.options.select_item"))
                        //->rules('required')
                        ->help(exmtrans("custom_column.help.select_item_valtext"))
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'select_valtext'])]);

                // define select-target table
                $form->select('select_target_table', exmtrans("custom_column.options.select_target_table"))
                        ->help(exmtrans("custom_column.help.select_target_table"))
                        //->rules('required')
                        ->options(function ($select_table) {
                            $options = CustomTable::all()->pluck('table_view_name', 'id')->toArray();
                            return $options;
                        })
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'select_table'])]);

                $form->text('true_value', exmtrans("custom_column.options.true_value"))
                        ->help(exmtrans("custom_column.help.true_value"))
                        //->rules('required')
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'boolean'])]);

                $form->text('true_label', exmtrans("custom_column.options.true_value"))
                        ->help(exmtrans("custom_column.help.true_label"))
                        //->rules('required')
                        ->default(exmtrans("custom_column.options.true_label_default"))
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'boolean'])]);
                    
                $form->text('false_value', exmtrans("custom_column.options.false_value"))
                        ->help(exmtrans("custom_column.help.false_value"))
                        //->rules('required')
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'boolean'])]);

                $form->text('false_label', exmtrans("custom_column.options.false_label"))
                        ->help(exmtrans("custom_column.help.false_label"))
                        //->rules('required')
                        ->default(exmtrans("custom_column.options.false_label_default"))
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'boolean'])]);

                // auto numbering
                $form->select('auto_number_type', exmtrans("custom_column.options.auto_number_type"))
                        //->rules('required')
                        ->options(
                            [
                            'format' => exmtrans("custom_column.options.auto_number_type_format"),
                            'random25' => exmtrans("custom_column.options.auto_number_type_random25"),
                            'random32' => exmtrans("custom_column.options.auto_number_type_random32")]
                        )
                        ->attribute(['data-filtertrigger' =>true, 'data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'auto_number'])]);

                $manual_url = url_join(config('exment.manual_url'), 'column#自動採番フォーマットのルール');
                $form->text('auto_number_format', exmtrans("custom_column.options.auto_number_format"))
                        ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'options_auto_number_type', 'value' => 'format'])])
                        ->help(sprintf(exmtrans("custom_column.help.auto_number_format"), $manual_url))
                    ;

                // calc
                $custom_table = $this->custom_table;
                $form->valueModal('calc_formula', exmtrans("custom_column.options.calc_formula"))
                    ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => 'calc'])])
                    ->help(exmtrans("custom_column.help.calc_formula"))
                    ->text(function($value){
                        /////TODO:copy and paste
                        if(!isset($value)){$value = [];}
                        // convert json to array
                        if(!is_array($value) && is_json($value)){
                            $value = json_decode($value, true);
                        }

                        // set calc formula list
                        $symbols = [
                            'plus' => '＋',
                            'minus' => '－',
                            'times' => '×',
                            'div' => '÷',
                        ];

                        ///// get text
                        $texts = [];
                        foreach($value as &$v){
                            $val = array_get($v, 'val');
                            switch(array_get($v, 'type')){
                                case 'dynamic':
                                    $texts[] = CustomColumn::find(array_get($v, 'val'))->column_view_name ?? null;
                                    break;
                                case 'symbol':
                                    $texts[] = array_get($symbols, $val);
                                    break;
                                case 'fixed':
                                    $texts[] = $val;
                                    break;
                            }
                        }
                        return implode(" ", $texts);
                    })
                    ->modalbody(function($value) use($id, $custom_table){
                        /////TODO:copy and paste
                        // get other columns
                        // return $id is null(calling create fuction) or not match $id and row id.
                        $custom_columns = $custom_table->custom_columns->filter(function($column) use($id){
                            return (!isset($id) || $id != array_get($column, 'id')) 
                                && in_array(array_get($column, 'column_type'), Define::TABLE_COLUMN_TYPE_CALC);
                        })->toArray();
                        
                        if(!isset($value)){$value = [];}
                        // convert json to array
                        if(!is_array($value) && is_json($value)){
                            $value = json_decode($value, true);
                        }

                        // set calc formula list
                        $symbols = [
                            'plus' => '＋',
                            'minus' => '－',
                            'times' => '×',
                            'div' => '÷',
                        ];

                        ///// get text
                        foreach($value as &$v){
                            $val = array_get($v, 'val');
                            switch(array_get($v, 'type')){
                                case 'dynamic':
                                    $v['text'] = CustomColumn::find(array_get($v, 'val'))->column_view_name ?? null;
                                    break;
                                case 'symbol':
                                    $v['text'] = array_get($symbols, $val);
                                    break;
                                case 'fixed':
                                    $v['text'] = $val;
                                    break;
                            }
                        }

                        return view('exment::custom-column.calc_formula_modal', [
                            'custom_columns' => $custom_columns,
                            'value' => $value,
                            'symbols' => $symbols,
                        ]);
                    })
                ;

                // image, file, select
                // enable multiple
                $form->switchbool('multiple_enabled', exmtrans("custom_column.options.multiple_enabled"))
                    ->attribute(['data-filter' => json_encode(['parent' => 1, 'key' => 'column_type', 'value' => ['file','image', 'select_table', 'select', 'select_valtext', 'user', 'organization']])]);
            });

            $form->saved(function (Form $form) {
                // create or drop index --------------------------------------------------
                $model = $form->model();
                alterColumn($model->custom_table->table_name, $model->column_name);
            });
            $form->disableReset();
            $form->disableViewCheck();
            $custom_table = $this->custom_table;
            $form->tools(function (Form\Tools $tools) use ($id, $form, $custom_table) {
                $tools->disableView();
                $tools->add((new Tools\GridChangePageMenu('column', $custom_table, false))->render());
            });
        });
    }
}
