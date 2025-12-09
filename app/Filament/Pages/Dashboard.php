<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('name')->label('Nom'),
                DatePicker::make('startDate')->label('Date de début'),
                DatePicker::make('endDate')->label('Date de fin'),
            
            ])->columns(3),  
            
        ]);
    }


}