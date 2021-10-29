<?php

namespace App\Admin\Controllers;
use App\VehicleCategory;
use App\Driver;
use App\LuckyOffer;
use App\InstantOffer;
use App\ComplaintCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Controllers\ModelForm;

class GeneralController extends Controller
{
    use ModelForm;

    public function GetVehicleCategory()
    {
        $country_id = Driver::where('id',$_GET['q'])->value('country_id');
        return VehicleCategory::where('country_id', $country_id)->get(['id', DB::raw('vehicle_type')]);
    }
    
    public function GetDrivers()
    {
        return Driver::where('status', 1)->where('country_id', $_GET['q'])->get(['id', DB::raw('first_name')]);
    }
    
    public function GetComplaintCategory()
    {
        return ComplaintCategory::where('country_id', $_GET['q'])->get(['id', DB::raw('complaint_category_name')]);
    }
    
    public function GetComplaintSubCategory()
    {
        return ComplaintSubCategory::where('complaint_category_id', $_GET['q'])->get(['id', DB::raw('complaint_sub_category_name')]);
    }
    
    public function getOffers()
    {
        if($_GET['q'] == 1){
        return InstantOffer::get(['id', DB::raw('offer_name')]);
        }else if($_GET['q'] == 2){
        return LuckyOffer::get(['id', DB::raw('offer_name')]);
        }
        
    }
}
