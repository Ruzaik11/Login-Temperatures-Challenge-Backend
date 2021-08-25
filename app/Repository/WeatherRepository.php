<?php
namespace App\Repository;

use App\Models\Weather;
use App\Repository\WeatherRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Config;

class WeatherRepository implements WeatherRepositoryInterface
{

    private $weather;
    /**
     * WeatherRepository constructor.
     *
     * @param Weather $model
     */
    public function __construct(Weather $weather)
    {
        $this->weather = $weather;
    }

    public function getHistory($data, $user)
    {
        try {

            $weather = $this->weather->where('user_id', '=', $user->id);

            if (isset($data['hottest_first'])) {
                $weather->orderBy('celcius', 'desc')
                ->orderBy('farenheit', 'desc');
            } else {
                   $weather->orderBy('date', 'asc');
            }

            return $weather = $weather->get();

        } catch (Exception $ex) {
            return false;
        }
    }

    public function storeToday($user)
    {
        try {

            $data = $this->weatherClient();

            $weather_data = array();

            foreach ($data['list'] as $item) {

                array_push($weather_data, [
                    'city' => $item['name'],
                    'user_id' => $user->id,
                    'date' => now(),
                    'celcius' => $item['main']['temp'],
                    'farenheit' => round(($item['main']['temp'] * 9 / 5) + 32, 2),
                ]);

            }

            $this->weather->insert($weather_data);

            return true;

        } catch (Exception $ex) {
            return false;
        }
    }

    public function weatherClient()
    {
        try {

            $client = new Client(['defaults' => [
                'header' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'verify' => false,
            ]]);

            $weather = Config::get('weather');

            $ids = array_map(function ($el) {
                return $el['id'];
            }, $weather);

            $ids = implode(',', $ids);

            $url = 'http://api.openweathermap.org/data/2.5/group?id=' . $ids . '&appid=' . env('WEATHER_API_KEY') . '&units=imperial';

            $response = $client->request('GET', $url, ['headers' => [], RequestOptions::JSON => []]);

            return json_decode($response->getBody(), true);

        } catch (BadResponseException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }

    }

}
