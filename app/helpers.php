<?php

if (!function_exists('getWeekendSurcharge')) {
  function getWeekendSurcharge()
  {
    return 50000;
  }
}

if (!function_exists('isWeekend')) {
  function isWeekend($date)
  {
    $dateObj = new \DateTime($date);
    $dayOfWeek = $dateObj->format('w'); // 0 (Sunday) and 6 (Saturday) are weekends
    return $dayOfWeek == 0 || $dayOfWeek == 6;
  }
}

if (!function_exists('calculatePrice')) {
  function calculatePrice($service, $date)
  {
    $basePrice = $service->price;
    $weekendSurcharge = isWeekend($date) ? getWeekendSurcharge() : 0;

    return $basePrice + $weekendSurcharge;
  }
}
