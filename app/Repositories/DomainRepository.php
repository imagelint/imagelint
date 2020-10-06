<?php


namespace App\Repositories;


use App\Models\Domain;

class DomainRepository
{
    public static function isDomainExists($userId, $domain) {
        return Domain::where('user_id', $userId)->where('domain', $domain)->exists();
    }
}
