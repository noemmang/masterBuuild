import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss'
})
export class HomeComponent {
  categorias = [
    { nombre: 'CPU', slug: 'cpu' },
    { nombre: 'GPU', slug: 'gpu' },
    { nombre: 'RAM', slug: 'ram' },
    { nombre: 'Placa Base', slug: 'placa_base' },
    { nombre: 'Almacenamiento', slug: 'almacenamiento' },
    { nombre: 'PSU', slug: 'psu' },
    { nombre: 'Gabinete', slug: 'gabinete' },
    { nombre: 'Refrigeraci�n', slug: 'refrigeracion_aire' },
  ];
}
