import { Component, OnInit, signal, inject, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../core/services/auth.service';
import { GuardadoService } from '../../core/services/guardado.service';

type Seccion = 'perfil' | 'seguridad' | 'apariencia' | 'notificaciones' | 'privacidad' | 'eliminar';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './Profile.component.html',
  styleUrl: './Profile.component.scss'
})
export class ProfileComponent implements OnInit, AfterViewInit {

  @ViewChild('mainScroll') mainScrollRef!: ElementRef<HTMLDivElement>;
  @ViewChild('avatarInput') avatarInputRef!: ElementRef<HTMLInputElement>;

  private auth            = inject(AuthService);
  private guardadoService = inject(GuardadoService);
  private http            = inject(HttpClient);
  private router          = inject(Router);

  // Sección visible en el scroll-spy
  seccionVisible = signal<Seccion>('perfil');

  usuario = this.auth.usuario;

  // Stats
  numGuardados       = signal(0);
  numAlertas         = signal(0);
  numConfiguraciones = signal(0);
  miembroDesde       = signal('');

  // Avatar
  avatarUrl   = signal<string>(this.auth.usuario()?.avatar ?? '');
  avatarError = signal('');

  // Cuenta - cambiar nombre
  nuevoNombre     = '';
  guardandoNombre = signal(false);
  nombreOk        = signal(false);
  errorNombre     = signal('');

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

  private secciones: Seccion[] = ['perfil', 'seguridad', 'apariencia', 'notificaciones', 'privacidad', 'eliminar'];

  ngOnInit(): void {
    this.modoOscuro.set(localStorage.getItem('tema') === 'dark');
    this.avatarUrl.set(this.auth.usuario()?.avatar ?? '');
    this.cargarStats();
  }

  ngAfterViewInit(): void {
    // El scroll-spy se activa con el evento (scroll) en el template
  }

  // ── Scroll-spy ────────────────────────────────────────────────

  onScroll(): void {
    const container = this.mainScrollRef.nativeElement;
    const scrollTop = container.scrollTop;

    for (const id of [...this.secciones].reverse()) {
      const el = document.getElementById(id);
      if (el && el.offsetTop - 80 <= scrollTop) {
        this.seccionVisible.set(id);
        return;
      }
    }
    this.seccionVisible.set('perfil');
  }

  scrollTo(id: Seccion): void {
    const container = this.mainScrollRef.nativeElement;
    const el = document.getElementById(id);
    if (el) {
      container.scrollTo({ top: el.offsetTop - 16, behavior: 'smooth' });
    }
  }

  // ── Avatar ────────────────────────────────────────────────────

  triggerAvatarInput(): void {
    this.avatarInputRef.nativeElement.click();
  }

  onAvatarChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    this.avatarError.set('');

    if (file.size > 2 * 1024 * 1024) {
      this.avatarError.set('La imagen no puede superar 2 MB.');
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      const base64 = reader.result as string;
      this.avatarUrl.set(base64);
      this.guardarAvatar(base64);
    };
    reader.readAsDataURL(file);

    // Limpiar input para permitir resubir el mismo fichero
    input.value = '';
  }

  private guardarAvatar(base64: string): void {
    this.http.patch('/api/v1/auth/me', { avatar: base64 }).subscribe({
      next: () => {
        const u = this.usuario();
        if (u) {
          const actualizado = { ...u, avatar: base64 };
          localStorage.setItem('usuario', JSON.stringify(actualizado));
          this.auth.usuario.set(actualizado);
        }
      },
      error: () => {
        this.avatarError.set('No se pudo guardar la foto de perfil.');
        this.avatarUrl.set(this.auth.usuario()?.avatar ?? '');
      }
    });
  }

  eliminarAvatar(): void {
    this.http.patch('/api/v1/auth/me', { avatar: null }).subscribe({
      next: () => {
        this.avatarUrl.set('');
        const u = this.usuario();
        if (u) {
          const actualizado = { ...u, avatar: '' };
          localStorage.setItem('usuario', JSON.stringify(actualizado));
          this.auth.usuario.set(actualizado);
        }
      },
      error: () => this.avatarError.set('No se pudo eliminar la foto.')
    });
  }

  // ── Stats ────────────────────────────────────────────────────

  private cargarStats(): void {
    this.guardadoService.listar().subscribe({
      next: (gs) => {
        this.numGuardados.set(gs.length);
        if (gs.length > 0) {
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

  // ── Cambiar nombre ────────────────────────────────────────────

  guardarNombre(): void {
    if (!this.nuevoNombre.trim()) return;
    this.guardandoNombre.set(true);
    this.errorNombre.set('');
    this.http.patch('/api/v1/auth/me', { name: this.nuevoNombre }).subscribe({
      next: () => {
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

  // ── Cambiar contraseña ────────────────────────────────────────

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
        this.passActual  = '';
        this.passNueva   = '';
        this.passConfirm = '';
        this.guardandoPass.set(false);
        this.passOk.set(true);
        setTimeout(() => this.passOk.set(false), 3000);
      },
      error: (err) => {
        this.errorPass.set(err.error?.message ?? 'No se pudo cambiar la contraseña.');
        this.guardandoPass.set(false);
      }
    });
  }

  // ── Apariencia ────────────────────────────────────────────────

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

  // ── Eliminar cuenta ───────────────────────────────────────────

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

  // ── Logout ────────────────────────────────────────────────────

  logout(): void {
    this.auth.logout().subscribe({
      next:  () => {},
      error: () => this.auth['limpiarSesion']()
    });
  }

  // ── Helpers ───────────────────────────────────────────────────

  inicialUsuario(): string {
    return this.usuario()?.nombre?.charAt(0).toUpperCase() ?? '?';
  }

  formatFecha(fecha: string): string {
    return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
  }
}