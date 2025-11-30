<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    public function getBreadcrumb(): string
    {
        return 'Créer un administrateur';
    }

    public function getTitle(): string
    {
        return 'Créer administrateur';
    }

    




}
