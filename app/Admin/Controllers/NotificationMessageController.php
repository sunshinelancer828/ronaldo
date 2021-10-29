<?php

namespace App\Admin\Controllers;

use App\NotificationMessage;
use App\Status;
use App\UserType;
use App\Country;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class NotificationMessageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Notification Messages';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new NotificationMessage);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($countries){
            $country_name = Country::where('id',$countries)->value('country_name');
                return "$country_name";
        });
        $grid->column('type', __('Type'))->display(function($types){
            $types = UserType::where('id',$types)->value('type_name');
                return "$types";
        });
        $grid->column('title', __('Title'));
        $grid->column('message', __('Message'));
        $grid->column('image', __('Image'))->hide();
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
        $grid->actions(function ($actions) {
        $actions->disableView();
        });
        $grid->filter(function ($filter) {
            $statuses = Status::pluck('name', 'id');
            $filter->disableIdFilter();
            $filter->like('type', 'Type');
            $filter->like('message', 'Message');
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
        $show = new Show(NotificationMessage::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('message', __('Message'));
        $show->field('image', __('Image'));
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
        $form = new Form(new NotificationMessage);
        $statuses = Status::where('type','general')->pluck('name','id');
        $countries = Country::pluck('country_name', 'id');
        $types = UserType::pluck('type_name', 'id');
        
        
        $form->select('country_id','Country')->options($countries)->rules('required');
        $form->select('type', __('Type'))->options($types)->rules('required');
        $form->text('title', __('Title'))->rules('required|max:250');
        $form->textarea('message', __('Message'))->rules('required');
        $form->image('image', __('Image'))->uniqueName()->rules('required');
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
