<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="dark">
<title>{{ $titulo }}</title>
</head>
<body style="margin:0; padding:0; background-color:#0D1117; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0D1117;">
    <tr>
      <td align="center" style="padding: 40px 16px;">

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:480px; background-color:#161B22; border:1px solid #30363D; border-radius:16px;">
          <tr>
            <td style="padding:36px 28px 32px 28px;">

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center" style="padding-bottom:18px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td width="64" height="64" align="center" valign="middle" style="background-color:{{ $colorBadgeFondo }}; border-radius:50%;">
                          {!! $icono !!}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding-bottom:8px;">
                    <h1 style="margin:0; color:#E6EDF3; font-size:20px; line-height:1.3; font-weight:700;">{{ $titulo }}</h1>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding-bottom:24px;">
                    <p style="margin:0; color:#8B949E; font-size:14px; line-height:1.5;">{{ $subtitulo }}</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding-bottom:24px;">
                    @yield('contenido')
                  </td>
                </tr>
                <tr>
                  <td>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td align="center" bgcolor="#E6EDF3" style="border-radius:10px;">
                          <a href="{{ $url }}" style="display:block; padding:14px 0; color:#0D1117; font-size:15px; font-weight:700; text-decoration:none; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">{{ $textoBoton }}</a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding-top:22px;">
                    <p style="margin:0; color:#8B949E; font-size:12px; line-height:1.5;">{{ $textoFooter }}</p>
                  </td>
                </tr>
              </table>

            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>
</body>
</html>