<?php

namespace App\Admin\Controllers;

use App\ComplaintSubCategory;
use App\Status;
use App\Country;
use App\ComplaintCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ComplaintSubCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Complaint Sub Categories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ComplaintSubCategory);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($countries){
            $country_name = Country::where('id',$countries)->value('country_name');
                return "$country_name";
        });
        $grid->column('complaint_category', __('Complaint category'))->display(function(){
            $value = ComplaintCategory::where('id',$this->complaint_category)->value('complaint_category_name');
            return $value;
        });
        $grid->column('complaint_sub_category_name', __('Complaint sub category'));
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
            $statuses = Status::where('type','general')->pluck('name','id');
            $complaint_categories = ComplaintCategory::where('status',1)->pluck('complaint_category_name','id');
            $countries = Country::pluck('country_name', 'id');
            
            
            $filter->disableIdFilter();
            $filter->equal('country_id', 'Country')->select($countries);
            $filter->like('complaint_category', 'Complaint category')->select( $complaint_categories);
            $filter->like('complaint_sub_category_name', 'Complaint sub category');
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
        $show = new Show(ComplaintSubCategory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('complaint_category', __('Complaint category'));
        $show->field('complaint_sub_category_name', __('Complaint sub category name'));
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
        $form = new Form(new ComplaintSubCategory);
        $statuses = Status::where('type','general')->pluck('name','id');
        $complaint_categories = ComplaintCategory::where('status',1)->pluck('complaint_category_name','id');
        $countries = Country::pluck('country_name', 'id');
        
        
        $form->select('country_id','Country')->load('complaint_category', '/admin/get_complaint_category', 'id', 'complaint_category_name')->options($countries)->rules(function ($form) {
            return 'required';
        });
        $form->select('complaint_category', __('Complaint category'))->options(function ($id) {
            $category = ComplaintCategory::find($id);

            if ($category) {
                return [$category->id => $category->complaint_category_name];
            }
        })->rules(function ($form) {
            return 'required';
        });
        $form->text('complaint_sub_category_name', __('Complaint sub category'))->rules('required|max:250');
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
