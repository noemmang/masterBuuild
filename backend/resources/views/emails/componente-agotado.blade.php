@extends('emails.layout', [
    'titulo'          => 'Ya no está disponible',
    'subtitulo'       => 'Un componente que guardaste se ha agotado o ha desaparecido de las tiendas que seguimos.',
    'colorBadgeFondo' => '#2E1F14',
    'textoBoton'      => 'Ver mis guardados',
    'textoFooter'     => 'Te avisaremos en cuanto vuelva a tener stock en alguna tienda.',
    'icono'           => '
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FF6B2B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9"/>
          <line x1="6.5" y1="6.5" x2="17.5" y2="17.5"/>
        </svg>
    ',
])

@section('contenido')
  @include('emails.partials.item-row', [
      'componente' => $componente,
      'tienda'     => null,
      'precio'     => null,
  ])
@endsection