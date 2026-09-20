import { Component } from '@angular/core';

/**
 * Icono de "incluye regalo" para la esquina de una tarjeta de componente.
 * Mismo tamaño y forma que el resto de badges de estado (guardado, alerta…).
 * Quien lo usa decide CUÁNDO mostrarlo con `@if (comp.tiene_regalo)`; el
 * backend ya garantiza que solo es true si hay un regalo vigente en una tienda
 * con stock.
 */
@Component({
  selector: 'app-badge-regalo',
  standalone: true,
  templateUrl: './badge-regalo.component.html',
  styleUrl: './badge-regalo.component.scss',
})
export class BadgeRegaloComponent {}
