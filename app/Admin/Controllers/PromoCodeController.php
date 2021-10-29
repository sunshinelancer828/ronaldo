<?php

namespace App\Admin\Controllers;

use App\PromoCode;
use App\Status;
use App\Country;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PromoCodeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Promo Codes';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PromoCode);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($countries){
            $country_name = Country::where('id',$countries)->value('country_name');
                return "$country_name";
        });
        $grid->column('promo_name', __('Promo name'));
        $grid->column('promo_code', __('Promo code'));
        $grid->column('description', __('Description'))->hide();
        $grid->column('promo_type', __('Promo type'))->display(function(){
            $value = Status::where('id',$this->promo_type)->value('name');
            return $value;
        });
        $grid->column('discount', __('Discount'));
        $grid->column('redemptions', __('Redemptions'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
       // $grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));

        $grid->disableExport();
        $grid->actions(function ($actions) {
        $actions->disableView();
        });
        $grid->filter(function ($filter) {
            $statuses = Status::where('type','general')->pluck('name','id');
            $promo_types = Status::where('type','promo_type')->pluck('name','id');
            $countries = Country::pluck('country_name', 'id');
            
            $filter->disableIdFilter();
            $filter->equal('country_id', 'Country')->select($countries);
            $filter->like('promo_type', 'Promo type')->select($promo_types);
            $filter->like('promo_name', 'Promo name');
            $filter->like('promo_code', 'Promo code');
            $filter->like('discount', 'Discount');
            $filter->equal('status', 'Status')->select($statuses);
        
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(PromoCode::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('promo_name', __('Promo name'));
        $show->field('promo-code', __('Promo code'));
        $show->field('description', __('Description'));
        $show->field('promo_type', __('Promo type'));
        $show->field('discount', __('Discount'));
        $show->field('redemptions', __('Redemptions'));
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PromoCode);
        $statuses = Status::where('type','general')->pluck('name','id');
        $promo_types = Status::where('type','promo_type')->pluck('name','id');
        $countries = Country::pluck('country_name', 'id');
        
        $form->select('country_id','Country')->options($countries)->rules('required');
        $form->text('promo_name', __('Promo name'))->rules('required|max:250');
        $form->text('promo_code', __('Promo code'))->rules('required|max:250');
        $form->textarea('description', __('Description'))->rules('required');
        $form->select('promo_type', __('Promo type'))->options($promo_types)->rules('required');
        $form->decimal('discount', __('Discount'))->rules('required');
        $form->text('redemptions', __('Redemptions'))->rules('required');
        $form->select('status', __('Status'))->options($statuses)->rules('required');

         $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete(); 
            $tools->disableView();
        });
           $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });



        return $form;
    }
}
