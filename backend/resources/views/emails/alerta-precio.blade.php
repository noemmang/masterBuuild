@extends('emails.layout', [
    'titulo'          => '¡Tu alerta de precio saltó!',
    'subtitulo'       => 'Un componente de tu lista ha bajado hasta tu precio objetivo, o menos.',
    'colorBadgeFondo' => '#14301F',
    'textoBoton'      => 'Ver componente',
    'textoFooter'     => 'Lo recibes porque configuraste esta alerta de precio en MasterBuild. Si el precio vuelve a subir, seguiremos vigilando y te avisaremos otra vez si vuelve a bajar.',
    'icono'           => '
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.41l9.58 9.59a2 2 0 0 0 2.83 0l4.59-4.59a2 2 0 0 0 0-2.83z"/>
          <circle cx="7.5" cy="7.5" r="1.4" fill="#34D399" stroke="none"/>
        </svg>
    ',
])

@section('contenido')
  @include('emails.partials.item-row', [
      'componente'  => $componente,
      'tienda'      => $mejorPrecio->tienda->nombre ?? null,
      'precio'      => number_format((float) $mejorPrecio->precio, 2, ',', '.') . ' €',
      'colorPrecio' => '#34D399',
  ])

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px;">
    <tr>
      <td width="50%" align="center" style="padding:12px 8px; background-color:#0D1117; border:1px solid #30363D; border-radius:12px 0 0 12px; border-right:none;">
        <p style="margin:0; color:#8B949E; font-size:11px; text-transform:uppercase; letter-spacing:.04em;">Objetivo</p>
        <p style="margin:4px 0 0 0; color:#E6EDF3; font-size:16px; font-weight:700;">{{ number_format((float) $alerta->precio_objetivo, 2, ',', '.') }} €</p>
      </td>
      <td width="50%" align="center" style="padding:12px 8px; background-color:#0D1117; border:1px solid #30363D; border-radius:0 12px 12px 0;">
        <p style="margin:0; color:#8B949E; font-size:11px; text-transform:uppercase; letter-spacing:.04em;">Conseguido</p>
        <p style="margin:4px 0 0 0; color:#34D399; font-size:16px; font-weight:700;">{{ number_format((float) $mejorPrecio->precio, 2, ',', '.') }} €</p>
      </td>
    </tr>
  </table>
@endsection