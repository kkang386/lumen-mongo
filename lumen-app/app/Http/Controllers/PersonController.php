<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Log;

class PersonController extends Controller
{
    
    public function show(): JsonResponse {
        $people = Person::all();
        // $people = [["name" => "Jack"]];
        return response()->json($people);
    }

    public function store(Request $request): JsonResponse {
        
        Log::error("post request: ", [$request]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|name_string',
            'birth_date' => 'required|datetime_string',
            'timezone' => 'required|timezone_string'
        ],
        [
            'name.name_string' => "Name may only contain letters",
            'birth_date.datetime_string' => "Invalid birth date format. Please use this format: 'YYYY-MM-DD HH:MM:SS'",
            'timezone.timezone_string' => "Invalid timezone."
        ]
        );

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        //$UTC = new \DateTimeZone("UTC");
        $newTZ = new \DateTimeZone($request->timezone);
        $date = new \DateTime($request->birth_date, $newTZ );
        //$date->setTimezone( $newTZ );
        
        $person = new Person();
        $person->name = $request->name;
        $person->birth_date = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
        $person->timezone = $request->timezone;

        $person->save();
        return response()->json(["result" => "ok"], 201);
    }
}
