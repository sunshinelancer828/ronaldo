<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ReferralSetting;
use App\Customer;
use Validator;

class ReferralController extends Controller
{
    public function get_referral_message(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'customer_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = ReferralSetting::first();
        $code = customer::where('id',$input['customer_id'])->value('referral_code');
        return response()->json([
            "result" => $data,
            "code" => $code,
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
