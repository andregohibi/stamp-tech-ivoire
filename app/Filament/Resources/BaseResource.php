<?php

namespace App\Filament\Resources;

use Filament\Tables\Table;
use Filament\Tables\Actions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\BaseResource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

abstract class BaseResource extends Resource
{
    public static function table(Table $table): Table
    {
        // Applique les actions de base avec restrictions
        return parent::table($table)
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->visible(fn (Model $record) => Auth::user()->role === 'super_admin'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->role === 'super_admin'),
                ]),
            ]);
    }
}