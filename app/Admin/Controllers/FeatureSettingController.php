<?php

namespace App\Admin\Controllers;

use App\Models\FeatureSetting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FeatureSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Feature Setting';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FeatureSetting());

        $grid->column('id', __('Id'));
        $grid->column('enable_sms', __('Enable Sms'))->display(function($enable_sms){
            if ($enable_sms == 0) {
                return "<span class='label label-success'>Yes</span>";
            } if ($enable_sms == 1) {
                return "<span class='label label-info'>No</span>";
            } 
        });
        $grid->column('enable_mail', __('Enable Mail'))->display(function($enable_mail){
            if ($enable_mail == 0) {
                return "<span class='label label-success'>Yes</span>";
            } if ($enable_mail == 1) {
                return "<span class='label label-info'>No</span>";
            } 
        });
        $grid->column('enable_referral_module', __('Enable Referral Module'))->display(function($enable_referral_module){
            if ($enable_referral_module == 0) {
                return "<span class='label label-success'>Yes</span>";
            } if ($enable_referral_module == 1) {
                return "<span class='label label-info'>No</span>";
            } 
        });
        //$grid->column('created_at', __('Created at'))->hide();
        //$grid->column('updated_at', __('Updated at'))->hide();
        $grid->disableExport();
        $grid->actions(function ($actions) {
        $actions->disableView();
        });

         $grid->filter(function ($filter) {
            //Get All status
            
            $filter->like('enable_sms', 'Enable Sms')->select([0=> 'Yes',1 => 'No']);
            $filter->like('enable_mail', 'Enable Mail')->select([0=> 'Yes',1 => 'No']);
            $filter->like('enable_referral_module', 'Enable Referral Module')->select([0=> 'Yes',1 => 'No']);
            
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
        $show = new Show(FeatureSetting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('enable_sms', __('Enable sms'));
        $show->field('enable_mail', __('Enable mail'));
        $show->field('enable_referral_module', __('Enable referral module'));
        $show->field('cancellation_fee', __('Cancellation fee'));
        $show->field('contact_showing', __('Contact showing'));
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
        $form = new Form(new FeatureSetting());

        $form->select('enable_sms', __('Enable Sms'))->options([0 => 'Yes',1 => 'No'])->rules(function ($form) {
            return 'required';
        });
        $form->select('enable_mail', __('Enable Mail'))->options([0 => 'Yes',1 => 'No'])->rules(function ($form) {
            return 'required';
        });
        $form->select('enable_referral_module', __('Enable Referral Module'))->options([0 => 'Yes',1 => 'No'])->rules(function ($form) {
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
