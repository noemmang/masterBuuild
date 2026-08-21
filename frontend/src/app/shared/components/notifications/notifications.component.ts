import {
  Component, ElementRef, HostListener, OnDestroy,
  ViewChild, effect, inject, signal,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { Notificacion, NotificacionService } from '../../../core/services/notificacion.service';

/**
 * Campanita de notificaciones del header, entre el toggle de tema y el
 * avatar de usuario. Es el mismo contenido que ya se manda por correo al
 * registrarse o tras el scraping (guardado agotado / disponible de
 * nuevo / alerta de precio saltada) — aquí simplemente se lee lo que el
 * backend ya guardó vía el canal 'database' de esas notificaciones (ver
 * NotificacionController). No incluye la de cuenta eliminada: para
 * cuando esa notificación se genera, el usuario ya no existe.
 */
@Component({
  selector: 'app-notifications',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './notifications.component.html',
  styleUrl: './notifications.component.scss',
})
export class NotificationsComponent implements OnDestroy {
  private auth   = inject(AuthService);
  private router = inject(Router);
  servicio       = inject(NotificacionService);

  // Referencia al contenedor botón + panel, para el cierre al hacer clic
  // fuera (mismo patrón que el desplegable de usuario en HeaderComponent).
  @ViewChild('notifWrap') private notifWrap?: ElementRef<HTMLElement>;

  panelAbierto = signal(false);
  cargandoMas  = signal(false);

  constructor() {
    // El header —y por tanto este componente— vive durante toda la
    // sesión, así que un login/logout sin recargar la página también
    // tiene que arrancar o parar la carga y el sondeo del contador.
    effect(() => {
      const usuario = this.auth.usuario();
      if (usuario) {
        if (!this.servicio.cargado()) {
          this.servicio.listar().subscribe({ error: () => {} });
        }
        this.servicio.iniciarSondeo();
      } else {
        this.servicio.detenerSondeo();
      }
    });
  }

  ngOnDestroy(): void {
    this.servicio.detenerSondeo();
  }

  toggle(): void {
    const seVaAbrir = !this.panelAbierto();
    this.panelAbierto.set(seVaAbrir);

    // Al abrir refrescamos la lista: el sondeo de fondo solo trae el
    // contador, no el contenido, así que puede haber novedades desde la
    // última vez que se abrió el panel.
    if (seVaAbrir) {
      this.servicio.listar().subscribe({ error: () => {} });
    }
  }

  cerrar(): void {
    this.panelAbierto.set(false);
  }

  abrir(notificacion: Notificacion): void {
    if (!notificacion.leida) {
      this.servicio.marcarLeida(notificacion.id).subscribe({ error: () => {} });
    }
    this.cerrar();
    if (notificacion.url) {
      this.router.navigateByUrl(notificacion.url);
    }
  }

  descartar(event: MouseEvent, id: string): void {
    event.stopPropagation();
    this.servicio.descartar(id).subscribe({ error: () => {} });
  }

  marcarTodasLeidas(): void {
    this.servicio.marcarTodasLeidas().subscribe({ error: () => {} });
  }

  descartarTodas(): void {
    this.servicio.descartarTodas().subscribe({ error: () => {} });
  }

  cargarMas(): void {
    if (this.cargandoMas()) return;
    this.cargandoMas.set(true);
    this.servicio.cargarMas().subscribe({
      next:  () => this.cargandoMas.set(false),
      error: () => this.cargandoMas.set(false),
    });
  }

  tiempoRelativo(fechaIso: string): string {
    const fecha    = new Date(fechaIso).getTime();
    const segundos = Math.max(0, Math.floor((Date.now() - fecha) / 1000));

    if (segundos < 60) return 'hace un momento';

    const minutos = Math.floor(segundos / 60);
    if (minutos < 60) return `hace ${minutos} minuto${minutos === 1 ? '' : 's'}`;

    const horas = Math.floor(minutos / 60);
    if (horas < 24) return `hace ${horas} hora${horas === 1 ? '' : 's'}`;

    const dias = Math.floor(horas / 24);
    if (dias < 7) return `hace ${dias} día${dias === 1 ? '' : 's'}`;

    const semanas = Math.floor(dias / 7);
    if (semanas < 5) return `hace ${semanas} semana${semanas === 1 ? '' : 's'}`;

    return new Date(fechaIso).toLocaleDateString('es-ES', {
      day: '2-digit', month: '2-digit', year: 'numeric',
    });
  }

  // Cierra el panel si se pulsa fuera de él. Al ser un listener sobre
  // `document` independiente del de HeaderComponent, abrir este panel
  // cierra el desplegable de usuario (y viceversa) sin acoplar ambos
  // componentes entre sí.
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    if (!this.panelAbierto()) return;
    const wrap = this.notifWrap?.nativeElement;
    const clickDentro = !!wrap && wrap.contains(event.target as Node);
    if (!clickDentro) {
      this.panelAbierto.set(false);
    }
  }
}