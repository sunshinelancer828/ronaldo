<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Trip;
use App\Models\TripType;
use App\DriverVehicle;
use App\VehicleCategory;
use App\Customer;
use App\UserPromoHistory;
use App\Rating;
use App\PaymentMethod;
use App\CancellationReason;
use App\TripHistory;
use App\PromoCode;
use Validator;

class RideDetailsController extends Controller
{
    public function ride_list(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
            'country_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $dat = Trip::where('customer-id',$input['customer_id'])->get();
         $j=0;
        if(sizeof($dat) > 0){
        foreach($dat as $data){
            // echo $data; exit;
        $dar = Trip::where('id',$data->id)->first();
        $trip[$j]['id'] = $dar->id;
        $trip[$j]['trip_id'] = $dar->trip_id;
        $trip[$j]['pickup_date'] = $dar->pickup_date;
        $trip[$j]['pickup_time'] = $dar->pickup_time;
        $trip[$j]['pickup_location_address'] = $dar->pickup_location_address;
        $trip[$j]['drop_location_address'] = $dar->drop_location_address;
        $trip[$j]['vehicle_id'] = $dar->vehicle-id;
        $trip[$j]['total'] = $dar->total;
        $driver_vehicle = DriverVehicle::where('id',$dar->vehicle-id)->where('driver_id', $dar->driver-id)->first();
        $trip[$j]['vehicle_brand'] = $driver_vehicle['brand'];
        $trip[$j]['vehicle_color'] = $driver_vehicle['color'];
        $trip[$j]['vehicle_name'] = $driver_vehicle['vehicle_name'];
        $trip[$j]['vehicle_number'] = $driver_vehicle['vehicle_number'];
        $trip[$j]['vehicle_type'] = VehicleCategory::where('id',$dar->vehicle_type)->where('country_id', $input['country_id'])->value('vehicle_type');
        $j++;
        } 
        return response()->json([
            "result" => $trip,
            "message" => 'Success',
            "count" => count($trip),
            "status" => 1
        ]);
    }else {
        return response()->json([
            "message" => 'No trips found',
            "status" => 0
        ]);
    }
    }
    
    public function ride_details(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'trip_id' => 'required',
            'country_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $dar = Trip::where('id',$input['trip_id'])->first();
        if($dar){
        $trip['id'] = $dar->id;
        $trip['trip_id'] = $dar->trip_id;
        $trip['pickup_date'] = $dar->pickup_date;
        $trip['pickup_time'] = $dar->pickup_time;
        $trip['pickup_location_address'] = $dar->pickup_location_address;
        $trip['drop_location_address'] = $dar->drop_location_address;
        $trip['vehicle-id'] = $dar->vehicle-id;
        $driver_vehicle = DriverVehicle::where('id',$dar->vehicle-id)->where('driver_id', $dar->driver-id)->first();
        $trip['vehicle_brand'] = $driver_vehicle['brand'];
        $trip['vehicle_color'] = $driver_vehicle['color'];
        $trip['vehicle_name'] = $driver_vehicle['vehicle_name'];
        $trip['vehicle_number'] = $driver_vehicle['vehicle_number'];
        $vehicle_type= VehicleCategory::where('id',$dar->vehicle_type)->where('country_id', $input['country_id'])->first();
        $trip['vehicle_type'] = $vehicle_type['vehicle_type'];
        $trip['base_fare'] = $vehicle_type['base_fare'];
        $trip['price_per_km'] = $vehicle_type['price_per_km'];
        $customer_name = Customer::where('id',$dar->customer-id)->first();
        $trip['first_name'] = $customer_name['first_name'];
        $trip['last_name'] = $customer_name['last_name'];
        $rating = Rating::where('trip_id',$dar->id)->where('customer_id',$dar->customer-id)->where('driver_id',$dar->driver-id)->first();
        $trip['rating'] = $rating['rating'];
        $trip['rating_feedback'] = $rating['rating_feedback'];
        $trip['total'] = $dar->total;
        $trip['sub_total'] = $dar->sub_total;
        $trip['discount'] = $dar->discount;
        $payment_method = PaymentMethod::where('id',$dar->payment_method)->where('country_id', $input['country_id'])->first();
        $trip['payment_mode'] = $payment_method['payment'];
        return response()->json([
            "result" => $trip,
            "message" => 'Success',
            "count" => count($trip),
            "status" => 1
        ]);
    }
    }
    
    public function driver_ride_list(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'driver_id' => 'required',
            'country_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $dat = TripHistory::where('driver_id',$input['driver_id'])->get();
         $j=0;
        if(sizeof($dat) > 0){
        foreach($dat as $data){
            // echo $data; exit;
        $dar = Trip::where('id',$data->trip_id)->first();
        $trip[$j]['id'] = $dar->id;
        $trip[$j]['trip_id'] = $dar->trip_id;
        $trip[$j]['pickup_date'] = $dar->pickup_date;
        $trip[$j]['pickup_time'] = $dar->pickup_time;
        $trip[$j]['pickup_location_address'] = $dar->pickup_location_address;
        $trip[$j]['drop_location_address'] = $dar->drop_location_address;
        $trip[$j]['vehicle_id'] = $dar->vehicle-id;
        $trip[$j]['total'] = $dar->total;
        $driver_vehicle = DriverVehicle::where('id',$dar->vehicle-id)->where('driver_id', $dar->driver-id)->first();
        $trip[$j]['vehicle_brand'] = $driver_vehicle['brand'];
        $trip[$j]['vehicle_color'] = $driver_vehicle['color'];
        $trip[$j]['vehicle_name'] = $driver_vehicle['vehicle_name'];
        $trip[$j]['vehicle_number'] = $driver_vehicle['vehicle_number'];
        $trip[$j]['vehicle_type'] = VehicleCategory::where('id',$dar->vehicle_type)->where('country_id', $input['country_id'])->value('vehicle_type');
        $j++;
        } 
        return response()->json([
            "result" => $trip,
            "message" => 'Success',
            "count" => count($trip),
            "status" => 1
        ]);
    }else {
        return response()->json([
            "message" => 'No trips found',
            "status" => 0
        ]);
    }
    }
    
    public function driver_ride_details(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'trip_id' => 'required',
            'country_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $dar = Trip::where('id',$input['trip_id'])->first();
        if($dar){
        $trip['id'] = $dar->id;
        $trip['trip_id'] = $dar->trip_id;
        $trip['pickup_date'] = $dar->pickup_date;
        $trip['pickup_time'] = $dar->pickup_time;
        $trip['pickup_location_address'] = $dar->pickup_location_address;
        $trip['drop_location_address'] = $dar->drop_location_address;
        $trip['vehicle-id'] = $dar->vehicle-id;
        $driver_vehicle = DriverVehicle::where('id',$dar->vehicle-id)->where('driver_id', $dar->driver-id)->first();
        $trip['vehicle_brand'] = $driver_vehicle['brand'];
        $trip['vehicle_color'] = $driver_vehicle['color'];
        $trip['vehicle_name'] = $driver_vehicle['vehicle_name'];
        $trip['vehicle_number'] = $driver_vehicle['vehicle_number'];
        $vehicle_type= VehicleCategory::where('id',$dar->vehicle_type)->where('country_id', $input['country_id'])->first();
        $trip['vehicle_type'] = $vehicle_type['vehicle_type'];
        $trip['base_fare'] = $vehicle_type['base_fare'];
        $trip['price_per_km'] = $vehicle_type['price_per_km'];
        $customer_name = Customer::where('id',$dar->customer-id)->first();
        $trip['first_name'] = $customer_name['first_name'];
        $trip['last_name'] = $customer_name['last_name'];
        $rating = Rating::where('trip_id',$dar->id)->where('customer_id',$dar->customer-id)->where('driver_id',$dar->driver-id)->first();
        $trip['rating'] = $rating['rating'];
        $trip['rating_feedback'] = $rating['rating_feedback'];
        $trip['total'] = $dar->total;
        $trip['sub_total'] = $dar->sub_total;
        $trip['discount'] = $dar->discount;
        $payment_method = PaymentMethod::where('id',$dar->payment_method)->where('country_id', $input['country_id'])->first();
        $trip['payment_mode'] = $payment_method['payment'];
        return response()->json([
            "result" => $trip,
            "message" => 'Success',
            "count" => count($trip),
            "status" => 1
        ]);
    }
    }
    
    public function get_cancellation_reasons(){
        
        $data = CancellationReason::where('type',1)->get();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "count" => count($data),
            "status" => 1
        ]);
    }
    
    public function get_promo_codes(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'country_id' => 'required',
            'customer_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = [];
        $codes = PromoCode::where('country_id',$input['country_id'])->where('customer_id',$input['customer_id'])->orWhere('country_id',$input['country_id'])->where('customer_id',0)->get();
        foreach($codes as $key => $value){
            $using_count = UserPromoHistory::where('user_id',$input['customer_id'])->where('promo_id',$value->id)->where('status',1)->count();
            if($value->redemptions > $using_count){
                array_push($data,$value);
            }
        }
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "count" => count($data),
            "status" => 1
        ]);
    }
    
    public function get_trip_type()
    {
       
        $data = TripType::where('status',1)->get();
        
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }
    public function sendError($message) {
        $message = $message->all();
        $response['error'] = "validation_error";
        $response['message'] = implode('',$message);
        $response['status'] = "0";
        return response()->json($response, 200);
    } 
}
