<?php

namespace App\Admin\Controllers;
use App\Status;
use App\Country;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CountryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Country';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Country());

        $grid->column('id', __('Id'));
        $grid->column('phone_code', __('Phone Code'));
        $grid->column('country_name', __('Country Name'));
        $grid->column('short_name', __('Short Name'));
        $grid->column('timezone', __('Time Zone'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });

        $grid->disableExport();
        $grid->actions(function ($actions) {
        $actions->disableView();
        });

         $grid->filter(function ($filter) {
            //Get All status
            $statuses = Status::where('type','general')->pluck('name','id');
            
            $filter->like('phone_code', 'Phone_Code');
            $filter->like('country_name', 'Country_Name');
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
        $show = new Show(Country::findOrFail($id));
    
        $show->field('id', __('Id'));
        $show->field('phone_code', __('Phone code'));
        $show->field('country_name', __('Country name'));
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
        $form = new Form(new Country());
        $statuses = Status::where('type','general')->pluck('name','id');

        $form->text('phone_code', __('Phone Code'))->rules(function ($form) {
            return 'required';
        });
        $form->text('country_name', __('Country Name'))->rules(function ($form) {
            return 'required';
        });
        $form->text('short_name', __('Short Name'))->rules(function ($form) {
            return 'required';
        });
        $form->text('timezone', __('Time Zone'))->rules(function ($form) {
            return 'required';
        });
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
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
