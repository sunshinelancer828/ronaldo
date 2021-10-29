<?php

namespace App\Admin\Controllers;

use App\DriverVehicle;
use App\Status;
use App\Country;
use App\Driver;
use App\VehicleCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
class DriverVehicleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Driver Vehicles';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DriverVehicle);

        $grid->column('id', __('Id'));
        $grid->column('country_id', __('Country'))->display(function($countries){
            $country_name = Country::where('id',$countries)->value('country_name');
                return "$country_name";
        });
        $grid->column('driver_id', __('Driver'))->display(function($drivers){
            $driver_name = Driver::where('id',$drivers)->value('first_name');
                return "$driver_name";
        });
        $grid->column('vehicle_type', __('Vehicle type'))->display(function(){
            $value = VehicleCategory::where('id',$this->vehicle_type)->value('vehicle_type');
            return $value;
        });
        $grid->column('brand', __('Brand'));
        $grid->column('color', __('Color'));
        $grid->column('vehicle_name', __('Vehicle name'));
        $grid->column('vehicle_number', __('Vehicle number'));
        $grid->column('status', __('status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        
        $grid->column('created_at')->hide();
        $grid->column('updated_at')->hide();
        $grid->disableExport();
        $grid->actions(function ($actions) {
        $actions->disableView();
        });
        $grid->filter(function ($filter) {
            $statuses = Status::where('type','general')->pluck('name','id');
            $vehicle_categories= VehicleCategory::pluck('vehicle_type','id');
            $countries = Country::pluck('country_name', 'id');
            $drivers = Driver::pluck('first_name', 'id');


            $filter->disableIdFilter();
            $filter->equal('country_id', 'Country');
            $filter->equal('driver_id', 'Driver');
            $filter->like('vehicle_type', 'Vehicle type')->select($vehicle_categories);
            $filter->like('brand', 'Brand');        
            $filter->like('color', 'Color');        
            $filter->like('vehicle_name', 'Vehicle name');
            $filter->equal('vehicle_number', 'vehicle_number');
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
        $show = new Show(DriverVehicle::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('vehicle_type', __('Vehicle type'));
        $show->field('brand', __('Brand'));
        $show->field('color', __('Color'));
        $show->field('vehicle_name', __('Vehicle name'));
        $show->field('vehicle_number', __('Vehicle number'));
        $show->field('status', __('status'));
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
        $form = new Form(new DriverVehicle);
        $statuses = Status::where('type','general')->pluck('name','id');
        $vehicle_categories= VehicleCategory::pluck('vehicle_type','id');
        $countries = Country::pluck('country_name', 'id');
        $drivers = Driver::pluck('first_name', 'id');
        
        $form->select('country_id','Country')->load('driver_id', '/admin/get-drivers', 'id', 'first_name')->options($countries)->rules(function ($form) {
            return 'required';
        });
        
        $form->select('driver_id', __('Driver'))->load('vehicle_type', '/admin/get-vehicle-category', 'id', 'vehicle_type')->options(function ($id) {
            $driver = Driver::find($id);

            if ($driver) {
                return [$driver->id => $driver->first_name];
            }
        })->rules(function ($form) {
            return 'required';
        });
        $form->select('vehicle_type', __('Vehicle type'))->options(function ($id) {
            $vehicle_type = VehicleCategory::find($id);

            if ($vehicle_type) {
                return [$vehicle_type->id => $vehicle_type->vehicle_type];
            }
        })->rules(function ($form) {
            return 'required';
        });
        $form->text('brand', __('Brand'))->rules('required|max:250');
        $form->text('color', __('Color'))->rules('required|max:250');
        $form->text('vehicle_name', __('Vehicle Name'))->rules('required|max:250');
        $form->text('vehicle_number', __('Vehicle Number'))->rules('required|max:250');
        $form->image('vehicle_image', __('Vehicle Image'))->uniqueName()->move('vehicle_images/');
        $form->select('status', __('status'))->options($statuses)->rules('required');
        
        $form->saved(function (Form $form) {
            $this->update_status($form->model()->vehicle_type,$form->model()->driver_id);
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
    
    public function update_status($vehicle_type,$driver_id){
       // $factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('vehicles/'.$vehicle_type.'/'.$driver_id)
        ->update([
            'booking_status' => 0,
            'driver_id' => $driver_id,
            'vehicle_type' => $vehicle_type,
            'driver_name' => Driver::where('id',$driver_id)->value('first_name'),
            'gender' => Driver::where('id',$driver_id)->value('gender'),
            'lat' => 0,
            'lng' => 0,
            'online_status' => 0,
            'bearing' => 0,
            'pickup_address' => 0,
            'drop_address' => 0,
            'static_map' => "",
            'total' => 0,
            'booking_id' => 0,
            'customer_name' => 0,
            'trip_type' => ""
            
        ]);
    }
}
