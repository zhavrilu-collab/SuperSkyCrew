@php
    $profilActive = $profilActive ?? 'pravne';
    $profilOsnovniUrl = route('organization.settings.index', [
        'slug' => $organization->slug,
        'tab' => 'organizacija',
        'section' => 'osnovni-podaci',
    ]);
    $profilKatalogUrl = fn (string $kat) => route('organization.settings.index', [
        'slug' => $organization->slug,
        'tab' => 'organizacija',
        'section' => 'ustroj',
        'katalog' => $kat,
    ]);
@endphp
<div class="ustroj-pilule" role="navigation" aria-label="Pogledi profila">
    @if($isOwner)
        <a class="ustroj-pilula @if($profilActive === 'osnovni') is-active @endif" href="{{ $profilOsnovniUrl }}">Osnovni podaci</a>
    @endif
    @foreach(\App\Support\StructureCatalog::profileTabs() as $tabKey => $tabLabel)
        <a class="ustroj-pilula @if($profilActive === $tabKey) is-active @endif" href="{{ $profilKatalogUrl($tabKey) }}">{{ $tabLabel }}</a>
    @endforeach
</div>
