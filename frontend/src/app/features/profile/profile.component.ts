import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../core/services/auth.service';
import { GuardadoService } from '../../core/services/guardado.service';

type Seccion = 'cuenta' | 'apariencia' | 'notificaciones' | 'privacidad';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.scss'
})
export class ProfileComponent implements OnInit {

  private auth           = inject(AuthService);
  private guardadoService = inject(GuardadoService);
  private http           = inject(HttpClient);
  private router         = inject(Router);

  seccionActiva = signal<Seccion>('cuenta');

  usuario = this.auth.usuario;

  // Stats
  numGuardados      = signal(0);
  numAlertas        = signal(0);
  numConfiguraciones = signal(0);
  miembroDesde      = signal('');

  // Cuenta - cambiar nombre
  nuevoNombre   = '';
  guardandoNombre = signal(false);
  nombreOk      = signal(false);
  errorNombre   = signal('');

  // Cuenta - cambiar contraseña
  passActual    = '';
  passNueva     = '';
  passConfirm   = '';
  guardandoPass = signal(false);
  passOk        = signal(false);
  errorPass     = signal('');

  // Apariencia
  modoOscuro = signal(false);

  // Notificaciones
  notifBajadaPrecio = signal(true);
  notifCupones      = signal(true);
  notifRegalos      = signal(false);

  // Eliminar cuenta
  confirmarEliminar = false;
  eliminando        = signal(false);

  ngOnInit(): void {
    this.modoOscuro.set(localStorage.getItem('tema') === 'dark');
    this.cargarStats();
  }

  private cargarStats(): void {
    this.guardadoService.listar().subscribe({
      next: (gs) => {
        this.numGuardados.set(gs.length);
        if (gs.length > 0) {
          // Fecha más antigua = fecha de registro aproximada
          const fechas = gs.map(g => new Date(g.guardado_en).getTime());
          const primera = new Date(Math.min(...fechas));
          this.miembroDesde.set(primera.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' }));
        }
      }
    });
    this.guardadoService.listarAlertas().subscribe({
      next: (as) => this.numAlertas.set(as.length)
    });
  }

  setSeccion(s: Seccion): void {
    this.seccionActiva.set(s);
    // Resetear estados al cambiar de sección
    this.nombreOk.set(false);
    this.errorNombre.set('');
    this.passOk.set(false);
    this.errorPass.set('');
    this.confirmarEliminar = false;
  }

  // ── Cambiar nombre ────────────────────────────────────────

  guardarNombre(): void {
    if (!this.nuevoNombre.trim()) return;
    this.guardandoNombre.set(true);
    this.errorNombre.set('');
    this.http.patch('/api/v1/auth/me', { name: this.nuevoNombre }).subscribe({
      next: () => {
        // Actualizar el signal del usuario localmente
        const u = this.usuario();
        if (u) {
          const actualizado = { ...u, nombre: this.nuevoNombre };
          localStorage.setItem('usuario', JSON.stringify(actualizado));
          this.auth.usuario.set(actualizado);
        }
        this.guardandoNombre.set(false);
        this.nombreOk.set(true);
        this.nuevoNombre = '';
        setTimeout(() => this.nombreOk.set(false), 3000);
      },
      error: () => {
        this.errorNombre.set('No se pudo actualizar el nombre.');
        this.guardandoNombre.set(false);
      }
    });
  }

  // ── Cambiar contraseña ────────────────────────────────────

  guardarContrasena(): void {
    this.errorPass.set('');
    if (!this.passActual || !this.passNueva || !this.passConfirm) {
      this.errorPass.set('Rellena todos los campos.');
      return;
    }
    if (this.passNueva.length < 8) {
      this.errorPass.set('La contraseña debe tener al menos 8 caracteres.');
      return;
    }
    if (this.passNueva !== this.passConfirm) {
      this.errorPass.set('Las contraseñas no coinciden.');
      return;
    }
    this.guardandoPass.set(true);
    this.http.patch('/api/v1/auth/password', {
      current_password:      this.passActual,
      password:              this.passNueva,
      password_confirmation: this.passConfirm,
    }).subscribe({
      next: () => {
        this.passActual = '';
        this.passNueva  = '';
        this.passConfirm = '';
        this.guardandoPass.set(false);
        this.passOk.set(true);
        setTimeout(() => this.passOk.set(false), 3000);
      },
      error: (err) => {
        this.errorPass.set(
          err.error?.message ?? 'No se pudo cambiar la contraseña.'
        );
        this.guardandoPass.set(false);
      }
    });
  }

  // ── Apariencia ────────────────────────────────────────────

  toggleTema(): void {
    if (this.modoOscuro()) {
      document.documentElement.removeAttribute('data-theme');
      localStorage.setItem('tema', 'light');
      this.modoOscuro.set(false);
    } else {
      document.documentElement.setAttribute('data-theme', 'dark');
      localStorage.setItem('tema', 'dark');
      this.modoOscuro.set(true);
    }
  }

  // ── Eliminar cuenta ───────────────────────────────────────

  eliminarCuenta(): void {
    if (!this.confirmarEliminar) { this.confirmarEliminar = true; return; }
    this.eliminando.set(true);
    this.http.delete('/api/v1/auth/me').subscribe({
      next: () => {
        localStorage.removeItem('token');
        localStorage.removeItem('usuario');
        this.auth.usuario.set(null);
        this.router.navigate(['/home']);
      },
      error: () => this.eliminando.set(false)
    });
  }

  // ── Logout ────────────────────────────────────────────────

  logout(): void {
    this.auth.logout().subscribe({
      next:  () => {},
      error: () => this.auth['limpiarSesion']()
    });
  }

  // ── Helpers ───────────────────────────────────────────────

  inicialUsuario(): string {
    return this.usuario()?.nombre?.charAt(0).toUpperCase() ?? '?';
  }

  formatFecha(fecha: string): string {
    return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
  }
}