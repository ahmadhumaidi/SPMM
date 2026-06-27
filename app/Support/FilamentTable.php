<?php

namespace App\Support;

use Filament\Tables\Columns\TextColumn;

class FilamentTable
{
    public static function rowNumberColumn(): TextColumn
    {
        return TextColumn::make('row_number')
            ->label('No')
            ->rowIndex()
            ->alignCenter()
            ->toggleable(false);
    }
}
