<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([
                    SpatieMediaLibraryFileUpload::make('cover')->collection('cover')->label('Cover'),
                    SpatieMediaLibraryFileUpload::make('gallery')->collection('gallery')->label('Gallery')->multiple(),
                    TextInput::make('name')->label('Product Name')->required(),
                    TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true),
                    TextInput::make('slug')->label('Slug')->unique(ignoreRecord: true),
                    SpatieTagsInput::make('tags')->type('collection')->label('Collection Tags'),
                    MarkdownEditor::make('description')->label('Description')->nullable(),
                    // TextInput::make('description')->label('Description')->nullable(),
                    TextInput::make('stock')->numeric()->default(0)->label('Stock')->default(0),
                    TextInput::make('price')->numeric()->prefix('Rp ')->label('Price')->default(0),
                    TextInput::make('weight')->numeric()->suffix('gram')->label('Weight')->default(0),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Product Name'),
                TextColumn::make('sku')->label('SKU'),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('stock')->label('Stock'),
                TextColumn::make('price')->label('Price'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
