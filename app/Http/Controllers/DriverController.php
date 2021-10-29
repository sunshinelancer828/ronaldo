<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Validator;
use App\Driver;
use App\Models\DriverQuery;
use App\DriverEarning;
use App\Customer;
use App\DriverBankKycDetail;
use App\Country;
use App\Currency;
use App\Trip;
use App\DriverTutorial;
use App\DriverVehicle;
use App\DriverWithdrawal;
use App\DriverWalletHistory;
use App\CustomerWalletHistory;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;



class DriverController extends Controller
{
    /*public function check_phone(Request $request){

         $input = $request->all();
        $validator = Validator::make($input, [
        	'phone_with_code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = array();
        $driver = Driver::where('phone_with_code',$input['phone_with_code'])->first();

        if(is_object($driver)){
            $data['is_available'] = 1;
            $data['otp'] = "";
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            $data['is_available'] = 0;
            $data['otp'] = rand(1000,9999);
            $message = "Hi".env('APP_NAME')." , Your OTP code is:".$data['otp'];
            $this->sendSms($input['phone_with_code'],$message);
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
        }

    }*/
    
    public function check_phone(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
        	'phone_with_code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = array();
        $driver = Driver::where('phone_with_code',$input['phone_with_code'])->first();

        if(is_object($driver)){
            return response()->json([
                //"result" => 'Phone number already exist',
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            
            /*$data['otp'] = rand(1000,9999);
            $message = "Hi".env('APP_NAME')." , Your OTP code is:".$data['otp'];
            $this->sendSms($input['phone_with_code'],$message);*/
            return response()->json([
                "message" => 'Sorry this number not available please contact admin',
                "status" => 0
            ]);
        }

    }
     public function register(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'first_name' => 'required',
            'last_name' => 'required',
            'country_code' => 'required',
            'phone_with_code' => 'required',
            'phone_number' => 'required|numeric|digits_between:9,20|unique:customers,phone_number',
            'email' => 'required|email|regex:/^[a-zA-Z]{1}/|unique:customers,email',
            'password' => 'required',
            'gender' => 'required',
            'date_of_birth' => 'required',
            'licence_number' => 'required',
            'fcm_token' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $options = [
            'cost' => 12,
        ];
        $input['password'] = password_hash($input["password"], PASSWORD_DEFAULT, $options);
        $input['status'] = 1;
        $data = Country::where('phone_code',$input['country_code'])->value('id');
        $input['country_id'] = $data;
        $input['currency'] = Currency::where('country_id',$input['country_id'])->value('currency');
        $driver = Driver::create($input);
        
        //$factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        //$database = $firebase->getDatabase();
        

        if (is_object($driver)) {
            
            $newPost = $database
            ->getReference('drivers/'.$driver->id)
            ->update([
            'driver_name' => $input['first_name'],
            'status' => $input['status'],
            'lat' => 0,
            'lng' => 0,
            'online_status' => 0,
            'booking_status' => 0
        ]);
        
            return response()->json([
                "result" => $driver,
                "message" => 'Registered Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }

    }
    
    public function login(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'phone_with_code' => 'required',
            'password' => 'required',
            'fcm_token' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $credentials = request(['phone_with_code', 'password']);
        $driver = Driver::where('phone_with_code',$credentials['phone_with_code'])->first();
        //$driver_phone = DriverVehicle::where('phone_with_code',$credentials['phone_with_code'])->value();

        if (!($driver)) {
            return response()->json([
                "message" => 'Invalid phone number or password',
                "status" => 0
            ]);
        }
        
        if (Hash::check($credentials['password'], $driver->password)) {
            
            if($driver->status == 1  ){
                $vehicle = DriverVehicle::where('driver_id', $driver->id)->first();
                if(is_object($vehicle)){
                   Driver::where('id', $driver->id)->update([ 'fcm_token' => $input['fcm_token']]);
                
                    return response()->json([
                        "result" => $driver,
                        "message" => 'Success',
                        "status" => 1
                    ]);    
                }else{
                    return response()->json([
                        "message" => 'Your vehicle details not updated',
                        "status" => 0
                    ]);
                }
                
            }else{
                return response()->json([
                    "message" => 'Your account has been blocked',
                    "status" => 0
                ]);
            }
        }else{
            return response()->json([
                "message" => 'Invalid phone number or password',
                "status" => 0
            ]);
        }

    }
    public function profile_picture(Request $request){

            $input = $request->all();
            $validator = Validator::make($input, [
                'driver_id' => 'required',
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg'
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors());
            }

            if ($request->hasFile('profile_picture')) {
                $image = $request->file('profile_picture');
                $name = time().'.'.$image->getClientOriginalExtension();
                $destinationPath = public_path('/uploads/drivers');
                $image->move($destinationPath, $name);
                if(Driver::where('id',$input['driver_id'])->update([ 'profile_picture' => 'drivers/'.$name ])){
                    return response()->json([
                        "result" => Driver::select('id', 'first_name', 'last_name', 'phone_with_code','email','profile_picture','password','status')->where('id',$input['driver_id'])->first(),
                        "message" => 'Success',
                        "status" => 1
                    ]);
                }else{
                    return response()->json([
                        "message" => 'Sorry something went wrong...',
                        "status" => 0
                    ]);
                }
            }

        }
    
      public function profile(Request $request)
        {
            $input = $request->all();
            $validator = Validator::make($input, [
                'driver_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors());
            }

            $result = Driver::where('id',$input['driver_id'])->first();

            if (is_object($result)) {
                return response()->json([
                    "result" => $result,
                    "message" => 'Success',
                    "status" => 1
                ]);
            } else {
                return response()->json([
                    "message" => 'Sorry, something went wrong...',
                    "status" => 0
                ]);
            }
        }
        
    public function profile_update(Request $request)
        {
            $input = $request->all();
            $validator = Validator::make($input, [
                'driver_id' => 'required'
                
            ]);
            $input['id'] = $input['driver_id'];
            unset($input['driver_id']);
            if ($validator->fails()) {
                return $this->sendError($validator->errors());
            }
            if($request->password){
                $options = [
                    'cost' => 12,
                ];
                $input['password'] = password_hash($input["password"], PASSWORD_DEFAULT, $options);
                $input['status'] = 1;
            }else{
                unset($input['password']);
            }

            if (Driver::where('id',$input['id'])->update($input)) {
                return response()->json([
                    "result" => Driver::select('id', 'first_name', 'last_name', 'phone_with_code','email','profile_picture','password','daily','rental','outstation','status')->where('id',$input['id'])->first(),
                    "message" => 'Success',
                    "status" => 1
                ]);
            } else {
                return response()->json([
                    "message" => 'Sorry, something went wrong...',
                    "status" => 0
                ]);
            }

        }
    
     public function driver_earning(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        /*$data['total_earnings'] = DriverEarning::where('driver_id',$input['id'])->get()->sum("amount");
        $data['today_earnings'] = DriverEarning::where('driver_id',$input['id'])->whereDay('created_at', now()->day)->sum("amount");
        $data['earnings'] = DriverEarning::where('driver_id',$input['id'])->get();*/
        
        $total_earnings = DriverEarning::where('driver_id',$input['id'])->get()->sum("amount");
        $data['total_earnings'] = number_format((float)$total_earnings, 2, '.', '');
        $today_earnings = DriverEarning::where('driver_id',$input['id'])->whereDay('created_at', now()->day)->sum("amount");
        $data['today_earnings'] = number_format((float)$today_earnings, 2, '.', '');
        $data['earnings'] = DriverEarning::where('driver_id',$input['id'])->get();
        
        
        if($data){
            return response()->json([
                "result" => $data,
                "count" => count($data),
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Something went wrong',
                "status" => 0
            ]);
        }

    }
    public function driver_wallet(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['wallet_amount'] = Driver::where('id',$input['id'])->value('wallet');
        
        $data['wallets'] = DriverWalletHistory::where('driver_id',$input['id'])->get();
        
        if($data){
            return response()->json([
                "result" => $data,
                "count" => count($data),
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Something went wrong',
                "status" => 0
            ]);
        }

    }
    public function driver_withdrawal_request(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'driver_id' => 'required',
            'amount' => 'required'
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $input['status'] = 11;
        $vendor = DriverWithdrawal::create($input);
        Driver::where('id',$input['driver_id'])->update([ 'wallet' => 0]);
        return response()->json([
            "message" => 'success',
            "status" => 1
        ]);
    }
    
    public function driver_withdrawal_history(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['wallet_amount'] = Driver::where('id',$input['id'])->value('wallet');
        
        $data['withdraw'] =  DB::table('driver_withdrawals')
                ->leftjoin('statuses', 'statuses.id', '=', 'driver_withdrawals.status')
                ->select('driver_withdrawals.*', 'statuses.name')
                ->get();
        
        if($data){
            return response()->json([
                "result" => $data,
                "count" => count($data),
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Something went wrong',
                "status" => 0
            ]);
        }
    }
    
    public function get_bank_kyc_details(Request $request){
        $input = $request->all();
        $kyc_details = DriverBankKycDetail::where('driver_id', $input['driver_id'])->first();
        if(is_object($kyc_details)){
            return response()->json([
                "result" => $kyc_details,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Still not updated',
                "status" => 0
            ]);
        }
        
    }
    
    public function bank_kyc_update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'driver_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $is_details_exist = DriverBankKycDetail::where('driver_id', $input['driver_id'])->first();
        if(is_object($is_details_exist)){
            $update = DriverBankKycDetail::where('driver_id', $input['driver_id'])->update($input);  
        }else{
             $update =  DriverBankKycDetail::create($input);   
        }
        if ($update) {
            return response()->json([
                "result" => DriverBankKycDetail::select('id','driver_id', 'bank_name', 'bank_account_number','ifsc_code','aadhar_number','pan_number')->where('driver_id', $input['driver_id'])->first(),
                "message" => 'Success',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong...',
                "status" => 0
            ]);
        }

    }
    
    public function change_online_status(Request $request){
        $input = $request->all();
        Driver::where('id',$input['id'])->update([ 'online_status' => $input['online_status']]);
        
        $vehicle = DriverVehicle::where('driver_id',$input['id'])->first();
        
        //$factory = (new Factory)->withServiceAccount(config_path().'/'.env('FIREBASE_FILE'));
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        //$database = $firebase->getDatabase();
        $newPost = $database
        ->getReference('vehicles/'.$vehicle->vehicle_type.'/'.$input['id'])
        ->update([
            'online_status' => (int) $input['online_status']
        ]);
        
         $newPost = $database
        ->getReference('drivers/'.$input['id'])
        ->update([
            'online_status' => (int) $input['online_status']
        ]);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    public function get_tutorials(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'country_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = DriverTutorial::where('country_id',$input['country_id'])->orderBy('id', 'DESC')->get();
        
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }
    public function forgot_password(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
        	'phone_with_code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $driver = Driver::where('phone_with_code',$input['phone_with_code'])->first();

        if(is_object($driver)){
            $otp = rand(1000,9999);
            $message = "Hi".env('APP_NAME')." , Your OTP code is:".$otp;
            $this->sendSms($input['phone_with_code'],$message);
            return response()->json([
                "result" => $otp,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Please enter valid phone number',
                "status" => 0
            ]);
        }

        
    }

    public function reset_password(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $options = [
            'cost' => 12,
        ];
        $input['password'] = password_hash($input["password"], PASSWORD_DEFAULT, $options);

        if(Driver::where('id',$input['id'])->update($input)){
            return response()->json([
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Sorry something went wrong',
                "status" => 0
            ]);
        }
    }
    
    public function get_vehicles(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'driver_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = DriverVehicle::where('driver_id',$input['driver_id'])->first();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function driver_dashboard(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $today_earnings = DriverEarning::where('driver_id',$input['id'])->whereDay('created_at', now()->day)->sum("amount");
        $result['today_earnings'] = number_format((float)$today_earnings, 2, '.', '');
        $result['today_bookings'] = Trip::where('driver_id',$input['id'])->where('status','!=', '6')->where('status','!=', '7')->whereDay('created_at', now()->day)->count();
        $result['today_completed_bookings'] = Trip::where('driver_id',$input['id'])->where('status',5)->whereDay('updated_at', now()->day)->count();
        $result['online_status'] = Driver::where('id',$input['id'])->value('online_status');
        $result['vehicle_type'] = DriverVehicle::where('driver_id',$input['id'])->value('vehicle_type');

        if($result){
            return response()->json([
                "result" => $result,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Something went wrong',
                "status" => 0
            ]);
        }

    }
    
    public function driver_ratings(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'trip_id' => 'required',
            'ratings' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
            $rating_update = Trip::where('id',$input['trip_id'])->update([ 'ratings' => $input['ratings']]);
            $trip = Trip::where('id',$input['trip_id'])->first();
            //print_r($trip);exit;
            if(is_object($trip)){
                 $this->driver_rating($trip->driver_id);
                 }else{
            return response()->json([
                "message" => 'Something went wrong',
                "status" => 0
            ]);
        }

    }
    
    public function driver_rating($driver_id)
    {
        $ratings_data = Trip::where('driver_id',$driver_id)->where('ratings','!=', '0')->get();
        //print_r($ratings_data);exit;
        $data_sum = Trip::where('driver_id',$driver_id)->get()->sum("ratings");
        $data = $data_sum / count($ratings_data);
        if($data){
            Driver::where('id',$driver_id)->update(['overall_ratings'=>number_format((float)$data, 1, '.', ''), 'no_of_ratings'=> count($ratings_data)]);
        }
        $ratings = Driver::where('id',$driver_id)->first();
        return response()->json([
                "result" => $ratings,
                "message" => 'Success',
                "status" => 1
            ]);
    }
    
    public function ride_completeion_mail(Request $request){
        $input = $request->all();
        $ride_id = $id;
         $data = DB::table('trips')
                ->leftJoin('customers','customers.id','trips.customer_id')
                ->leftJoin('drivers','drivers.id','trips.driver_id')
                ->leftJoin('payment_methods','payment_methods.id','trips.payment_method')
                ->leftJoin('driver_vehicles','driver_vehicles.id','trips.vehicle_id')
                ->leftJoin('vehicle_categories','vehicle_categories.id','driver_vehicles.vehicle_type')
                ->leftJoin('booking_statuses','booking_statuses.id','trips.status')
                ->select('trips.*','customers.first_name as customer_name','customers.email as email','drivers.first_name as driver_name','drivers.profile_picture','payment_methods.payment as payment_method','driver_vehicles.brand as vehicle_brand','driver_vehicles.color','driver_vehicles.vehicle_name as vehicle_name','driver_vehicles.vehicle_number as vehicle_number','booking_statuses.status_name','vehicle_categories.vehicle_type')
                ->where('trips.id',$ride_id)
                ->first();
        $mail_header = array("data" => $data);
        $this->ride_completeion($mail_header,'Ride Completed Successfully',$data->email);
    }
    public function vehicle_type_list(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'country_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = VehicleCategory::where('country_id',$input['country_id'])->get();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }

    public function vehicle_update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'country_id' => 'required',
            'driver_id' => 'required',
            'vehicle_type' => 'required',
            'vehicle_image' => 'required',
            'brand' => 'required',
            'color' => 'required',
            'vehicle_name' => 'required',
            'vehicle_number' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $input['status'] = 1;
        $driver_vehicles = DriverVehicle::create($input);

        if (is_object($driver_vehicles)) {
            return response()->json([
                "result" => $driver_vehicles,
                "message" => 'Registered Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }

    }
    public function vehicle_image_upload(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/uploads/vehicle_images');
            $image->move($destinationPath, $name);
            return response()->json([
                "result" => 'vehicle_images/'.$name,
                "message" => 'Success',
                "status" => 1
            ]);
            
        }
    }
    
    public function register_query(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'first_name' => 'required',
            'last_name' => 'required',
            'phone_number' => 'required|numeric|digits_between:9,20',
            'email' => 'required|email|regex:/^[a-zA-Z]{1}/',
            'description' =>'required'
            
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $input['status'] = 1;
        $driver = DriverQuery::create($input);
        
        if($driver){
            return response()->json([
                "result" => $driver,
                "message" => 'Registered Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }

    }
    
    
    
    public function sendError($message) {
        $message = $message->all();
        $response['error'] = "validation_error";
        $response['message'] = implode('',$message);
        $response['status'] = "0";
        return response()->json($response, 200);
    } 

}
