import { Component, Input } from '@angular/core';
import { DatePipe } from '@angular/common';
import { Regalo } from '../../../core/services/componente.service';

/**
 * Regalos de UNA tienda, para enseñarlos justo debajo de su fila de precio en
 * el panel de tiendas. Recibe solo los regalos de esa tienda (vienen dentro de
 * cada precio en GET /componentes/{uuid}/precios) y no pinta nada si no hay.
 */
@Component({
  selector: 'app-regalos-tienda',
  standalone: true,
  imports: [DatePipe],
  templateUrl: './regalos-tienda.component.html',
  styleUrl: './regalos-tienda.component.scss',
})
export class RegalosTiendaComponent {
  @Input() regalos: Regalo[] | null | undefined = [];

  /**
   * Si el banner no carga (hotlinking bloqueado, URL rota…) se oculta junto a
   * su marco, sin dejar un hueco roto. El título y la fecha siguen ahí.
   */
  ocultarImagen(ev: Event): void {
    const img = ev.target as HTMLImageElement;
    (img.parentElement ?? img).style.display = 'none';
  }
}
