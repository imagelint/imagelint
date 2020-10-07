<?php


namespace App\Repositories;


use App\Models\Domain;

class DomainRepository
{
    public function domainExists($userId, $domain) {
        return Domain::where('user_id', $userId)->where('domain', $domain)->exists();
    }
}
