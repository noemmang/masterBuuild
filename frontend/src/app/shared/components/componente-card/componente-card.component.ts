import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TooltipDirective } from '../../directives/tooltip.directive';
import { BadgeRegaloComponent } from '../badge-regalo/badge-regalo.component';
import { Componente } from '../../../core/services/componente.service';

/**
 * Tarjeta de un componente en la rejilla de resultados: imagen, categoría,
 * nombre, marca, precio y nº de tiendas — más los iconos de estado
 * (guardado, alerta, agotado, regalo, bajada de precio) y, si la categoría
 * activa admite varias unidades, el stepper de cantidad.
 *
 * Antes Buscador y Configurador tenían cada uno su propia copia casi
 * idéntica de esta tarjeta (con nombres de clase distintos: `.card-*` vs
 * `.cfg-card-*`). Ahora ambos usan este único componente: mismo HTML y
 * mismo CSS en los dos sitios, y todas las tarjetas miden siempre lo mismo
 * de alto (el nombre reserva espacio para 2 líneas aunque solo ocupe 1, y
 * la marca reserva su línea aunque el componente no tenga marca), así que
 * el precio y el nº de tiendas quedan siempre a la misma altura.
 *
 * Es puramente de presentación: `estaGuardado`, `tieneAlerta`, etc. viven en
 * el componente padre, que los pasa por @Input y reacciona a los @Output.
 */
@Component({
  selector: 'app-componente-card',
  standalone: true,
  imports: [CommonModule, TooltipDirective, BadgeRegaloComponent],
  templateUrl: './componente-card.component.html',
  styleUrl: './componente-card.component.scss',
})
export class ComponenteCardComponent {
  @Input({ required: true }) comp!: Componente;

  /** Resaltado con borde de acento: "ya añadido al slot" (Configurador) o
   *  "es el que tiene el panel de precios abierto" (Buscador). */
  @Input() seleccionado = false;

  @Input() guardado = false;
  @Input() tieneAlerta = false;
  /** Los iconos de guardado/alerta solo tienen sentido si hay sesión. */
  @Input() logueado = false;

  /** El buscador sí muestra el icono "↓" de bajada de precio; el
   *  configurador no lo usaba, así que por defecto va oculto. */
  @Input() mostrarBajadaPrecio = false;

  /** Solo el Configurador: la categoría activa admite varias unidades
   *  (RAM, ventiladores...) y por tanto muestra el stepper de cantidad. */
  @Input() apilable = false;
  @Input() cantidad = 0;

  @Output() seleccionar = new EventEmitter<Componente>();
  @Output() incrementar = new EventEmitter<void>();
  @Output() decrementar = new EventEmitter<void>();

  get agotado(): boolean {
    return this.comp.num_tiendas > 0 && !this.comp.en_stock;
  }

  formatPrecio(precio: number | null | undefined): string {
    if (!precio) return '';
    return precio.toLocaleString('es-ES', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
}
