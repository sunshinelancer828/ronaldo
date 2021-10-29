<?php

namespace App\Admin\Controllers;

use App\Models\Ratings;
use App\Models\Customer;
use App\Models\Driver;
use App\Trip;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RatingsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Ratings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Ratings());

        $grid->column('id', __('Id'));
        $grid->column('trip_id', __('Trip Id'))->display(function($trips){
            $country_name = Trip::where('id',$trips)->value('trip_id');
                return "$trip_id";
        });
        $grid->column('customer_id', __('Customer Id'))->display(function($customers){
            $country_name = Customer::where('id',$customers)->value('first_name');
                return "$first_name";
        });
        $grid->column('driver_id', __('Driver Id'))->display(function($drivers){
            $country_name = Driver::where('id',$countries)->value('first_name');
                return "$first_name";
        });
        $grid->column('rating', __('Rating'));
        $grid->column('feedback', __('Feedback'));
        
        $grid->disableExport();
        $grid->actions(function ($actions) {
           $actions->disableView();
        });

        $grid->filter(function ($filter) { 


           $filter->disableIdFilter();
           $trips = Trip::pluck('trip_id', 'id');

           $filter->equal('trip_id', __('Trip id'))->select($trips);
            $filter->equal('customer_id', __('Customer id'))->select($customers);
            $filter->equal('driver_id', __('Driver id'))->select($drivers);     
           $filter->like('rating', __('Rating'));
           $filter->like('feedback', __('Feedback'));

               
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
        $show = new Show(Ratings::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('trip_id', __('Trip id'));
        $show->field('customer_id', __('Customer id'));
        $show->field('driver_id', __('Driver id'));
        $show->field('rating', __('Rating'));
        $show->field('feedback', __('Feedback'));
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
        $form = new Form(new Ratings());
          $trips = Trip::pluck('trip_id', 'id');

        $form->decimal('trip_id', __('Trip id'))->rules('required');
        $form->decimal('customer_id', __('Customer id'))->rules('required');
        $form->decimal('driver_id', __('Driver id'))->options('required');
        $form->decimal('rating', __('Rating'))->rules('required');
        $form->textarea('feedback', __('Feedback'))->rules(function ($form) {
                    return 'required';
                });

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
