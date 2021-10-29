<?php

namespace App\Admin\Controllers;

use App\Status;
use App\Models\TripType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TripTypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Trip Type';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TripType());

        $grid->column('id', __('Id'));
        $grid->column('active_icon', __('Active Icon'))->image();
        $grid->column('Inactive_icon', __('Inactive Icon'))->image();
        $grid->column('name', __('Name'));
         $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();

        });
        
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
         $grid->filter(function ($filter) {
        
            $statuses = Status::where('type','general')->pluck('name','id');
            
            $filter->like('name', 'Name');
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
        $show = new Show(TripType::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('active_icon', __('Active Icon'));
        $show->field('Inactive_icon', __('Inactive Icon'));
        $show->field('name', __('Name'));
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
        $form = new Form(new TripType());

         $statuses = Status::where('type','general')->pluck('name','id');

        $form->image('active_icon', __('Active Icon'))->move('trip_types/')->rules('required');
        $form->image('Inactive_icon', __('Inactive Icon'))->move('trip_types/')->rules('required');
        $form->text('name', __('Name'));
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
