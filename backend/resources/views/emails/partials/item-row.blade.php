{{-- Fila con el componente: imagen (si tiene) o icono genérico a la
     izquierda, nombre + tienda en el medio, precio (opcional) a la
     derecha. Se usa desde componente-disponible y alerta-precio. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0D1117; border:1px solid #30363D; border-radius:12px;">
  <tr>
    <td style="padding:14px 16px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td width="44" valign="middle">
            @if(!empty($componente->imagen_url))
              <img src="{{ $componente->imagen_url }}" width="44" height="44" alt="" style="display:block; border-radius:10px; object-fit:cover; background-color:#21262D;">
            @else
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td width="44" height="44" align="center" valign="middle" bgcolor="#21262D" style="border-radius:10px; color:#8B949E; font-size:20px;">&#128230;</td>
                </tr>
              </table>
            @endif
          </td>
          <td width="12"></td>
          <td valign="middle">
            <p style="margin:0; color:#E6EDF3; font-size:15px; font-weight:700; line-height:1.3;">{{ $componente->nombre }}</p>
            @isset($tienda)
              <p style="margin:2px 0 0 0; color:#8B949E; font-size:13px;">{{ $tienda }}</p>
            @endisset
          </td>
          @isset($precio)
            <td align="right" valign="middle" style="white-space:nowrap; padding-left:12px;">
              <p style="margin:0; color:{{ $colorPrecio ?? '#34D399' }}; font-size:16px; font-weight:700;">{{ $precio }}</p>
            </td>
          @endisset
        </tr>
      </table>
    </td>
  </tr>
</table>