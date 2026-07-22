import { Component, ElementRef, HostListener, inject, OnInit, Renderer2, ViewChild } from '@angular/core';
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

  // Referencia al contenedor del botón de avatar + dropdown. Solo existe en
  // el DOM cuando hay usuario autenticado (está dentro de un @if), por eso
  // es opcional.
  @ViewChild('userMenuWrap') private userMenuWrap?: ElementRef<HTMLElement>;

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

  // Cierra el desplegable si se pulsa en cualquier sitio fuera del botón de
  // avatar o del propio menú (incluido el resto del header: logo, nav,
  // toggle de tema, o cualquier parte del contenido de la página).
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    if (!this.menuUsuarioAbierto) return;
    const wrap = this.userMenuWrap?.nativeElement;
    const clickDentro = !!wrap && wrap.contains(event.target as Node);
    if (!clickDentro) {
      this.menuUsuarioAbierto = false;
    }
  }
}