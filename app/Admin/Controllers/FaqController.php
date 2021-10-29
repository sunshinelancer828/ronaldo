<?php

namespace App\Admin\Controllers;

use App\Faq;
use App\UserType;
use App\Status;
use App\Country;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FaqController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Faqs';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new faq);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($countries){
            $country_name = Country::where('id',$countries)->value('country_name');
                return "$country_name";
        });
        $grid->column('user_type_id', __('User Type'))->display(function($types){
            $types = UserType::where('id',$types)->value('type_name');
                return "$types";
        });
        $grid->column('question', __('Question'));
        $grid->column('answer', __('Answer'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
        //$grid->column('created_at', __('Created at'))->hide();
        //$grid->column('updated_at', __('Updated at'))->hide();

        $grid->disableExport();
        $grid->actions(function ($actions) {
        $actions->disableView();
        });
        $grid->filter(function ($filter) {
            $statuses = Status::where('type','general')->pluck('name','id');
            $countries = Country::pluck('country_name', 'id');
            
            
            $filter->disableIdFilter();
            $filter->equal('country_id', 'Country')->select($countries);
            $filter->like('question', 'Question');
            $filter->like('answer', 'Answer');
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
        $show = new Show(faq::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('question', __('Question'));
        $show->field('answer', __('Answer'));
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
        $form = new Form(new faq);
        $statuses = Status::where('type','general')->pluck('name','id');
        $countries = Country::pluck('country_name', 'id');
        $types = UserType::pluck('type_name', 'id');
        
        $form->select('country_id','Country')->options($countries)->rules('required');
        $form->select('user_type_id','User Type')->options($types)->rules('required');
        $form->text('question', __('Question'))->rules('required|max:250');
        $form->textarea('answer', __('Answer'))->rules('required');
        $form->select('status','Status')->options($statuses)->rules('required');

        $form->footer(function ($footer) {
        $footer->disableViewCheck();
        $footer->disableEditingCheck();
        $footer->disableCreatingCheck();

        });

        $form->tools(function (Form\Tools $tools) {
        $tools->disableDelete(); 
        $tools->disableView();
        

        });

        return $form;
    }
}
