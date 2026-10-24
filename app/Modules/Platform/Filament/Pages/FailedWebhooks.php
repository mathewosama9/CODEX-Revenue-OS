<?php

namespace App\Modules\Platform\Filament\Pages;

use App\Shared\Webhooks\ProcessWebhookEvent;
use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Dead-letter view for webhooks (09 §9.9.4). Replay is safe: every handler is
 * idempotent. Payloads are never shown here (PII).
 */
class FailedWebhooks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Webhooks فاشلة';

    protected static ?int $navigationSort = 2;

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(WebhookEvent::query()->where('status', WebhookStatus::Failed)->latest('received_at'))
            ->columns([
                TextColumn::make('provider'),
                TextColumn::make('event_type')->label('النوع'),
                TextColumn::make('tenant_id')->label('المتجر'),
                TextColumn::make('attempts')->label('محاولات'),
                TextColumn::make('error')->label('الخطأ')->limit(80),
                TextColumn::make('received_at')->label('وصل')->since(),
            ])
            ->recordActions([
                Action::make('replay')
                    ->label('أعد المعالجة')
                    ->action(function (WebhookEvent $record) {
                        ProcessWebhookEvent::dispatch($record->id);
                        Notification::make()->title('اتبعت للمعالجة')->success()->send();
                    }),
            ]);
    }
}
