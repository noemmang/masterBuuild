@extends('emails.layout', [
    'titulo'          => 'Tu cuenta se ha eliminado',
    'subtitulo'       => 'Se han borrado tu cuenta y todos tus datos (guardados, alertas, configuraciones) de MasterBuild.',
    'colorBadgeFondo' => '#21262D',
    'textoBoton'      => 'Volver a MasterBuild',
    'textoFooter'     => 'Si no has sido tú, ya no es posible recuperar los datos porque se han eliminado por completo. Te recomendamos revisar la seguridad de tu correo.',
    'icono'           => '
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#8B949E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4"/>
          <path d="M16 17l5-5-5-5"/>
          <path d="M21 12H9"/>
        </svg>
    ',
])

@section('contenido')
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td align="center">
        <p style="margin:0; color:#8B949E; font-size:14px; line-height:1.6;">Gracias por haber usado MasterBuild{{ $nombre ? ', '.$nombre : '' }}. Si algún día quieres volver, puedes crear una cuenta nueva con el mismo correo cuando quieras.</p>
      </td>
    </tr>
  </table>
@endsection