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
    private function nextBirthdateInSeconds(\DateTime $birthDate): int {

    }
    
    public function show(): JsonResponse {
        // $people = Person::all();
        $currentDate = new \DateTime();
        $people = Person::query()
                    ->get();

        $records = [];
        foreach ($people as $person) {
            $rec = [];
            $rec['name'] = $person->name;
            $rec['birthdate'] = $person->birthdate;
            $rec['timezone'] = $person->timezone;

            $birthdate = new \DateTime($person->birthdate, new \DateTimeZone($person->timezone));
            $currentDate->setTimezone(new \DateTimeZone($person->timezone));
            // check if today is the bday
            $rec['isBirthday'] = ($birthdate->format('m-d') == $currentDate->format('m-d'));
            $records[] = $rec;
        }
        return response()->json($records);
    }

    public function store(Request $request): JsonResponse {
        
        Log::error("post request: ", [$request]);
        $validator = Validator::make($request->all(),
            [
                'name' => 'required|max:255|name_string',
                'birthdate' => 'required|datetime_string',
                'timezone' => 'required|timezone_string'
            ],
            [
                'name.name_string' => "Name may only contain letters",
                'birthdate.datetime_string' => "Invalid birth date format. Please use this format: 'YYYY-MM-DD HH:MM:SS'",
                'timezone.timezone_string' => "Invalid timezone."
            ]
        );

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $person = new Person();
        $person->name = $request->name;
        // $date = new \DateTime($request->birthdate, new \DateTimeZone("UTC") );
        // $person->birthdate = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
        $person->birthdate = $request->birthdate;
        $person->timezone = $request->timezone;

        $person->save();
        return response()->json(["result" => "ok"], 201);
    }
}
