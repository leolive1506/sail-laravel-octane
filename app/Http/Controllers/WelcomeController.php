<?php

namespace App\Http\Controllers;

use Google\Cloud\Logging\LoggingClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WelcomeController extends Controller
{
    public function index()
    {
        try {
            $logging = new LoggingClient([
                'projectId' => 'log-octane-swoole',
                'keyFile' => json_decode(file_get_contents(base_path('google/log-octane-swoole.json')), true)
            ]);


            // Get a logger instance.
            $logger = $logging->logger('sail');

            $logger->write('sail octane log');
//            $response = Http::get("viacep.com.br/ws/01001000/json/");
//            dd($response);
        } catch (\Exception $e) {
            dd($e);
        }

        return view('welcome');
    }
}
