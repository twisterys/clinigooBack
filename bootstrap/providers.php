<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Modules\Address\Providers\AddressServiceProvider;
use Modules\Beneficiary\Providers\BeneficiaryServiceProvider;
use Modules\CareRequest\Providers\CareRequestServiceProvider;
use Modules\Professions\Providers\ProfessionsServiceProvider;
use Modules\Service\Providers\ServiceServiceProvider;
use Modules\User\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ProfessionsServiceProvider::class,
    ServiceServiceProvider::class,
    UserServiceProvider::class,
    BeneficiaryServiceProvider::class,
    AddressServiceProvider::class,
    CareRequestServiceProvider::class,
];
