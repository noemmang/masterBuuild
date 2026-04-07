import { Component, inject, OnInit, Renderer2 } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, CommonModule],
  templateUrl: './header.component.html',
  styleUrl: './header.component.scss'
})
export class HeaderComponent implements OnInit {
  auth             = inject(AuthService);
  private renderer = inject(Renderer2);

  menuUsuarioAbierto = false;
  modoOscuro         = false;

  ngOnInit(): void {
    if (localStorage.getItem('tema') === 'dark') {
      this.activarOscuro();
    }
  }

  toggleTema(): void {
    this.modoOscuro ? this.activarClaro() : this.activarOscuro();
  }

  private activarOscuro(): void {
    this.renderer.setAttribute(document.documentElement, 'data-theme', 'dark');
    localStorage.setItem('tema', 'dark');
    this.modoOscuro = true;
  }

  private activarClaro(): void {
    this.renderer.removeAttribute(document.documentElement, 'data-theme');
    localStorage.setItem('tema', 'light');
    this.modoOscuro = false;
  }

  logout(): void {
    this.auth.logout().subscribe({
      next:  () => {},
      error: () => this.auth['limpiarSesion']()
    });
  }

  toggleMenuUsuario(): void {
    this.menuUsuarioAbierto = !this.menuUsuarioAbierto;
  }
}