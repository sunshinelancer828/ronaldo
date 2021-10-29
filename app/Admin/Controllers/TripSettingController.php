<?php

namespace App\Admin\Controllers;

use App\TripSetting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TripSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Trip Settings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TripSetting);

        $grid->column('id', __('Id'));
        $grid->column('admin_commission', __('Admin commission'));
        $grid->column('maximum_searching_time', __('Maximum searching time'));
        $grid->column('booking_searching_radius', __('Booking searching radius'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('created_at')->hide();
        $grid->column('updated_at')->hide();
        $grid->disableRowSelector();
        //$grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableExport();
        $grid->actions(function ($actions) {
        $actions->disableDelete();
        $actions->disableView();
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
        $show = new Show(TripSetting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('driver_commission', __('Driver commission'));
        $show->field('maximum_searching_time', __('Maximum searching time'));
        $show->field('booking_searching_radius', __('Booking searching radius'));
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
        $form = new Form(new TripSetting);

        $form->decimal('admin_commission', __('Admin commission'))->rules('required');
        $form->number('maximum_searching_time', __('Maximum searching time'))->rules('required|max:11');
        $form->decimal('booking_searching_radius', __('Booking searching radius'))->rules('required');
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
