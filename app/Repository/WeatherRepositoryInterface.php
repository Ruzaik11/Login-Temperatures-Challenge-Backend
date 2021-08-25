<?php
namespace App\Repository;

interface WeatherRepositoryInterface
{
    public function storeToday($user);

    public function getHistory($data, $user);

}
