<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Schedule;
use Carbon\Carbon;

class ServiceController extends Controller
{
  public function getById($id)
  {
    $services = Service::all();
    $service = Service::findOrFail($id);

    // Calculate base price
    $basePrice = $service->price;

    // Get all booked schedules for this service and transform into a simple status map
    $bookedDates = Schedule::where('service_id', $id)
      ->where('status', 'booked')
      ->get()
      ->pluck('date')
      ->map(function ($date) {
        return Carbon::parse($date)->format('Y-m-d');
      })
      ->toArray();

    // Create a status array with just the booked dates
    $status = [];
    foreach ($bookedDates as $date) {
      $status[$date] = 'booked';
    }

    // Pass data to the view - we now just send the booked dates
    return view('bookings.index', compact('services', 'service', 'status', 'basePrice'));
  }
}