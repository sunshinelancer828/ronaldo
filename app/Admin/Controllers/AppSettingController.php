<?php

namespace App\Admin\Controllers;

use App\AppSetting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AppSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'App Settings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AppSetting);

        $grid->column('id', __('Id'));
        $grid->column('app_name', __('App name'));
        $grid->column('app_version', __('App version'));
        $grid->column('default_currency', __('Default currency'));
        $grid->column('default_currency_symbol', __('Default currency symbol'));
        
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableExport();
        $grid->disableRowSelector();
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
        $show = new Show(AppSetting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('app_name', __('App name'));
        $show->field('logo', __('Logo'));
        $show->field('app_version', __('App version'));
        $show->field('default_currency', __('Default currency'));
        $show->field('default_currency_symbol', __('Default currency symbol'));
        $show->field('login_image', __('Login image'));
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
        $form = new Form(new AppSetting);

        $form->text('app_name', __('App name'))->rules('required|max:250');
        $form->image('logo',__('App Logo'))->uniqueName()->rules('required');
        $form->text('app_version', __('App version'))->rules('required|max:10');
        $form->text('default_currency', __('Default currency'))->rules('required|max:100');
        $form->text('default_currency_symbol', __('Default currency symbol'))->rules('required|max:10');
        //$form->text('razorpay_key', __('Razorpay Key'))->rules('required');
        $form->image('login_image', __('Login image'))->uniqueName()->rules('required');
        $form->textarea('about_us', __('About Us'))->rules('required');
        $form->text('referral_amount', __('referral Amount'))->rules('required');


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
