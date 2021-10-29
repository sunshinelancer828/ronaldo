<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Faq;
use App\Trip;
use App\Customer;
use App\AppSetting;
use App\Country;
use App\Status;
use App\Currency;
use App\Models\DriverTripRequest;
use App\UserPromoHistory;
use App\PaymentMethod;
use App\DriverEarning;
use App\DriverWalletHistory;
use App\BookingStatus;
use App\PaymentHistory;
use App\Driver;
use App\TripCancellation;
use App\DriverVehicle;
use App\TripSetting;
use App\TripRequest;
use App\UserType;
use App\Models\ScratchCardSetting;
use App\Models\CustomerOffer;
use App\Models\LuckyOffer;
use App\InstantOffer;
use App\TripType;
use Validator;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use DateTime;
use DateTimeZone;
use App\CustomerWalletHistory;
class BookingController extends Controller
{
    public function ride_confirm(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'km' => 'required',
            'vehicle_type' => 'required',
            'customer_id' => 'required',
            'promo' => 'required',
            'country_id' => 'required',
            'pickup_address' => 'required',
            'pickup_date' => 'required',
            'pickup_lat' => 'required',
            'pickup_lng' => 'required',
            'trip_type' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $input['pickup_date'] = date("Y-m-d H:i:s", strtotime($input['pickup_date']));
        $current_date = $this->get_date($input['country_id']);
        $interval_time = $this->date_difference($input['pickup_date'],$current_date);
        if($interval_time <= 30){
            $input['booking_type'] = 1;
            $input['status'] = 1;
        }else{
            $input['booking_type'] = 2;
            $input['status'] = 2;
        }
        //$factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        $drivers = $database->getReference('/vehicles/'.$input['vehicle_type'])
                    ->getSnapshot()->getValue();
        
        $min_distance = 0;
        $min_driver_id = 0;
        $booking_searching_radius = TripSetting::value('booking_searching_radius');
        
        foreach($drivers as $key => $value){
            if($value && array_key_exists('gender', $value)){
            if($value['gender'] == $input['filter'] || $input['filter'] == 0){
            
                $distance = $this->distance($input['pickup_lat'], $input['pickup_lng'], $value['lat'], $value['lng'], 'K') ;
                if($distance <= $booking_searching_radius && $value['online_status'] == 1 && $value['booking_status'] == 0){
                    if($min_distance == 0){
                        $min_distance = $distance;
                        $min_driver_id = $value['driver_id'];
                    }else if($distance < $min_distance){
                        $min_distance = $distance;
                        $min_driver_id = $value['driver_id'];
                    }
                }
                
            }
            }
        }
        if($min_driver_id == 0){
            return response()->json([
                "message" => 'Sorry drivers not available right now',
                "status" => 0
            ]);
        }
        
        $url = 'https://maps.googleapis.com/maps/api/staticmap?center='.$input['pickup_lat'].','.$input['pickup_lng'].'&zoom=16&size=600x300&maptype=roadmap&markers=color:red%7Clabel:L%7C'.$input['pickup_lat'].','.$input['pickup_lng'].'&key='.env('MAP_KEY');
            $img = 'trip_request_static_map/'.md5(time()).'.png';
        file_put_contents('uploads/'.$img, file_get_contents($url));
        
        if($input['trip_type'] == 1){
            $fares = $this->calculate_daily_fare($input['vehicle_type'],$input['km'],$input['promo'],$input['country_id']);
        }
        
        if($input['trip_type'] == 2){
            $fares = $this->calculate_rental_fare($input['vehicle_type'],$input['package_id'],$input['promo'],$input['country_id'],0,0);
        }
        
        if($input['trip_type'] == 3){
            $fares = $this->calculate_outstation_fare($input['vehicle_type'],$input['km'],$input['promo'],$input['country_id'],1);
        }
        
        $booking_request = $input;
        $booking_request['distance'] = $input['km'];
        unset($booking_request['km']);
        $booking_request['total'] = $fares['total_fare'];
        $booking_request['sub_total'] = $fares['fare'];
        $booking_request['discount'] = $fares['discount'];
        $booking_request['tax'] = $fares['tax'];
        $booking_request['static_map'] = $img;

        $id = TripRequest::create($booking_request)->id;
        
        if($input['booking_type'] == 2){
            return response()->json([
                "result" => $id,
                "booking_type" => $input['booking_type'],
                "message" => 'Success',
                "status" => 1
            ]);
        }
        
        $newPost = $database
        ->getReference('customers/'.$input['customer_id'])
        ->update([
            'booking_id' => $id,
            'booking_status' => 1
        ]);
        if($input['trip_type'] == 2){
            $input['drop_address'] = "Sorry, customer not mentioned";
        }
        $newPost = $database
        ->getReference('vehicles/'.$input['vehicle_type'].'/'.$min_driver_id)
        ->update([
            'booking_id' => $id,
            'booking_status' => 1,
            'pickup_address' => $input['pickup_address'],
            'drop_address' => $input['drop_address'],
            'total' => $fares['total_fare'],
            'customer_name' => Customer::where('id',$input['customer_id'])->value('first_name'),
            'static_map' => $img,
            'trip_type'=>DB::table('trip_types')->where('id',$input['trip_type'])->value('name')
        ]);
        
        return response()->json([
            "result" => $id,
            "booking_type" => $input['booking_type'],
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function ride_later(){
        $data = TripRequest::select('id','country_id','pickup_date')->where('status',2)->where('booking_type',2)->orderBy('pickup_date')->get();
    
        $timeout_trip_time = 15;
        $future_trip_time = 15;

        foreach($data as $key => $value){
            $value->pickup_date = date("Y-m-d H:i:s", strtotime($value->pickup_date));
            $current_date = $this->get_date($value->country_id);
            
            $interval_time = $this->date_difference($value->pickup_date,$current_date);
            
            if($value->pickup_date > $current_date) {
                if($interval_time <= $future_trip_time){
                    $this->find_driver($value->id);
                }
            }else{
                if($interval_time <= $timeout_trip_time){
                    $this->find_driver($value->id);
                }else{
                    TripRequest::where('id',$value->id)->update([ 'status' => 5 ]);
                }
            }
        }

    }
    
    public function find_driver($trip_request_id){
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        $trip_request = TripRequest::where('id',$trip_request_id)->first();
        
        $drivers = $database->getReference('/vehicles/'.$trip_request->vehicle_type)
                    ->getSnapshot()->getValue();
                    
        $rejected_drivers = DriverTripRequest::where('trip_request_id',$trip_request_id)->where('status',4)->pluck('driver_id')->toArray();
        
        $min_distance = 0;
        $min_driver_id = 0;
        $booking_searching_radius = TripSetting::value('booking_searching_radius');
        
        foreach($drivers as $key => $value){
            if($value && array_key_exists('gender', $value)){
            //if($value['gender'] == $input['filter'] || $input['filter'] == 0){
            if (!in_array($value['driver_id'], $rejected_drivers)){
                
                $distance = $this->distance($trip_request->pickup_lat, $trip_request->pickup_lng, $value['lat'], $value['lng'], 'K') ;
                
                if($distance <= $booking_searching_radius && $value['online_status'] == 1 && $value['booking_status'] == 0){
                    
                    if($min_distance == 0){
                        $min_distance = $distance;
                        $min_driver_id = $value['driver_id'];
                    }else if($distance < $min_distance){
                        $min_distance = $distance;
                        $min_driver_id = $value['driver_id'];
                    }
                }
            }
            }
            //}
        }
        
        if($min_driver_id == 0){
            $newPost = $database
            ->getReference('customers/'.$trip_request->customer_id)
            ->update([
                'booking_id' => 0,
                'booking_status' => 0
            ]);
        
            return 0;
        }
        
        if($trip_request->trip_type == 2){
            $trip_request->drop_address = "Sorry, customer not mentioned";
        }
        
        $newPost = $database
        ->getReference('vehicles/'.$trip_request->vehicle_type.'/'.$min_driver_id)
        ->update([
            'booking_id' => $trip_request->id,
            'booking_status' => 1,
            'pickup_address' => $trip_request->pickup_address,
            'drop_address' => $trip_request->drop_address,
            'total' => $trip_request->total,
            'static_map' => $trip_request->static_map,
            'customer_name' => Customer::where('id',$trip_request->customer_id)->value('first_name'),
            'trip_type'=>DB::table('trip_types')->where('id',$trip_request->trip_type)->value('name')
        ]);
        
        return $trip_request->id;
    }
    
    public function get_date($country_id){
        $date = new DateTime();
        $usersTimezone = Country::where('id',$country_id)->value('timezone');
        
        if($usersTimezone){
            // Convert timezone
            $tz = new DateTimeZone($usersTimezone);
            $date->setTimeZone($tz);
            
            // Output date after
            return $date->format('Y-m-d H:i:s');
        }else{
            return $date->format('Y-m-d H:i:s');
        }
        
    }
    
    public function date_difference($date1,$date2){
        $date1=date_create($date1);
        $date2=date_create($date2);
        $diff=date_diff($date1,$date2);
        $days = $diff->format("%a");
        $hours = $diff->format("%h");
        $min = $diff->format("%i");
        
        $minutes = $days * 24 * 60;
        $minutes += $hours * 60;
        $minutes += $min;
        return $minutes;
    }
    
    public function trip_reject(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'trip_id' => 'required',
            'driver_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $trip = TripRequest::where('id',$input['trip_id'])->first();
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        /*$newPost = $database
        ->getReference('customers/'.$trip->customer_id)
        ->update([
            'booking_id' => 0,
            'booking_status' => 0
        ]);*/
        
        $newPost = $database
        ->getReference('drivers/'.$input['driver_id'])
        ->update([
            'booking_status' => 0
        ]);
        
        $newPost = $database
        ->getReference('vehicles/'.$trip->vehicle_type.'/'.$input['driver_id'])
        ->update([
            'booking_id' => 0,
            'booking_status' => 0,
            'pickup_address' => "",
            'drop_address' => "",
            'total' => 0,
            'customer_name' => "",
            "static_map" => "",
            'trip_type'=>""
        ]);
        
        if($trip->booking_type == 1){
            TripRequest::where('id',$input['trip_id'])->update([ 'status' => 4 ]);
        }
        
        
        $data['driver_id'] = $input['driver_id'];
        $data['trip_request_id'] = $input['trip_id'];
        $data['status'] = 4;
        
        DriverTripRequest::create($data);
        $this->find_driver($input['trip_id']);
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function trip_accept(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'trip_id' => 'required',
            'driver_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $trip = TripRequest::where('id',$input['trip_id'])->first()->toArray();
        $customer_id = TripRequest::where('id',$input['trip_id'])->value('customer_id');
        $phone_with_code = Customer::where('id',$customer_id)->value('phone_with_code');
        
        $data = $trip;
        $data['driver_id'] = $input['driver_id'];
        $data['promo_code'] = $data['promo'];
        unset($data['promo']);
        unset($data['id']);
        $data['pickup_date'] = $trip['pickup_date'];
        $data['country_id'] = $trip['country_id'];
        $data['package_id'] = $trip['package_id'];
        $data['trip_type'] = $trip['trip_type'];
        $data['status'] = 1;
        $data['vehicle_type'] = $trip['vehicle_type'];
        $data['vehicle_id'] = DB::table('driver_vehicles')->where('driver_id',$input['driver_id'])->value('id');
        $data['otp'] = $otp = rand(1000,9999);
        $message = "Hi ".env('APP_NAME')." , Your OTP code is:  ".$data['otp'];
            $this->sendSms($phone_with_code,$message);
        $id = Trip::create($data)->id;
        
        if($data['promo_code']){
            $user_promo['user_id'] = $data['customer_id'];
            $user_promo['promo_id'] = $data['promo_code'];
            $user_promo['status'] = 1;
            UserPromoHistory::create($user_promo);
        }
        
        $trip_id = str_pad($id,6,"0",STR_PAD_LEFT);
        Trip::where('id',$id)->update([ 'trip_id' => $trip_id ]);
        
        
        //Firebase
        //$factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        
        
        $trip_details = Trip::where('id',$id)->first();
        $customer = Customer::where('id',$trip_details->customer_id)->first();
        $driver = Driver::where('id',$trip_details->driver_id)->first();
        $vehicle = DriverVehicle::where('id',$trip_details->vehicle_id)->first();
        $current_status = BookingStatus::where('id',1)->first();
        $new_status = BookingStatus::where('id',2)->first();
        $payment_method = PaymentMethod::where('id',$trip_details->payment_method)->value('Payment');
        
        $data['driver_id'] = $input['driver_id'];
        $data['trip_request_id'] = $input['trip_id'];
        $data['status'] = 3;
        
        DriverTripRequest::create($data);
        
        //Trip Firebase
        $data = [];
        $data['id'] = $id;
        $data['trip_id'] = $trip_id;
        $data['trip_type'] = $trip_details->trip_type;
        $data['customer_id'] = $trip_details->customer_id;
        $data['customer_name'] = $customer->first_name;
        $data['customer_profile_picture'] = $customer->profile_picture;
        $data['customer_phone_number'] = $customer->phone_number;
        $data['driver_id'] = $trip_details->driver_id;
        $data['driver_name'] = $driver->first_name;
        $data['driver_profile_picture'] = $driver->profile_picture;
        $data['driver_phone_number'] = $driver->phone_number;
        $data['pickup_address'] = $trip_details->pickup_address;
        $data['pickup_lat'] = $trip_details->pickup_lat;
        $data['pickup_lng'] = $trip_details->pickup_lng;
        $data['drop_address'] = $trip_details->drop_address;
        $data['drop_lat'] = $trip_details->drop_lat;
        $data['drop_lng'] = $trip_details->drop_lng;
        $data['payment_method'] = $payment_method;
        $data['pickup_date'] = $trip_details->pickup_date;
        $data['total'] = $trip_details->total;
        $data['collection_amount'] = $trip_details->total;
        $data['vehicle_id'] = $trip_details->vehicle_id;
        $data['otp'] = $trip_details->otp;
        $data['vehicle_image'] = $vehicle->vehicle_image;
        $data['vehicle_number'] = $vehicle->vehicle_number;
        $data['vehicle_color'] = $vehicle->color;
        $data['vehicle_name'] = $vehicle->vehicle_name;
        $data['customer_status_name'] = $current_status->customer_status_name;
        $data['driver_status_name'] = $current_status->status_name;
        $data['status'] = 1;
        $data['new_driver_status_name'] = $new_status->status_name;
        $data['new_status'] = 2;
        $data['driver_lat'] = 0;
        $data['driver_lng'] = 0;
        $data['bearing'] = 0;
        
        $newPost = $database
        ->getReference('trips/'.$trip_details->id)
        ->update($data);
        
        $newPost = $database
        ->getReference('customers/'.$trip['customer_id'])
        ->update([
            'booking_id' => $id,
            'booking_status' => 2
        ]);
        
        $newPost = $database
        ->getReference('drivers/'.$input['driver_id'])
        ->update([
            'booking_status' => 2
        ]);
        
        $newPost = $database
        ->getReference('vehicles/'.$trip['vehicle_type'].'/'.$input['driver_id'])
        ->update([
            'booking_id' => $id,
            'booking_status' => 2
        ]);
        
        TripRequest::where('id',$input['trip_id'])->update([ 'status' => 3 ]);
        
        $this->send_fcm($current_status->status_name,$current_status->customer_status_name,$customer->fcm_token);
        
        return response()->json([
            "result" => $id,
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function trip_cancel_by_customer(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'trip_id' => 'required',
            'status' => 'required',
            'reason_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['trip_id'] = $input['trip_id'];
        $data['reason_id'] = $input['reason_id'];
        $data['cancelled_by'] = 1;
        
        TripCancellation::create($data);
        
        Trip::where('id',$input['trip_id'])->update([ 'status' => $input['status']]);
        
        $trip = Trip::where('id',$input['trip_id'])->first();
        
        //Firebase
        //$factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        $newPost = $database
        ->getReference('customers/'.$trip->customer_id)
        ->update([
            'booking_id' => 0,
            'booking_status' => 0
        ]);
        
        $newPost = $database
        ->getReference('drivers/'.$trip->driver_id)
        ->update([
            'booking_status' => 0
        ]);
        
        $vehicle_type = DriverVehicle::where('id',$trip->vehicle_id)->value('vehicle_type');
        
        $newPost = $database
        ->getReference('vehicles/'.$vehicle_type.'/'.$trip->driver_id)
        ->update([
            'booking_id' => 0,
            'booking_status' => 0,
            'customer_name' => '',
            'pickup_address' => '',
            'drop_address' => '',
            'total' => '',
            'trip_type'=>''
        ]);
        
        $newPost = $database
        ->getReference('trips/'.$input['trip_id'])
        ->update([
            'status' => $input['status']
        ]);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function get_fare(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'trip_type' => 'required',
            'km' => 'required',
            'vehicle_type' => 'required',
            'promo' => 'required',
            'country_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        if($input['trip_type'] == 1){
            $fares = $this->calculate_daily_fare($input['vehicle_type'],$input['km'],$input['promo'],$input['country_id']);
        }else if($input['trip_type'] == 2){
            $fares = $this->calculate_rental_fare($input['vehicle_type'],$input['package_id'],$input['promo'],$input['country_id'],0,0);
        }else if($input['trip_type'] == 3){
            $fares = $this->calculate_outstation_fare($input['vehicle_type'],$input['km'],$input['promo'],$input['country_id'],$input['days']);
        }
        
        
        return response()->json([
            "result" => $fares,
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function calculate_daily_fare($vehicle_type,$km,$promo,$country_id){
        
        $data = [];
        $vehicle = DB::table('daily_fare_management')->where('id',$vehicle_type)->first();
        
        if(is_object($vehicle)){
            $data['base_fare'] = number_format((float)$vehicle->base_fare, 2, '.', '');
            $data['km'] = $km;
            $data['price_per_km'] = number_format((float)$vehicle->price_per_km, 2, '.', '');
            $additional_fare = number_format($data['km']) * $data['price_per_km'];
            $data['additional_fare'] = number_format((float)$additional_fare, 2, '.', '');
            $fare =  $data['base_fare'] + $data['additional_fare'];
            $data['fare'] = number_format((float)$fare, 2, '.', '');
            
            //Tax
            $taxes = DB::table('tax_lists')->where('id',$country_id)->get();
            $total_tax = 0.00;
            if(count($taxes)){
                foreach($taxes as $key => $value){
                    $total_tax = $total_tax + ($value->percent / 100) * $data['fare'];
                }
            }
            $data['tax'] = number_format((float)$total_tax, 2, '.', '');
            $total_fare = $data['tax'] + $data['fare'];
            $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
        }
        
        if($promo == 0){
            $data['discount'] = 0.00;
        }else{
            $data['discount'] = 0.00;
            $promo = DB::table('promo_codes')->where('id',$promo)->first();
            if($promo->promo_type == 5){
                $total_fare = $data['total_fare'] - $promo->discount;
                if($total_fare > 0){
                    $data['discount'] = number_format((float)$promo->discount, 2, '.', '');
                    $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
                }else{
                    $data['discount'] = number_format((float)$data['total_fare'], 2, '.', '');
                    $data['total_fare'] = 0.00;
                }
            }else{
                $discount = ($promo->discount / 100) * $data['total_fare'];
                $total_fare = $data['total_fare'] - $discount;
                $data['discount'] = number_format((float)$discount, 2, '.', '');
                $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
            }
        }
        
        return $data;
    }
    
    public function calculate_rental_fare($vehicle_type,$package_id,$promo,$country_id,$extra_km,$extra_hour){
        
        $data = [];
        $package_price = DB::table('rental_fare_management')->where('package_id',$package_id)->first();
        
        if(is_object($package_price)){
            $data['price_per_km'] = number_format((float)$package_price->price_per_km, 2, '.', '');
            $data['price_per_hour'] = number_format((float)$package_price->price_per_hour, 2, '.', '');
            $data['base_fare'] = number_format((float)$package_price->package_price, 2, '.', '');
            
            $additional_km_fare = $extra_km * $data['price_per_km'];
            $data['additional_km_fare'] = number_format((float)$additional_km_fare, 2, '.', '');
            $additional_hour_fare = $extra_hour * $data['price_per_hour'];
            $data['additional_hour_fare'] = number_format((float)$additional_hour_fare, 2, '.', '');
            
            $data['price_per_hour'] = number_format((float)$package_price->price_per_hour, 2, '.', '');
            $fare = $data['additional_km_fare'] + $data['additional_hour_fare'] + $data['base_fare'];
            $data['fare'] = number_format((float)$fare, 2, '.', '');
            
            //Tax
            $taxes = DB::table('tax_lists')->where('id',$country_id)->get();
            $total_tax = 0.00;
            if(count($taxes)){
                foreach($taxes as $key => $value){
                    $total_tax = $total_tax + ($value->percent / 100) * $data['fare'];
                }
            }
            $data['tax'] = number_format((float)$total_tax, 2, '.', '');
            $total_fare = $data['tax'] + $data['fare'];
            $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
        }
        
        if($promo == 0){
            $data['discount'] = 0.00;
        }else{
            $data['discount'] = 0.00;
            $promo = DB::table('promo_codes')->where('id',$promo)->first();
            if($promo->promo_type == 5){
                $total_fare = $data['total_fare'] - $promo->discount;
                if($total_fare > 0){
                    $data['discount'] = number_format((float)$promo->discount, 2, '.', '');
                    $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
                }else{
                    $data['discount'] = number_format((float)$data['total_fare'], 2, '.', '');
                    $data['total_fare'] = 0.00;
                }
            }else{
                $discount = ($promo->discount / 100) * $data['total_fare'];
                $total_fare = $data['total_fare'] - $discount;
                $data['discount'] = number_format((float)$discount, 2, '.', '');
                $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
            }
        }
        
        return $data;
    }
    
    public function calculate_outstation_fare($vehicle_type,$km,$promo,$country_id,$days){
        
        $data = [];
        $vehicle = DB::table('outstation_fare_management')->where('id',$vehicle_type)->first();
        
        if(is_object($vehicle)){
            $data['base_fare'] = number_format((float)$vehicle->base_fare, 2, '.', '');
            $data['km'] = $km;
            $data['price_per_km'] = number_format((float)$vehicle->price_per_km, 2, '.', '');
            $data['driver_allowance'] = number_format((float)$vehicle->driver_allowance, 2, '.', '');
            $data['driver_allowance'] = $data['driver_allowance'] * $days;
            $additional_fare = number_format($data['km']) * $data['price_per_km'];
            $additional_fare = $additional_fare * 2;
            $data['additional_fare'] = number_format((float)$additional_fare, 2, '.', '');
            $fare =  $data['base_fare'] + $data['additional_fare'] + $data['driver_allowance'];
            $data['fare'] = number_format((float)$fare, 2, '.', '');
            
            //Tax
            $taxes = DB::table('tax_lists')->where('id',$country_id)->get();
            $total_tax = 0.00;
            if(count($taxes)){
                foreach($taxes as $key => $value){
                    $total_tax = $total_tax + ($value->percent / 100) * $data['fare'];
                }
            }
            $data['tax'] = number_format((float)$total_tax, 2, '.', '');
            $total_fare = $data['tax'] + $data['fare'];
            $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
        }
        
        if($promo == 0){
            $data['discount'] = 0.00;
        }else{
            $data['discount'] = 0.00;
            $promo = DB::table('promo_codes')->where('id',$promo)->first();
            if($promo->promo_type == 5){
                $total_fare = $data['total_fare'] - $promo->discount;
                if($total_fare > 0){
                    $data['discount'] = number_format((float)$promo->discount, 2, '.', '');
                    $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
                }else{
                    $data['discount'] = number_format((float)$data['total_fare'], 2, '.', '');
                    $data['total_fare'] = 0.00;
                }
            }else{
                $discount = ($promo->discount / 100) * $data['total_fare'];
                $total_fare = $data['total_fare'] - $discount;
                $data['discount'] = number_format((float)$discount, 2, '.', '');
                $data['total_fare'] = number_format((float)$total_fare, 2, '.', '');
            }
        }
        
        return $data;
    }
    
    public function customer_bookings(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('trips')
                ->leftJoin('customers','customers.id','trips.customer_id')
                ->leftJoin('drivers','drivers.id','trips.driver_id')
                ->leftJoin('payment_methods','payment_methods.id','trips.payment_method')
                ->leftJoin('trip_types','trip_types.id','trips.trip_type')
                ->leftJoin('driver_vehicles','driver_vehicles.id','trips.vehicle_id')
                ->leftJoin('vehicle_categories','vehicle_categories.id','driver_vehicles.vehicle_type')
                ->leftJoin('booking_statuses','booking_statuses.id','trips.status')
                ->select('trips.*','customers.first_name as customer_name','drivers.first_name as driver_name','drivers.profile_picture','payment_methods.payment','driver_vehicles.brand','driver_vehicles.color','driver_vehicles.vehicle_name','driver_vehicles.vehicle_number','trip_types.name as trip_type','booking_statuses.status_name','vehicle_categories.vehicle_type')
                ->where('trips.customer_id',$input['customer_id'])->orderBy('id', 'DESC')
                ->get();
                
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function driver_bookings(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'driver_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('trips')
                ->leftJoin('customers','customers.id','trips.customer_id')
                ->leftJoin('drivers','drivers.id','trips.driver_id')
                ->leftJoin('payment_methods','payment_methods.id','trips.payment_method')
                ->leftJoin('driver_vehicles','driver_vehicles.id','trips.vehicle_id')
                ->leftJoin('vehicle_categories','vehicle_categories.id','driver_vehicles.vehicle_type')
                ->leftJoin('booking_statuses','booking_statuses.id','trips.status')
                ->select('trips.*','customers.first_name as customer_name','drivers.first_name as driver_name','customers.profile_picture','payment_methods.payment','driver_vehicles.brand','driver_vehicles.color','driver_vehicles.vehicle_name','driver_vehicles.vehicle_number','booking_statuses.status_name','vehicle_categories.vehicle_type')
                ->where('trips.driver_id',$input['driver_id'])->orderBy('id', 'DESC')
                ->get();
                
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function change_statuses(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'trip_id' => 'required',
            'status' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        Trip::where('id',$input['trip_id'])->update([ 'status' => $input['status']]);
        
        if($input['status'] == 3){
            Trip::where('id',$input['trip_id'])->update([ 'start_time' => date('Y-m-d H:i:s'), 'actual_pickup_address' => $input['address'], 'actual_pickup_lat' => $input['lat'], 'actual_pickup_lng' => $input['lng'] ]);
        }
        
        $trip = Trip::where('id',$input['trip_id'])->first();
        
        if($input['status'] == 4){
            Trip::where('id',$input['trip_id'])->update([ 'end_time' => date('Y-m-d H:i:s'),'actual_drop_address' => $input['address'], 'actual_drop_lat' => $input['lat'], 'actual_drop_lng' => $input['lng'] ]);
            
            $this->calculate_fare($input['trip_id']);
            
            $newPost = $database
            ->getReference('customers/'.$trip->customer_id)
            ->update([
                'booking_id' => 0,
                'booking_status' => 0
            ]);
            
            $newPost = $database
            ->getReference('drivers/'.$trip->driver_id)
            ->update([
                'booking_status' => 0
            ]);
            $vehicle_type = DriverVehicle::where('id',$trip->vehicle_id)->value('vehicle_type');
            
            $newPost = $database
            ->getReference('vehicles/'.$vehicle_type.'/'.$trip->driver_id)
            ->update([
                'booking_id' => 0,
                'booking_status' => 0,
                'pickup_address' => "",
                'customer_name' => "",
                'drop_address' => "",
                'total' => ""
            ]);
        }
        
        if($input['status'] != 5){
            $current_status = BookingStatus::where('id',$input['status'])->first();
            $new_status = BookingStatus::where('id',$input['status']+1)->first();
        }else{
            $current_status = BookingStatus::where('id',$input['status'])->first();
            $new_status = BookingStatus::where('id',$input['status'])->first();
            
            $this->calculate_earnings($input['trip_id']);
            $this->create_reward($input['trip_id']);
        }
        
        $fcm_token = Customer::where('id',$trip->customer_id)->value('fcm_token');
        
        if($fcm_token){
            $this->send_fcm($current_status->status_name,$current_status->customer_status_name,$fcm_token);
        }
        
        
        $newPost = $database
        ->getReference('trips/'.$input['trip_id'])
        ->update([
            'customer_status_name' => $current_status->customer_status_name,
            'status' => $current_status->id,
            'driver_status_name' => $current_status->status_name,
            'new_status' => $new_status->id,
            'new_driver_status_name' => $new_status->status_name
        ]);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function calculate_fare($trip_id){
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        
        $trip = Trip::where('id',$trip_id)->first();
        $distance = $this->get_distance($trip_id);
        if($distance != 0 && is_object($trip)){
            if($trip->trip_type == 1){
                $fare = $this->calculate_daily_fare($trip->vehicle_type,$distance,$trip->promo_code,$trip->country_id);
            }else if($trip->trip_type == 2){
                $input['start_date'] = date("Y-m-d H:i:s", strtotime($trip->start_date));
                $input['end_date'] = date("Y-m-d H:i:s", strtotime($trip->end_date));
                $interval_time = $this->date_difference($input['start_date'],$input['end_date']);
                
                $hours = ceil($interval_time/60);
                
               $fare = $this->calculate_rental_fare($trip->vehicle_type,$trip->package_id,$trip->promo_code,$trip->country_id,$distance,$hours);
            }else if($trip->trip_type == 3){
                $input['start_date'] = date("Y-m-d H:i:s", strtotime($trip->start_date));
                $input['end_date'] = date("Y-m-d H:i:s", strtotime($trip->end_date));
                $interval_time = $this->date_difference($input['start_date'],$input['end_date']);
                
                $days = ceil($interval_time/1440);
                
                $fare = $this->calculate_outstation_fare($trip->vehicle_type,$distance,$trip->promo_code,$trip->country_id,$days);
            
            }
            
            
            if($fare['total_fare'] > $trip->total){
                $collection_amount = $this->update_payment_mode($trip->id,$trip,$fare['total_fare']);
                Trip::where('id',$trip_id)->update([ 'total' => $fare['total_fare'], 'sub_total' => $fare['fare'], 'tax' => $fare['tax'], 'discount' => $fare['discount'], 'distance' => $distance]);
                $data['total'] = $fare['total_fare'];
                $data['actual_pickup_address'] = $trip->actual_pickup_address;
                $data['actual_drop_address'] = $trip->actual_drop_address;
                $data['collection_amount'] = $collection_amount;
                $newPost = $database
                ->getReference('trips/'.$trip_id)
                ->update($data);
                
            }else{
                $collection_amount = $this->update_payment_mode($trip->id,$trip,$trip->total);
                $data['actual_pickup_address'] = $trip->actual_pickup_address;
                $data['actual_drop_address'] = $trip->actual_drop_address;
                $data['collection_amount'] = $collection_amount;
                $newPost = $database
                ->getReference('trips/'.$trip_id)
                ->update($data);
                
            }
        }
        
    }
    
    public function update_payment_mode($trip_id,$trip,$fare){
        $customer_wallet = Customer::where('id',$trip->customer_id)->value('wallet');
        if($customer_wallet && $customer_wallet > 0){
            $remaining_fare = $customer_wallet - $fare;
            $remaining_fare = number_format((float)$remaining_fare, 2, '.', '');
            if($remaining_fare >= 0){
                Trip::where('id',$trip_id)->update(['payment_method' => PaymentMethod::where('country_id',$trip->country_id)->where('payment_type',2)->value('id') ]);
                Customer::where('id',$trip->customer_id)->update([ 'wallet' => $remaining_fare ]);
               
                $payment_history['trip_id'] = $trip_id;
                $payment_history['mode'] = "Wallet";
                $payment_history['amount'] = $fare;
                PaymentHistory::create($payment_history);
                CustomerWalletHistory::create(['country_id' => $trip->country_id, 'customer_id' => $trip->customer_id, 'type' => 2, 'message' => 'Amount debited for booking(#'.$trip->trip_id.')', 'amount' => $fare, 'transaction_type' => 3 ]);
                return 0;
            }else{
                Trip::where('id',$trip_id)->update(['payment_method' => PaymentMethod::where('country_id',$trip->country_id)->where('payment_type',3)->value('id') ]);
                Customer::where('id',$trip->customer_id)->update([ 'wallet' => 0 ]);
                
                $payment_history['trip_id'] = $trip_id;
                $payment_history['mode'] = "Wallet";
                $payment_history['amount'] = $customer_wallet;
                PaymentHistory::create($payment_history);
                $payment_history['trip_id'] = $trip_id;
                $payment_history['mode'] = "Cash";
                $payment_history['amount'] = abs($remaining_fare);
                PaymentHistory::create($payment_history);
                
                CustomerWalletHistory::create(['country_id' => $trip->country_id, 'customer_id' => $trip->customer_id, 'type' => 2, 'message' => 'Amount debited for booking(#'.$trip->trip_id.')', 'amount' => $customer_wallet, 'transaction_type' => 3 ]);
                
                return abs($remaining_fare);
            }
            
        }else{
            $payment_history['trip_id'] = $trip_id;
            $payment_history['mode'] = "Cash";
            $payment_history['amount'] = $fare;
            PaymentHistory::create($payment_history);
                
            return abs($fare);
        }
    }
    
    function distance($lat1, $lon1, $lat2, $lon2, $unit) {

      $theta = $lon1 - $lon2;
      $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
      $dist = acos($dist);
      $dist = rad2deg($dist);
      $miles = $dist * 60 * 1.1515;
      $unit = strtoupper($unit);
    
      if ($unit == "K") {
          return ($miles * 1.609344);
      } else if ($unit == "N") {
          return ($miles * 0.8684);
      } else {
          return $miles;
      }
    }
    
    public function calculate_earnings($trip_id){
        $trip = Trip::where('id',$trip_id)->first();
        $payment_method = PaymentMethod::where('id',$trip->payment_method)->first();
        $admin_commission_percent = TripSetting::value('admin_commission');
        $total = $trip->total;
        $total = number_format((float)$total, 2, '.', '');
        $admin_commission = ($admin_commission_percent / 100) * $total;
        $admin_commission = number_format((float)$admin_commission, 2, '.', '');
        $vendor_commission = $total - $admin_commission;
        $vendor_commission = number_format((float)$vendor_commission, 2, '.', '');
        
        DriverEarning::create([ 'trip_id' => $trip_id, 'driver_id' => $trip->driver_id, 'amount' => $vendor_commission ]);
        $old_wallet = Driver::where('id',$trip->driver_id)->value('wallet');
        
        if ($payment_method->payment_type == 2) {
           DriverWalletHistory::create([ 'driver_id' => $trip->driver_id, 'type' => 1, 'message' => 'credited to your account for the order '.$trip->trip_id, 'amount' => $vendor_commission ]);
            $new_wallet = $old_wallet + $vendor_commission;
        }else if ($payment_method->payment_type == 1) {
            DriverWalletHistory::create([ 'driver_id' => $trip->driver_id, 'type' => 2, 'message' => 'debited from your account for the order '.$trip->trip_id, 'amount' => $admin_commission ]);
           $new_wallet = $old_wallet - $admin_commission;
        }else if ($payment_method->payment_type == 3) {
            $wallet_payment = PaymentHistory::where('trip_id',$trip->id)->where('mode','Wallet')->value('amount');
            
            DriverWalletHistory::create([ 'driver_id' => $trip->driver_id, 'type' => 1, 'message' => 'credited to your account for the order '.$trip->trip_id, 'amount' => $wallet_payment ]);
            $secondry_wallet = $old_wallet + $wallet_payment;
            $secondry_wallet = number_format((float)$secondry_wallet, 2, '.', '');
            Driver::where('id',$trip->driver_id)->update([ 'wallet' => $secondry_wallet ]);
            
            DriverWalletHistory::create([ 'driver_id' => $trip->driver_id, 'type' => 2, 'message' => 'debited from your account for the order '.$trip->trip_id, 'amount' => $admin_commission ]);
           $new_wallet = $secondry_wallet - $admin_commission;
        }
        $new_wallet = number_format((float)$new_wallet, 2, '.', '');
        Driver::where('id',$trip->driver_id)->update([ 'wallet' => $new_wallet ]);
    }
    
    public function direct_booking(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'phone_number' => 'required',
            'customer_name' => 'required',
            'pickup_address' => 'required',
            'pickup_lat' => 'required',
            'pickup_lng' => 'required',
            'drop_address' => 'required',
            'drop_lat' => 'required',
            'drop_lng' => 'required',
            'driver_id' => 'required',
            'km' => 'required'
        ]);
        
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $driver = Driver::where('id',$input['driver_id'])->first();
        $vehicle = DriverVehicle::where('driver_id',$input['driver_id'])->first();
        $customer = Customer::where('phone_number',$input['phone_number'])->first();
        if(!is_object($customer)){
            
            $country = Country::where('id',$driver->country_id)->first();
            $currency = Currency::where('country_id',$country->id)->first();
            
            $customer['first_name'] = $input['customer_name'];
            $customer['country_id'] = $country->id;
            $customer['country_code'] = $country->phone_code;
            $customer['currency'] = $currency->currency;
            $customer['currency_short_code'] = $currency->currency_short_code;
            $customer['phone_number'] = $input['phone_number'];
            $customer['phone_with_code'] = $country->phone_code.$input['phone_number'];
            $customer['status'] = 1;
            Customer::create($customer);
            $customer = Customer::where('phone_number',$input['phone_number'])->first();
        }
        
        $data['km'] = $input['km'];
        $data['vehicle_type'] = $vehicle->vehicle_type;
        $data['customer_id'] = $customer->id;
        $data['booking_type'] = 2;
        $data['promo'] = 0;
        $data['country_id'] = $customer->country_id;
        $data['payment_method'] = PaymentMethod::where('country_id',$customer->country_id)->where('payment_type',1)->value('id');
        $data['pickup_address'] = $input['pickup_address'];
        $data['pickup_lat'] = $input['pickup_lat'];
        $data['pickup_lng'] = $input['pickup_lng'];
        $data['drop_address'] = $input['drop_address'];
        $data['drop_lat'] = $input['drop_lat'];
        $data['drop_lng'] = $input['drop_lng'];
        
         $url = 'https://maps.googleapis.com/maps/api/staticmap?center='.$input['pickup_lat'].','.$input['pickup_lng'].'&zoom=16&size=600x300&maptype=roadmap&markers=color:red%7Clabel:L%7C'.$input['pickup_lat'].','.$input['pickup_lng'].'&key='.env('MAP_KEY');
            $img = 'trip_request_static_map/'.md5(time()).'.png';
        file_put_contents('uploads/'.$img, file_get_contents($url));
        
        $fares = $this->calculate_daily_fare($data['vehicle_type'],$data['km'],$data['promo'],$data['country_id']);
        
        $booking_request = $data;
        $booking_request['distance'] = $data['km'];
        unset($booking_request['km']);
        $booking_request['total'] = $fares['total_fare'];
        $booking_request['sub_total'] = $fares['fare'];
        $booking_request['discount'] = $fares['discount'];
        $booking_request['tax'] = $fares['tax'];
        $booking_request['trip_type'] = 1;
        $booking_request['booking_type'] = 1;
        $booking_request['package_id'] = 0;
        $booking_request['static_map'] = $img;
        $booking_request['pickup_date'] = date("Y-m-d H:i:s");
        $id = TripRequest::create($booking_request)->id;
        //print_r($id);exit;
        
        //$factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        $newPost = $database
        ->getReference('customers/'.$data['customer_id'])
        ->update([
            'booking_id' => $id,
            'booking_status' => 1
        ]);
        
        $newPost = $database
        ->getReference('drivers/'.$input['driver_id'])
        ->update([
            'booking_status' => 1
        ]);
        
        $newPost = $database
        ->getReference('vehicles/'.$data['vehicle_type'].'/'.$input['driver_id'])
        ->update([
            'booking_id' => $id,
            'booking_status' => 1,
            'pickup_address' => $data['pickup_address'],
            'customer_name' => $customer->first_name,
            'drop_address' => $data['drop_address'],
            'total' => $booking_request['total'],
            'static_map' => $booking_request['static_map'],
            'trip_type'=>DB::table('trip_types')->where('id',$booking_request['trip_type'])->value('name')
        ]);
        
        //$this->auto_trip_accept($id,$input['driver_id']);
        return response()->json([
            "result" => $id,
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function auto_trip_accept($trip_id,$driver_id)
    {
        $input['trip_id'] = $trip_id;
        $input['driver_id'] = $driver_id;
        
        $trip = TripRequest::where('id',$input['trip_id'])->first()->toArray();
        $customer_id = TripRequest::where('id',$input['trip_id'])->value('customer_id');
        $phone_with_code = Customer::where('id',$customer_id)->value('phone_with_code');
        
        $data = $trip;
        $data['driver_id'] = $input['driver_id'];
        $data['promo_code'] = $data['promo'];
        unset($data['promo']);
        unset($data['id']);
        $data['pickup_date'] = $trip['pickup_date'];
        $data['country_id'] = $trip['country_id'];
        $data['package_id'] = $trip['package_id'];
        $data['trip_type'] = $trip['trip_type'];
        $data['status'] = 1;
        $data['vehicle_type'] = $trip['vehicle_type'];
        $data['vehicle_id'] = DB::table('driver_vehicles')->where('driver_id',$input['driver_id'])->value('id');
        $data['otp'] = $otp = rand(1000,9999);
        $message = "Hi ".env('APP_NAME')." , Your OTP code is:  ".$data['otp'];
            $this->sendSms($phone_with_code,$message);
        $id = Trip::create($data)->id;
        
        if($data['promo_code']){
            $user_promo['user_id'] = $data['customer_id'];
            $user_promo['promo_id'] = $data['promo_code'];
            $user_promo['status'] = 1;
            UserPromoHistory::create($user_promo);
        }
        
        $trip_id = str_pad($id,6,"0",STR_PAD_LEFT);
        Trip::where('id',$id)->update([ 'trip_id' => $trip_id ]);
        
        
        //Firebase
        //$factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        
        
        $trip_details = Trip::where('id',$id)->first();
        $customer = Customer::where('id',$trip_details->customer_id)->first();
        $driver = Driver::where('id',$trip_details->driver_id)->first();
        $vehicle = DriverVehicle::where('id',$trip_details->vehicle_id)->first();
        $current_status = BookingStatus::where('id',1)->first();
        $new_status = BookingStatus::where('id',2)->first();
        $payment_method = PaymentMethod::where('id',$trip_details->payment_method)->value('Payment');
        
        $data['driver_id'] = $input['driver_id'];
        $data['trip_request_id'] = $input['trip_id'];
        $data['status'] = 3;
        
        DriverTripRequest::create($data);
        
        //Trip Firebase
        $data = [];
        $data['id'] = $id;
        $data['trip_id'] = $trip_id;
        $data['trip_type'] = $trip_details->trip_type;
        $data['customer_id'] = $trip_details->customer_id;
        $data['customer_name'] = $customer->first_name;
        $data['customer_profile_picture'] = $customer->profile_picture;
        $data['customer_phone_number'] = $customer->phone_number;
        $data['driver_id'] = $trip_details->driver_id;
        $data['driver_name'] = $driver->first_name;
        $data['driver_profile_picture'] = $driver->profile_picture;
        $data['driver_phone_number'] = $driver->phone_number;
        $data['pickup_address'] = $trip_details->pickup_address;
        $data['pickup_lat'] = $trip_details->pickup_lat;
        $data['pickup_lng'] = $trip_details->pickup_lng;
        $data['drop_address'] = $trip_details->drop_address;
        $data['drop_lat'] = $trip_details->drop_lat;
        $data['drop_lng'] = $trip_details->drop_lng;
        $data['payment_method'] = $payment_method;
        $data['pickup_date'] = $trip_details->pickup_date;
        $data['total'] = $trip_details->total;
        $data['collection_amount'] = $trip_details->total;
        $data['vehicle_id'] = $trip_details->vehicle_id;
        $data['otp'] = $trip_details->otp;
        $data['vehicle_image'] = $vehicle->vehicle_image;
        $data['vehicle_number'] = $vehicle->vehicle_number;
        $data['vehicle_color'] = $vehicle->color;
        $data['vehicle_name'] = $vehicle->vehicle_name;
        $data['customer_status_name'] = $current_status->customer_status_name;
        $data['driver_status_name'] = $current_status->status_name;
        $data['status'] = 1;
        $data['new_driver_status_name'] = $new_status->status_name;
        $data['new_status'] = 2;
        $data['driver_lat'] = 0;
        $data['driver_lng'] = 0;
        $data['bearing'] = 0;
        
        $newPost = $database
        ->getReference('trips/'.$trip_details->id)
        ->update($data);
        
        $newPost = $database
        ->getReference('customers/'.$trip['customer_id'])
        ->update([
            'booking_id' => $id,
            'booking_status' => 2
        ]);
        
        $newPost = $database
        ->getReference('drivers/'.$input['driver_id'])
        ->update([
            'booking_status' => 2
        ]);
        
        $newPost = $database
        ->getReference('vehicles/'.$trip['vehicle_type'].'/'.$input['driver_id'])
        ->update([
            'booking_id' => $id,
            'booking_status' => 2
        ]);
        
        TripRequest::where('id',$input['trip_id'])->update([ 'status' => 3 ]);
        
        $this->send_fcm($current_status->status_name,$current_status->customer_status_name,$customer->fcm_token);
        
        return response()->json([
            "result" => $id,
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function create_reward($trip_id){
        $trip = Trip::where('id',$trip_id)->first();
        $scratch_settings = ScratchCardSetting::first();
        if($scratch_settings->coupon_type == 2){
            $rand = rand(10,100);
            if ($rand % 2 == 0) {
                $data['customer_id'] = $trip->customer_id;
                $data['title'] = "Better Luck Next Time !";
                $data['description'] = "Take Advantage of this Amazing offer before. Now it's too late. Better Luck next time";
                $data['view_status'] = 0;
                $data['image'] = "rewards/better_luck.png";
                $data['type'] = 0;
                $data['ref_id'] = 0;
                $data['status'] = 1;
                CustomerOffer::create($data);
                return true;
            }
        }
        $lucky_offer_limit = $scratch_settings->lucky_offer;
        $last_lucky_offer = CustomerOffer::where('type',2)->orderBy('id', 'DESC')->value('id');
        $last_offer = CustomerOffer::orderBy('id', 'DESC')->value('id');
        $lucky_find = $last_offer - $last_lucky_offer;
        
        if($lucky_find >= $lucky_offer_limit){
            $lucky_status = 1;
        }else{
            $lucky_status = 0;
        }
        
        if($lucky_status == 1){
            $lucky_count = LuckyOffer::count();
            $lucky_id = rand(1,$lucky_count);
            $lucky = LuckyOffer::where('id',$lucky_id)->first();
            $data['customer_id'] = $trip->customer_id;
            $data['title'] = $lucky->offer_name;
            $data['description'] = $lucky->offer_description;
            $data['image'] = $lucky->image;
            $data['ref_id'] = $lucky_id;
            $data['view_status'] = 0;
            $data['type'] = 2;
            $data['status'] = 1;
            CustomerOffer::create($data);
        }else{
            $instant_count = InstantOffer::count();
            $instant_id = rand(1,$instant_count);
            $instant = InstantOffer::where('id',$instant_id)->first();
            $data['customer_id'] = $trip->customer_id;
            $data['title'] = $instant->offer_name;
            $data['description'] = $instant->offer_description;
            $data['view_status'] = 0;
            $data['ref_id'] = $instant_id;
            $data['image'] = "rewards/reward_image.png";
            $data['type'] = 1;
            $data['status'] = 1;
            CustomerOffer::create($data);
        }
        return true;
    }
    
    public function spot_booking_otp(Request $request){

         $input = $request->all();
        $validator = Validator::make($input, [
            'phone_number' => 'required',
            'customer_name' => 'required',
            'pickup_address' => 'required',
            'pickup_lat' => 'required',
            'pickup_lng' => 'required',
            'drop_address' => 'required',
            'drop_lat' => 'required',
            'drop_lng' => 'required',
            'driver_id' => 'required',
            'km' => 'required',
            'fare' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $driver = Driver::where('id',$input['driver_id'])->first();
        $vehicle = DriverVehicle::where('driver_id',$input['driver_id'])->first();
        $country = Country::where('id',$driver->country_id)->first();
        $phone_with_code = $country->phone_code.$input['phone_number'];
        $currency = Currency::where('country_id',$country->id)->value('currency');
        //print_r($currency);exit;
        
        $data['km'] = $input['km'];
        $data['vehicle_type'] = $vehicle->vehicle_type;
        $data['booking_type'] = 2;
        $data['promo'] = 0;
        $data['country_id'] = $driver->country_id;
        
        //$data['fare'] = [];
           $fares = $this->calculate_daily_fare($data['vehicle_type'],$data['km'],$data['promo'],$data['country_id']);
            $data['otp'] = rand(1000,9999);
            $message = "Hi ".env('APP_NAME')." , Your OTP code is:  ".$data['otp'].". Pickup location:  ".$input['pickup_address'].", Drop location:" .$input['drop_address'].". Total fare:" .$currency.$input['fare'];
            $this->sendSms($phone_with_code,$message);
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
       

    }
    public function send_invoice(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'country_code' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
            $booking_details = Trip::where('id',$input['id'])->first();
            $customer = Customer::where('id',$booking_details->customer_id)->first();
            $email = $customer->email;
            $app_setting = AppSetting::first();
            $data = array();
            $data['logo'] = $app_setting->logo;
            $data['booking_id'] = $booking_details->trip_id;
            $data['customer_name'] = Customer::where('id',$booking_details->customer_id)->value('first_name');
            $data['pickup_address'] = $booking_details->pickup_address;
            $data['drop_address'] = $booking_details->drop_address;
            $data['start_time'] = $booking_details->start_time;
            $data['end_time'] = $booking_details->end_time;
            
            
            $data['driver'] = (Driver::where('id',$booking_details->driver_id)->value('first_name') != '' ) ? Driver::where('id',$booking_details->driver_id)->value('first_name') : "---" ;
            $country = Country::where('phone_code',$input['country_code'])->value('id');
            $data['country_id'] = $country;
            $data['currency'] = Currency::where('country_id',$data['country_id'])->value('currency');
           
            $data['payment_method'] = PaymentMethod::where('id',$booking_details->payment_method)->value('payment');
            $data['sub_total'] = $booking_details->sub_total;
            $data['discount'] =  $booking_details->discount;
            $data['total'] =  $booking_details->total;
            $data['tax'] =  $booking_details->tax;
            $data['status'] =  Status::where('id',$booking_details->status)->value('name');
            
            $mail_header = array("data" => $data);
            $this->send_order_mail($mail_header,'Enjoy the ride',$email);
            return response()->json([
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
    
    public function get_distance($trip_id){
        $trip = Trip::where('id',$trip_id)->first();
        $url= 'https://maps.googleapis.com/maps/api/directions/json?origin='.$trip->actual_pickup_lat.','.$trip->actual_pickup_lng.'&destination='.$trip->actual_drop_lat.','.$trip->actual_drop_lng.'&key='.env('MAP_KEY');
        
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_POST, 0);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
         $response = curl_exec ($ch);
         $err = curl_error($ch);  //if you need
         curl_close ($ch);
         $result = json_decode($response);
         if(@$result->routes[0]->legs[0]->distance->text){
             
             $distance = str_replace(" km","",$result->routes[0]->legs[0]->distance->text);
             $distance = str_replace(" m","",$distance);
             return $distance;
         }else{
             return 0;
         }
    }
}
