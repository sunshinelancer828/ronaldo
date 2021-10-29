<?php

namespace App\Admin\Controllers;

use App\ReferralSetting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ReferralSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Referral Settings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ReferralSetting);

        $grid->column('id', __('Id'));
        $grid->column('referral_message', __('Referral message'));
        $grid->column('referral_bonus', __('Referral bonus'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->column('created_at')->hide();
        $grid->column('updated_at')->hide();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
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
        $show = new Show(ReferralSetting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('referral_message', __('Referral message'));
        $show->field('referral_bonus', __('Referral bonus'));
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
        $form = new Form(new ReferralSetting);

        $form->textarea('referral_message', __('Referral message'))->rules('required');
        $form->decimal('referral_bonus', __('Referral bonus'))->rules('required');

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
