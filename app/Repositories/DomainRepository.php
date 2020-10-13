<?php


namespace App\Repositories;


use App\Models\Domain;

class DomainRepository
{
    public function domainExists($accountId, $domain) {
        return Domain::where('account_id', $accountId)->where('domain', $domain)->exists();
    }
}
