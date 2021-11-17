<?php

use App\Models\Airport;
use App\Models\Pirep;
use App\Models\User;
use App\Models\Enums\FareType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Geotools\Geotools;
use League\Geotools\Coordinate\Coordinate;
use Modules\DisposableSpecial\Models\DS_Tour;

// Generate automatic fare price based on GC distance
// Return decimal
if (!function_exists('DS_AutoPrice')) {
    function DS_AutoPrice($pirep, $fare, $base_price = 10, $per_nm = 0.11)
    {
        $gc_dist = DS_CalculateDistance($pirep->dpt_airport_id, $pirep->arr_airport_id);
        $distance = is_numeric($gc_dist) ? $gc_dist : $pirep->distance;

        if (!is_numeric($distance) || !is_numeric($base_price) || !is_numeric($per_nm)) {
            return $fare->price;
        }
        // Flag Carriers Check
        $flag_carriers = ['THY', 'TKC', 'SVA'];
        $flag_check = in_array($pirep->airline->icao, $flag_carriers);
        // Cargo Flights Types Check
        $cargo_types = ['A', 'H', 'F', 'M'];
        $cargo_check = in_array($pirep->flight_type, $cargo_types);
        // Flag Carrier Adjustments
        if ($flag_check) {
            $base_price = round($base_price * 3.5);
        }
        // Short Distance Domestic Adjustments
        if (!$cargo_check && !$flag_check && $distance < 150) {
            $base_price = $base_price * 4;
        }
        // Cargo Flight Adjustments
        if ($cargo_check) {
            $base_price = round($base_price / 500, 2);
            $per_nm = 0.0010;
        }
        // Wide Body Adjustments
        if ($fare->type === FareType::PASSENGER && $fare->count > 200) {
            $per_nm = 0.076;
        }
        // Cargo Fare Adjustments for Passenger Aircraft
        if (!$cargo_check && $fare->type === FareType::CARGO) {
            $base_price = round($base_price / 500, 2);
            $per_nm = 0.00046;
            if ($fare->count > 10000) {
                $per_nm = 0.00024;
            }
        }
        // VIP Fare Class Adjustments
        if ($fare->code === 'V' || $fare->code === 'CGV') {
            $base_price = $base_price * 5;
            $per_nm = $per_nm * 5;
        }

        $multiplier = is_numeric($fare->notes) ? $fare->notes : 1;
        Log::debug('Disposable Special, APC F=' . $pirep->airline->icao . $pirep->flight_number . ' B=' . $base_price . ' D=' . $distance . ' Pd=' . $per_nm . ' M=' . $multiplier);

        return round($base_price + ($distance * $per_nm) * $multiplier, 2);
    }
}

// Calculate Block Time for given nmi distance
// With defined speed (knots) and margin (minutes)
// Return integer (minutes)
if (!function_exists('DS_CalculateBlockTime')) {
    function DS_CalculateBlockTime($distance, $speed = 490, $margin = 40)
    {
        if (!is_numeric($distance) || !is_numeric($speed) || !is_numeric($margin)) {
            return 1;
        }

        $btime_calc = ceil(($distance / ($speed / 60)) + $margin);
        $round_btime = fmod($btime_calc, 5);
        $final_btime = $btime_calc + (5 - $round_btime);

        return $final_btime;
    }
}

// Calculate Great Circle distance between airports
// Return decimal (default unit nautical miles)
if (!function_exists('DS_CalculateDistance')) {
    function DS_CalculateDistance($orig_icao, $dest_icao, $unit = 'nmi')
    {
        $orig = DB::table('airports')->where('id', $orig_icao)->first();
        $dest = DB::table('airports')->where('id', $dest_icao)->first();

        if (!$orig || !$dest) {
            return null;
        }

        $geotools = new Geotools();
        $orig_loc = new Coordinate([$orig->lat, $orig->lon]);
        $dest_loc = new Coordinate([$dest->lat, $dest->lon]);
        $geo_dist = $geotools->distance()->setFrom($orig_loc)->setTo($dest_loc);

        $distance = $geo_dist->greatCircle(); // Meters

        if ($unit === 'nmi') {
            $distance = $distance / 1852;
        } elseif ($unit === 'mi') {
            $distance = $distance / 1609;
        } elseif ($unit === 'km') {
            $distance = $distance / 1000;
        }

        return round($distance, 2);
    }
}

// Check phpVMS Module
// Return boolean
if (!function_exists('DS_CheckModule')) {
    function DS_CheckModule($module_name)
    {
        $phpvms_module = Module::find($module_name);
        return isset($phpvms_module) ? $phpvms_module->isEnabled() : false;
    }
}

// Convert Minutes
// Return string
if (!function_exists('DS_ConvertMinutes')) {
    function DS_ConvertMinutes($minutes = 0, $format = '%02d:%02d')
    {
        $minutes = intval($minutes);

        if ($minutes < 1) {
            return null;
        }
        $hours = floor($minutes / 60);
        $minutes = ($minutes % 60);

        return sprintf($format, $hours, $minutes);
    }
}

// Convert Weight from LBS to KGS
// Return string
if (!function_exists('DS_ConvertWeight')) {
    function DS_ConvertWeight($value = 0, $target_unit = null)
    {
        if ($value == 0) {
            return null;
        }
        $target_unit = isset($target_unit) ? $target_unit : setting('units.weight');

        if ($target_unit === 'kg') {
            $value = $value / 2.20462262185;
        }
        $value = number_format($value) . ' ' . $target_unit;

        return $value;
    }
}

// Get Airports for listing
// Return collection
if (!function_exists('DS_GetAirports')) {
    function DS_GetAirports()
    {
        return Airport::select('id', 'name', 'country')->orderby('id')->get();
    }
}

// Get Tour name for matching flight route_code
// Return string
if (!function_exists('DS_GetTourName')) {
    function DS_GetTourName($route_code = null) 
    {
        $tour = DS_Tour::select('tour_name')->where('tour_code', $route_code)->first();
        return filled($tour) ? $tour->tour_name : $route_code; 
    }
}

// Check Disposable Module Setting
// Return mixed, either boolean or the value itself as string
// If setting is not found, return either false or provided default
if (!function_exists('DS_Setting')) {
    function DS_Setting($key, $default_value = null)
    {
        $setting = DB::table('disposable_settings')->select('key', 'value')->where('key', $key)->first();

        if (!$setting && !$default_value) {
            $result = false;
        } elseif (!$setting && $default_value) {
            $result = $default_value;
        } elseif (!$setting->value) {
            $result = $default_value;
        } elseif ($setting->value === 'false') {
            $result = false;
        } elseif ($setting->value === 'true') {
            $result = true;
        } else {
            $result = $setting->value;
        }

        return $result;
    }
}

// Get Required Units
// Return array
if (!function_exists('DS_GetUnits')) {
    function DS_GetUnits($type = null)
    {
        $units = [];
        $units['currency'] = setting('units.currency');
        $units['distance'] = setting('units.distance');
        $units['fuel'] = setting('units.fuel');
        $units['weight'] = setting('units.weight');

        if ($type === 'full') {
            $units['volume'] = settings('units.volume');
            $units['altitude'] = settings('units.altitude');
        }

        return $units;
    }
}

// Get Total User Count
// Return integer
if (!function_exists('DS_UserCount')) {
    function DS_UserCount()
    {
        return User::count();
    }
}

// Tour Specific Helpers

// Check if the user has an accepted pirep for a particular tour Leg
// Check all details for tours like code, leg, dates, aircraft
// Return boolean
if (!function_exists('DS_IsTourLegFlown')) {
    function DS_IsTourLegFlown($tour_id, $flight_id, $user_id)
    {
        $tour = DS_Tour::with('legs.subfleets')->where('id', $tour_id)->first();
        $flight = $tour->legs->where('id', $flight_id)->first();
        // Get User's Pirep either with Flight ID (acars or prefile via button) or pinpoint with more details (manual or free flight)
        $pirep = Pirep::with('aircraft')->where([
            'user_id'   => $user_id,
            'flight_id' => $flight->id,
            'state'     => 2,
            'status'    => 'ONB',
        ])->orWhere('user_id', $user_id)->where([
            'route_code'     => $flight->route_code,
            'route_leg'      => $flight->route_leg,
            'dpt_airport_id' => $flight->dpt_airport_id,
            'arr_airport_id' => $flight->arr_airport_id,
            'state'          => 2,
            'status'         => 'ONB',
        ])->orderby('submitted_at', 'desc')->first();

        // Get The Dates either for Flight or the Tour
        $start_date = $flight->start_date ?? $tour->start_date;
        $end_date = $flight->end_date ?? $tour->end_date;

        // Define Default Check Results
        $aircraft_check = false;
        $airline_check = false;
        $date_check = false;

        if ($pirep) {
            // Check Dates
            if ($pirep->submitted_at->between($start_date, $end_date)) {
                $date_check = true;
            }
            // Check Airline Match if Needed
            if ($tour->tour_airline != 0) {
                if ($tour->tour_airline == $pirep->airline_id) {
                    $airline_check = true;
                }
            } else {
                $airline_check = true;
            }
            // Aircraft Check
            if ($flight->subfleets->count() > 0) {
                if ($flight->subfleets->contains('id', $pirep->aircraft->subfleet_id)) {
                    $aircraft_check = true;
                }
            } else {
                $aircraft_check = true;
            }
        }

        return ($aircraft_check && $airline_check && $date_check) ? true : false;
    }
}
