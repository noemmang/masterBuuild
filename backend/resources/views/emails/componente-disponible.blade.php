@extends('emails.layout', [
    'titulo'          => 'Ya está disponible de nuevo',
    'subtitulo'       => 'Un componente que guardaste ha vuelto a tener stock.',
    'colorBadgeFondo' => '#14301F',
    'textoBoton'      => 'Ver componente',
    'textoFooter'     => 'Lo recibes porque guardaste este componente en MasterBuild.',
    'icono'           => '
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 8L12 3 3 8l9 5 9-5z"/>
          <path d="M3 8v8l9 5 9-5V8"/>
          <path d="M12 13v8"/>
        </svg>
    ',
])

@section('contenido')
  @include('emails.partials.item-row', [
      'componente'  => $componente,
      'tienda'      => $oferta->tienda->nombre ?? null,
      'precio'      => number_format((float) $oferta->precio, 2, ',', '.') . ' €',
      'colorPrecio' => '#34D399',
  ])
@endsection