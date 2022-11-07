<?php

namespace App\Http\Controllers;

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
        
        Log::info("post request: ", [$request]);
        $errors = [];
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
