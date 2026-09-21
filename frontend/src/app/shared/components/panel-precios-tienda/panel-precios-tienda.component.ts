import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PriceHistoryComponent } from '../price-history/price-history.component';
import { RegalosTiendaComponent } from '../regalos-tienda/regalos-tienda.component';

/**
 * Aside de "tienda + precios + regalos" de un componente: cabecera (nombre,
 * categoría, nº de tiendas), acciones (guardar / alerta de precio), el
 * listado de tiendas con su precio y sus regalos, y el histórico de precios.
 *
 * Antes Buscador y Configurador tenían cada uno su propia copia de este
 * bloque (HTML y SCSS casi idénticos). Ahora ambos usan este único
 * componente, así el CSS es exactamente el mismo en los dos sitios.
 *
 * Es puramente de presentación: todo el estado (qué componente está
 * abierto, si está guardado, si hay una alerta de precio activa...) sigue
 * viviendo en el componente padre (Buscador o Configurador), que lo pasa
 * por @Input y reacciona a los @Output para llamar a sus propios métodos
 * (servicios, navegación, etc.). Este componente no hace ninguna llamada
 * HTTP.
 */
@Component({
  selector: 'app-panel-precios-tienda',
  standalone: true,
  imports: [CommonModule, PriceHistoryComponent, RegalosTiendaComponent],
  templateUrl: './panel-precios-tienda.component.html',
  styleUrl: './panel-precios-tienda.component.scss',
})
export class PanelPreciosTiendaComponent {
  // ── Cabecera ──────────────────────────────────────────────────────────
  @Input() categoriaLabel = '';
  @Input() nombreComponente = '';
  @Input() numTiendas: number | null | undefined = null;
  /** UUID del componente, para pedir su histórico de precios. */
  @Input() componenteUuid = '';

  // ── Guardar / Alerta de precio ─────────────────────────────────────────
  @Input() guardado = false;
  @Input() guardando = false;
  @Input() eliminandoGuardado = false;
  @Input() tieneAlerta = false;
  @Input() mostrarAlerta = false;
  @Input() precioObjetivo: number | null = null;
  @Input() guardandoAlerta = false;

  // ── Precios de las tiendas ───────────────────────────────────────────
  @Input() cargandoPrecios = false;
  @Input() precios: any[] = [];
  @Input() precioSeleccionado: any | null = null;

  @Output() cerrar = new EventEmitter<void>();
  @Output() guardar = new EventEmitter<void>();
  @Output() quitarGuardado = new EventEmitter<void>();
  @Output() toggleAlerta = new EventEmitter<void>();
  @Output() guardarAlerta = new EventEmitter<void>();
  @Output() quitarAlerta = new EventEmitter<void>();
  @Output() precioObjetivoChange = new EventEmitter<string>();
  @Output() seleccionarPrecio = new EventEmitter<any>();

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
