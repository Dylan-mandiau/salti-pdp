@php
    /**
     * Une ligne du tableau des risques.
     * @var string $situation
     * @var bool   $applicable
     * @var string $risque    (texte multiligne avec \n)
     * @var string $mesure    (texte multiligne avec \n)
     * @var bool   $eu
     * @var bool   $ee
     */
@endphp
<tr>
    <td><strong>{{ $situation }}</strong></td>
    <td class="col-applicable">{!! $applicable ? '<strong>X</strong>' : '☐' !!}</td>
    <td>{!! nl2br(e($risque)) !!}</td>
    <td>{!! nl2br(e($mesure)) !!}</td>
    <td class="col-resp">{!! $eu ? '<strong>X</strong>' : '' !!}</td>
    <td class="col-resp">{!! $ee ? '<strong>X</strong>' : '' !!}</td>
</tr>
