@php
    $trialOrganization = $trialOrganization ?? (app()->bound('currentOrganization') ? app('currentOrganization') : null);
    $trialOrgUser = $trialOrgUser ?? (app()->bound('currentOrganizationUser') ? app('currentOrganizationUser') : null);
@endphp

@if($trialOrganization && auth()->check())
    @if($trialOrganization->onTrial())
        <div class="trial-notice" role="status">
            <div class="trial-notice__inner">
                <span class="trial-notice__label">Probni period</span>
                <span class="trial-notice__text">
                    još <strong>{{ $trialOrganization->trialDaysRemaining() }}</strong>
                    {{ $trialOrganization->trialDaysRemaining() === 1 ? 'dan' : 'dana' }}
                    (do {{ $trialOrganization->trial_ends_at->timezone(config('app.timezone'))->format('d.m.Y.') }})
                    · {{ \App\Support\OrganizationFeatures::planLabel($trialOrganization->plan) }}
                    — bez naplate do isteka
                </span>
                @if($trialOrgUser?->isOwner())
                    <a href="{{ route('organization.settings.index', ['slug' => $trialOrganization->slug, 'tab' => 'pretplata']) }}"
                       class="trial-notice__action">Pretplata</a>
                @endif
            </div>
        </div>
    @elseif($trialOrganization->trialExpired())
        <div class="trial-notice trial-notice--expired" role="status">
            <div class="trial-notice__inner">
                <span class="trial-notice__label">Istekao</span>
                <span class="trial-notice__text">
                    Probni period je istekao. Aktivan je Osnovni paket.
                    @if($trialOrgUser?->isOwner())
                        Odaberite paket u konzoli za napredne značajke.
                    @endif
                </span>
                @if($trialOrgUser?->isOwner())
                    <a href="{{ route('organization.settings.index', ['slug' => $trialOrganization->slug, 'tab' => 'pretplata']) }}"
                       class="trial-notice__action">Odaberi paket</a>
                @endif
            </div>
        </div>
    @endif
@endif
