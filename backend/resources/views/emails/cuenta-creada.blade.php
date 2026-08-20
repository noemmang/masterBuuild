@extends('emails.layout', [
    'titulo'          => '¡Bienvenido a MasterBuild!',
    'subtitulo'       => 'Tu cuenta se ha creado correctamente. Ya puedes empezar a comparar y guardar componentes.',
    'colorBadgeFondo' => '#14301F',
    'textoBoton'      => 'Ir a MasterBuild',
    'textoFooter'     => 'Recibes este correo porque acabas de crear una cuenta en MasterBuild con este email.',
    'icono'           => '
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 6L9 17l-5-5"/>
        </svg>
    ',
])

@section('contenido')
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0D1117; border:1px solid #30363D; border-radius:12px;">
    <tr>
      <td style="padding:4px 16px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="padding:12px 0; border-bottom:1px solid #21262D;">
              <p style="margin:0; color:#E6EDF3; font-size:14px; font-weight:700;">Compara precios</p>
              <p style="margin:2px 0 0 0; color:#8B949E; font-size:13px;">Entre varias tiendas, actualizado cada noche.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:12px 0; border-bottom:1px solid #21262D;">
              <p style="margin:0; color:#E6EDF3; font-size:14px; font-weight:700;">Guarda componentes</p>
              <p style="margin:2px 0 0 0; color:#8B949E; font-size:13px;">Te avisamos si se agotan o si vuelven a estar disponibles.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:12px 0;">
              <p style="margin:0; color:#E6EDF3; font-size:14px; font-weight:700;">Crea alertas de precio</p>
              <p style="margin:2px 0 0 0; color:#8B949E; font-size:13px;">Te escribimos en cuanto un precio baje hasta donde tú digas.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
@endsection