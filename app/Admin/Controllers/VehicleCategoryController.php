<?php

namespace App\Admin\Controllers;

use App\VehicleCategory;
use App\Status;
use App\Country;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VehicleCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vehicle Categories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VehicleCategory);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($countries){
            $country_name = Country::where('id',$countries)->value('country_name');
                return "$country_name";
        });
        $grid->column('vehicle_type', __('Vehicle type'));
        $grid->column('base_fare', __('Base fare'));
        $grid->column('price_per_km', __('Price per km'));
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
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            $statuses = Status::where('type','general')->pluck('name','id');
            $countries = Country::pluck('country_name', 'id');
        
            $filter->disableIdFilter();
            $filter->equal('country_id', 'Country')->select($countries);
            $filter->like('vehicle_type', 'vehicle type');
            $filter->equal('base_fare', 'Base fare');
            $filter->equal('price_per_km', 'Price per km');
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
        $show = new Show(VehicleCategory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('vehicle_type', __('Vehicle type'));
        $show->field('base_fare', __('Base fare'));
        $show->field('price_per_km', __('Price per km'));
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
        $form = new Form(new VehicleCategory);
        $statuses = Status::where('type','general')->pluck('name','id');
        $countries = Country::pluck('country_name', 'id');
        
        
        $form->select('country_id','Country')->options($countries)->rules('required');
        $form->text('vehicle_type', __('Vehicle type'))->rules('required|max:250');
        $form->textarea('description', __('Description'))->rules('required');
        $form->decimal('base_fare', __('Base fare'))->rules('required|integer|min:0');
        $form->decimal('price_per_km', __('Price per km'))->rules('required|integer|min:0');
        $form->image('active_icon', __('Active Icon'))->move('vehicle_categories/')->uniquename()->rules('required');
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
