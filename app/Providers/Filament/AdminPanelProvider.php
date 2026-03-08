<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Helpers\SamlHelper;
use App\Livewire\SurfConextProfileSection;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->darkMode(false)
            ->login(Login::class)
            ->passwordReset()
            ->colors([
                'primary' => Color::hex('#7fabc9'),
            ])
            ->navigationGroups([
                'Projects',
                'User Management',
                'Settings',
            ])
            ->userMenuItems(array_filter([
                'profile' => Action::make('profile')
                    ->label(fn () => auth()->user()->name)
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle'),
                'link-surf' => SamlHelper::isEnabled() ? Action::make('linkSurf')
                    ->label('Link SURF Conext')
                    ->url(fn (): string => route('saml.link'))
                    ->icon('heroicon-m-arrow-right-on-rectangle')
                    ->openUrlInNewTab(false)
                    ->visible(fn (): bool => empty(auth()->user()?->surf_id))
                    : null,
            ]))
            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('My Profile')
                    ->setNavigationLabel('My Profile')
                    ->setNavigationGroup('Group Profile')
                    ->setIcon('heroicon-o-user')
                    ->shouldShowAvatarForm()
                    ->shouldRegisterNavigation(false)
                    ->customProfileComponents([SurfConextProfileSection::class])
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\ProjectsStatsWidget::class,
                \App\Filament\Widgets\RecentProjectsTableWidget::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
