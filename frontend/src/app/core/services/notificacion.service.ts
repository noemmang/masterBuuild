import { Injectable, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';

// ── Tipos ─────────────────────────────────────────────────────────────────────

// Coincide con el campo `tipo` que cada Notification de Laravel guarda en
// su toDatabase() (ver backend/app/Notifications/*). CuentaEliminada no
// está porque esa notificación nunca pasa por el canal 'database'.
export type TipoNotificacion =
  | 'cuenta_creada'
  | 'componente_agotado'
  | 'componente_disponible'
  | 'alerta_precio';

export interface Notificacion {
  id: string;
  tipo: TipoNotificacion | string;
  titulo: string;
  mensaje: string;
  url: string | null;
  imagen: string | null;
  leida: boolean;
  creada_en: string; // ISO 8601
}

interface NotificacionesResponse {
  data: Notificacion[];
  no_leidas: number;
  pagina: number;
  ultima_pagina: number;
  total: number;
}

// ── Servicio ──────────────────────────────────────────────────────────────────

@Injectable({ providedIn: 'root' })
export class NotificacionService {
  private readonly API = environment.apiUrl;
  private http = inject(HttpClient);

  // Estado compartido: el mismo signal alimenta tanto el badge del
  // botón de campana como la lista del panel, sin importar desde qué
  // instancia del componente se dispare la carga.
  notificaciones = signal<Notificacion[]>([]);
  noLeidas       = signal(0);
  cargando       = signal(false);
  cargado        = signal(false);
  ultimaPagina   = signal(1);
  pagina         = signal(1);

  private intervaloSondeo?: ReturnType<typeof setInterval>;

  // Lista de notificaciones del usuario autenticado (primera página).
  listar(): Observable<NotificacionesResponse> {
    this.cargando.set(true);
    return this.http.get<NotificacionesResponse>(`${this.API}/notificaciones`).pipe(
      tap(res => {
        this.notificaciones.set(res.data);
        this.noLeidas.set(res.no_leidas);
        this.pagina.set(res.pagina);
        this.ultimaPagina.set(res.ultima_pagina);
        this.cargando.set(false);
        this.cargado.set(true);
      }),
    );
  }

  // Páginas siguientes ("cargar más" al hacer scroll en el panel).
  cargarMas(): Observable<NotificacionesResponse> {
    const siguiente = this.pagina() + 1;
    return this.http
      .get<NotificacionesResponse>(`${this.API}/notificaciones`, {
        params: { por_pagina: 20, page: siguiente },
      })
      .pipe(
        tap(res => {
          this.notificaciones.update(actual => [...actual, ...res.data]);
          this.noLeidas.set(res.no_leidas);
          this.pagina.set(res.pagina);
          this.ultimaPagina.set(res.ultima_pagina);
        }),
      );
  }

  // Sondeo ligero del contador, para que el badge se actualice aunque el
  // panel esté cerrado (p.ej. tras el scraping nocturno) sin recargar la
  // página.
  actualizarContador(): void {
    this.http.get<{ no_leidas: number }>(`${this.API}/notificaciones/contador`).subscribe({
      next: res => this.noLeidas.set(res.no_leidas),
      error: () => {}, // el badge simplemente no se actualiza en este ciclo
    });
  }

  iniciarSondeo(intervaloMs = 60_000): void {
    this.detenerSondeo();
    this.intervaloSondeo = setInterval(() => this.actualizarContador(), intervaloMs);
  }

  detenerSondeo(): void {
    if (this.intervaloSondeo) {
      clearInterval(this.intervaloSondeo);
      this.intervaloSondeo = undefined;
    }
  }

  marcarLeida(id: string): Observable<{ message: string }> {
    return this.http.patch<{ message: string }>(`${this.API}/notificaciones/${id}/leer`, {}).pipe(
      tap(() => {
        let eraNoLeida = false;
        this.notificaciones.update(lista =>
          lista.map(n => {
            if (n.id === id && !n.leida) eraNoLeida = true;
            return n.id === id ? { ...n, leida: true } : n;
          }),
        );
        if (eraNoLeida) this.noLeidas.update(n => Math.max(0, n - 1));
      }),
    );
  }

  marcarTodasLeidas(): Observable<{ message: string }> {
    return this.http.patch<{ message: string }>(`${this.API}/notificaciones/leer-todas`, {}).pipe(
      tap(() => {
        this.notificaciones.update(lista => lista.map(n => ({ ...n, leida: true })));
        this.noLeidas.set(0);
      }),
    );
  }

  descartar(id: string): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.API}/notificaciones/${id}`).pipe(
      tap(() => {
        const eraNoLeida = this.notificaciones().find(n => n.id === id)?.leida === false;
        this.notificaciones.update(lista => lista.filter(n => n.id !== id));
        if (eraNoLeida) this.noLeidas.update(n => Math.max(0, n - 1));
      }),
    );
  }

  descartarTodas(): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.API}/notificaciones`).pipe(
      tap(() => {
        this.notificaciones.set([]);
        this.noLeidas.set(0);
      }),
    );
  }

  // Se llama al cerrar sesión: evita que el panel muestre un instante
  // las notificaciones del usuario anterior si otro inicia sesión justo
  // después en la misma pestaña.
  limpiarEstado(): void {
    this.detenerSondeo();
    this.notificaciones.set([]);
    this.noLeidas.set(0);
    this.cargando.set(false);
    this.cargado.set(false);
    this.pagina.set(1);
    this.ultimaPagina.set(1);
  }
}