<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enroll;
use Illuminate\Support\Facades\Storage;

class EnrollController extends Controller
{
    public function create()
    {
        return view('enroll');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'age'         => 'required|integer|min:1',
            'address'     => 'required|string|max:255',
            'photo'       => 'required|string', // base64 image
        ]);

        // decode base64
        $imageData = $request->photo;
        $image = str_replace('data:image/png;base64,', '', $imageData);
        $image = str_replace(' ', '+', $image);
        $imageName = 'face_' . time() . '.png';

        Storage::disk('public')->put('enrolls/' . $imageName, base64_decode($image));

        Enroll::create([
            'first_name'  => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'   => $request->last_name,
            'age'         => $request->age,
            'address'     => $request->address,
            'photo_path'  => 'enrolls/' . $imageName,
        ]);

        return redirect()->back()->with('success', 'Enrollment successful!');
    }

    public function verify()
{
    return view('verify');
}

// API to return all enrolled faces
public function enrolledFaces()
{
    $faces = \App\Models\Enroll::all(['id','first_name','middle_name','last_name','age','address','photo_path']);
    return response()->json($faces);
}
}
