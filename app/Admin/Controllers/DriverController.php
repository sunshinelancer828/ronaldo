<?php

namespace App\Admin\Controllers;

use App\Driver;
use App\Status;
use App\Country;
use App\Currency;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;

class DriverController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Drivers';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Driver);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($countries){
            $country_name = Country::where('id',$countries)->value('country_name');
                return "$country_name";
        });
        $grid->column('first_name', __('First Name'));
        $grid->column('last_name', __('Last Name'));
        $grid->column('phone_number', __('Phone number'));
        $grid->column('phone_with_code', __('Phone number with Code'));
        $grid->column('email', __('Email'));
        $grid->column('password', __('Password'))->hide();
        $grid->column('profile_picture', __('Profile picture'))->hide();
        $grid->column('date_of_birth', __('Date of birth'))->hide();
        $grid->column('licence_number', __('Licence number'));
        $grid->column('id_proof', __('Id proof'))->hide();
        $grid->column('address', __('Address'))->hide();
        $grid->column('currency', __('Currency'))->display(function($currency){
            $currency = Currency::where('currency',$currency)->value('currency');
                return "$currency";
        });
        $grid->column('daily', __('Daily'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-info'>Yes</span>";
            } else {
                return "<span class='label label-danger'>No</span>";
            }
        });
        $grid->column('rental', __('Rental'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-info'>Yes</span>";
            } else {
                return "<span class='label label-danger'>No</span>";
            }
        });
        $grid->column('outstation', __('Outstation'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-info'>Yes</span>";
            } else {
                return "<span class='label label-danger'>No</span>";
            }
        });
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
        
        $grid->disableExport();
        //$grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
         $grid->filter(function ($filter) {
         $statuses = Status::where('type','general')->pluck('name','id');
         $countries = Country::pluck('country_name', 'id');

            $filter->disableIdFilter();
            $filter->equal('country_id', 'Country')->select($countries);
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');        
            $filter->equal('phone_number', 'Phone number');        
            $filter->like('email', 'Email');
            $filter->equal('licence_number', 'Licence number');
            $filter->equal('daily', 'Daily')->select([1 => 'Yes', 0 => 'No']);
            $filter->equal('rental', 'Rental')->select([1 => 'Yes', 0 => 'No']);
            $filter->equal('outstation', 'Outstation')->select([1 => 'Yes', 0 => 'No']);
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
        $show = new Show(Driver::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('first_name', __('First Name'));
        $show->field('last_name', __('Last Name'));
        $show->field('phone_number', __('Phone number'));
        $show->field('phone_with_code', __('Phone number'));
        $show->field('email', __('Email'));
        $show->field('password', __('Password'));
        $show->field('profile_picture', __('Profile picture'));
        $show->field('date_of_birth', __('Date of birth'));
        $show->field('licence_number', __('Licence number'));
        $show->field('id_proof', __('Id proof'));
        $show->field('address', __('Address'));
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
        $form = new Form(new Driver);
        $statuses = Status::where('type','general')->pluck('name','id');
        $countries = Country::pluck('country_name', 'id');
        $currencies = Currency::pluck('currency', 'currency');

        $form->select('country_id','Country')->options($countries)->rules('required');
        $form->text('first_name', __('First name'))->rules('required|max:250');
        $form->text('last_name', __('Last name'))->rules('required|max:250');
        $form->select('gender','Gender')->options([1 => 'Male', 2 => 'Female'])->rules('required');
        $form->text('phone_number', __('Phone number'))->rules(function ($form) {
                return 'numeric|digits_between:9,20|required';
        });
         $form->text('phone_with_code', __('Phone number with code'))->rules(function ($form) {
                return 'required';
        });
        $form->email('email', __('Email'))->rules(function ($form) {
                return 'required|max:100';
        });
        $form->password('password', __('Password'))->rules('required|max:250');
        $form->image('profile_picture', __('Profile picture'))->move('drivers/')->uniqueName()->rules('required');
        $form->date('date_of_birth', __('Date of birth'))->default(date('Y-m-d'));
        $form->text('licence_number', __('Licence number'))->rules('required|max:250');
        $form->image('id_proof', __('Id proof'))->rules('required|max:250');
        $form->textarea('address', __('Address'))->rules('required|max:250');
        $form->select('currency','Currency')->options($currencies)->rules('required');
        $form->select('daily','Daily')->options([1 => 'Yes', 0 => 'No'])->default(1)->rules('required');
        $form->select('rental','Rental')->options([1 => 'Yes', 0 => 'No'])->rules('required');
        $form->select('outstation','Outstation')->options([1 => 'Yes', 0 => 'No'])->rules('required');
        $form->select('status','Status')->options($statuses)->rules('required');
        

        $form->saving(function ($form) {
            if($form->password && $form->model()->password != $form->password)
            {
                $form->password = $this->getEncryptedPassword($form->password);
            }
        });
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete(); 
            $tools->disableView();
        });
        $form->saved(function (Form $form) {
            $this->update_status($form->model()->id,$form->model()->status,$form->model()->first_name);
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }

    public function getEncryptedPassword($input, $rounds = 12) {
        $salt = "";
        $saltchars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));
        for ($i = 0; $i < 22; $i++) {
            $salt .= $saltchars[array_rand($saltchars)];
        }
        return crypt($input, sprintf('$2y$%2d$', $rounds) . $salt);
    }
    
    public function update_status($id,$status,$driver_name){
        //$factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();

        // $serviceAccount = ServiceAccount::fromJsonFile(config_path().'/'.env('FIREBASE_FILE'));
        // $firebase = (new Factory)
        // ->withServiceAccount($serviceAccount)
        // ->withDatabaseUri(env('FIREBASE_DB'))
        // ->create();
        // $database = $firebase->getDatabase();
        $newPost = $database
        ->getReference('drivers/'.$id)
        ->update([
            'driver_name' => $driver_name,
            'status' => $status,
            'lat' => 0,
            'lng' => 0,
            'online_status' => 0,
            'booking_status' => 0
        ]);
    }
}
