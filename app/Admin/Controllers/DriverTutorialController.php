<?php

namespace App\Admin\Controllers;

use App\DriverTutorial;
use App\Status;
use App\Country;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DriverTutorialController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Driver Tutorial';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DriverTutorial);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($country){
            $country_name = Country::where('id',$country)->value('country_name');
             return "$country_name";
        });
        
        $grid->column('title', __('Title'));
        $grid->column('thambnail_image', __('Thambnail Image'))->image();
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
            $countries = Country::pluck('country_name', 'id');
            
            $filter->equal('country_id', 'Country')->select($countries);
            $filter->equal('status', 'Status');
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
        $show = new Show(DriverTutorial::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
        $show->field('file', __('File'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new DriverTutorial);
        
        $countries = Country::pluck('country_name', 'id');
       
        $form->select('country_id', __('Country id'))->options($countries)->rules(function ($form) {
            return 'required';
        });
        $form->text('title', __('Title'));
        $form->textarea('description', __('Description'));
        $form->image('thambnail_image', __('Thambnail Image'))->uniqueName();
        $form->file('file', __('File'))->uniqueName();
        $form->select('status', __('Status'))->options(Status::where('type','general')->pluck('name','id'))->rules(function ($form) {
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
