<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ContactSetting;
use App\AppSetting;
use Validator;

class AboutController extends Controller
{
    public function get_about()
    {
        
        $about_us = AppSetting::first();
        $data['about_us'] = $about_us['about_us'];
        $contact_details = ContactSetting::first();
        $data['phone_number'] = $contact_details['phone_number'];
        $data['email'] = $contact_details['email'];
        $data['address'] = $contact_details['address'];
        
        return response()->json([
            "result" => $data,
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
