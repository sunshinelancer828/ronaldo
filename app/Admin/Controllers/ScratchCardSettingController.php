<?php

namespace App\Admin\Controllers;

use App\Models\ScratchCardSetting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ScratchCardSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Scratch Card Setting';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ScratchCardSetting());

        $grid->column('id', __('Id'));
        $grid->column('coupon_type', __('Coupon Type'))->display(function($coupon_type){
            if ($coupon_type == 1) {
                return "<span class='label label-success'>All</span>";
            } if ($coupon_type == 2) {
                return "<span class='label label-info'> Random</span>";
            } 
        });
        $grid->column('lucky_offer', __('Lucky Offer'));
        //$grid->column('random_credit', __('Random Credit'));
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->filter(function ($filter) {
            
            $filter->disableIdFilter();
            
            $filter->like('coupon_type', 'Coupon Type');
            $filter->like('lucky_offer', 'Lucky Offer');
            $filter->like('random_credit', 'Random Credit');
            
        
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
        $show = new Show(ScratchCardSetting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('coupon_type', __('Coupon type'));
        $show->field('lucky_offer', __('Lucky offer'));
        $show->field('random_credit', __('Random credit'));
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
        $form = new Form(new ScratchCardSetting());

        $form->select('coupon_type', __('Coupon Type'))->options([1 => 'All', 2 => 'Random'])->rules(function ($form) {
            return 'required';
        });
        $form->text('lucky_offer', __('Lucky Offer'))->rules(function ($form) {
            return 'required';
        });
        /*$form->text('random_credit', __('Random Credit'))->rules(function ($form) {
            return 'required';
        });*/
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
