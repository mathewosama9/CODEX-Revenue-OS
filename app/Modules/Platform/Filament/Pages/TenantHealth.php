<?php

namespace App\Modules\Platform\Filament\Pages;

use App\Models\Team;
use App\Modules\Compliance\ActorType;
use App\Modules\Platform\Models\Admin;
use App\Modules\Platform\PlatformHealth;
use App\Modules\Recovery\RecoveryControls;
use App\Shared\Tenancy\TenantContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Every merchant on one screen: is anything silently stopping recovery?
 */
class TenantHealth extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'صحة المتاجر';

    protected static ?int $navigationSort = 1;

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        $health = app(PlatformHealth::class);

        // One health read per merchant row, shared by every column: each read
        // is about 10 queries, and there are 9 columns (review H14).
        $rows = [];
        $row = function (int $tenantId) use ($health, &$rows): array {
            return $rows[$tenantId] ??= $health->forTenant($tenantId);
        };
        $cell = fn (string $key) => fn (Team $record) => $row($record->id)[$key];

        return $table
            ->query(Team::query()->where('is_personal', false))
            ->columns([
                TextColumn::make('name')->label('المتجر')->searchable(),
                TextColumn::make('level')->label('الحالة')->badge()->state($cell('level'))
                    ->color(fn (string $state) => match ($state) {
                        'red' => 'danger',
                        'yellow' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('store_status')->label('سلة')->state($cell('store_status')),
                TextColumn::make('token_expires_at')->label('انتهاء التوكن')->state($cell('token_expires_at'))->since(),
                TextColumn::make('last_webhook_at')->label('آخر webhook')->state($cell('last_webhook_at'))->since(),
                TextColumn::make('reconciliation_gaps')->label('فجوات')->state($cell('reconciliation_gaps')),
                TextColumn::make('whatsapp_quality')->label('جودة الرقم')->state($cell('whatsapp_quality')),
                TextColumn::make('recovery_enabled')->label('الاسترجاع')->state(fn (Team $record) => $row($record->id)['recovery_enabled'] ? 'شغال' : 'متوقف'),
                TextColumn::make('failed_actions')->label('فشل 24س')->state($cell('failed_actions')),
            ])
            ->recordActions([
                Action::make('pause')
                    ->label('أوقف الإرسال')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->label('السبب')->required()->maxLength(500)])
                    ->action(function (Team $record, array $data) {
                        /** @var Admin|null $admin */
                        $admin = Auth::guard('admin')->user();

                        $cancelled = app(TenantContext::class)->run($record->id, fn () => app(RecoveryControls::class)
                            ->pause(ActorType::Admin, $admin?->id, 'admin: '.$data['reason']));

                        Notification::make()->title("اتوقف. اتلغى {$cancelled} تسلسل.")->success()->send();
                    }),
            ]);
    }
}
