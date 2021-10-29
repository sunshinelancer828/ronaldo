<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\NotificationMessage;
use Validator;

class NotificationController extends Controller
{
    public function get_customer_notification_messages(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'country_id' => 'required',
            'customer_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = NotificationMessage::where('status',1)->where('type',1)->where('country_id',$input['country_id'])->orderBy('id', 'DESC')->get();
        
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }

    public function get_driver_notification_messages(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'country_id' => 'required',
            'driver_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = NotificationMessage::where('status',1)->where('type',2)->where('country_id',$input['country_id'])->orderBy('id', 'DESC')->get();
        
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
