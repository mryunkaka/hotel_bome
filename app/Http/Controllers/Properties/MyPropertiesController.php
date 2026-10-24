<?php

namespace App\Http\Controllers\Properties;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MyPropertiesController
{
    public function index(Request $request): View
    {
        $properties = DB::table('property_user')
            ->join('properties', 'properties.id', '=', 'property_user.property_id')
            ->where('property_user.user_id', $request->user()->id)
            ->where('property_user.role', 'housekeeping')
            ->where('properties.is_active', true)
            ->orderBy('properties.name')
            ->get(['properties.id', 'properties.name', 'properties.code']);

        return view('properties.housekeeping.properties', ['properties' => $properties]);
    }
}
