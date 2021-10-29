<?php

namespace App\Admin\Controllers;

use App\BookingStatus;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BookingStatusController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Booking Status';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BookingStatus);

        $grid->column('id', __('Id'));
        $grid->column('status_name', __('Status Name'));
        $grid->column('customer_status_name', __('Customer Status Name'));
        //$grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));
        
        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
        $actions->disableView();
        $actions->disableEdit();
        $actions->disableDelete();
        });

         $grid->filter(function ($filter) {
            //Get All status
        
            $filter->like('status_name', 'Status Name');
            $filter->like('customer_status_name', 'Customer Status Name');
            
            
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
        $show = new Show(BookingStatus::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('status_name', __('Status name'));
        $show->field('customer_status_name', __('Customer status name'));
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
        $form = new Form(new BookingStatus);

        $form->text('status_name', __('Status Name'));
        $form->text('customer_status_name', __('Customer Status Name'));
        
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
