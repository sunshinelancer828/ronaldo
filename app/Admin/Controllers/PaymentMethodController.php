<?php

namespace App\Admin\Controllers;

use App\PaymentMethod;
use App\Status;
use App\Country;
use App\Models\PaymentType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PaymentMethodController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Payment Methods';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PaymentMethod);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($countries){
            $country_name = Country::where('id',$countries)->value('country_name');
                return "$country_name";
        });
        $grid->column('payment', __('Payment'));
        $grid->column('payment_type', __('Payment Type'))->display(function($payment){
            $payment_type = PaymentType::where('id',$payment)->value('payment_type');
            if ($payment == 1) {
                return "<span class='label label-success'>$payment_type</span>";
            } if ($payment == 2) {
                return "<span class='label label-danger'>$payment_type</span>";
            } if ($payment == 3) {
                return "<span class='label label-info'>$payment_type</span>";
            } if ($payment == 4) {
                return "<span class='label label-warning'>$payment_type</span>";
            }
        });
        $grid->column('icon', __('Icon'))->image();
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();
        
        $grid->disableExport();
        //$grid->disableCreateButton();
        $grid->actions(function ($actions) {
        $actions->disableView();
        //$actions->disableDelete();
        //$actions->disableEdit();
        });
        $grid->filter(function ($filter) {
            $statuses = Status::where('type','general')->pluck('name','id');
            $countries = Country::pluck('country_name', 'id');
            
            $filter->disableIdFilter();
            $filter->equal('country_id', 'Country')->select($countries);  
            $filter->like('payment', 'Payment');
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
        $show = new Show(PaymentMethod::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('payment', __('Payment'));
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
        $form = new Form(new PaymentMethod);
        $statuses = Status::where('type','general')->pluck('name','id');
        $countries = Country::pluck('country_name', 'id');
        $payment_types = PaymentType::pluck('payment_type', 'id');
        
        $form->select('country_id','Country')->options($countries)->rules('required');
        $form->text('payment', __('Payment'))->rules('required|max:250');
        $form->select('payment_type', __('Payment Type'))->options($payment_types);
        $form->image('icon', __('Icon'))->uniqueName();
        $form->select('status','Status')->options($statuses)->rules('required');

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
