<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\WeatherRepositoryInterface;

class WeatherController extends Controller
{
    //
    private $weatherRepository;

    public function __construct(WeatherRepositoryInterface $weatherRepository)
    {
        $this->weatherRepository = $weatherRepository;
    }

    public function getHistory(Request $request)
    {   
        $data = $this->weatherRepository->getHistory($request->all(),auth()->user());
        return response()->json(['data'=>$data, 'msg'=>'success'],200);
    }


}
