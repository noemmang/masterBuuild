import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

// ── Interfaces ────────────────────────────────────────────────────────────────

export interface ComponenteGuardado {
  uuid: string;
  notas: string | null;
  guardado_en: string; 
  componente: {
    uuid: string;
    nombre: string;
    categoria: string;
    marca: string | null;
    imagen_url: string | null;
    mejor_precio: number | null;
    precio_actual: number | null;
    tienda: string | null;
    con_cupon: boolean;
    con_regalo: boolean;
  };
}

export interface AlertaPrecio {
  uuid: string;
  precio_objetivo: number;
  activa: boolean;
  disparada: boolean;
  componente: {
    uuid: string;
    nombre: string;
    categoria: string;
    precio_actual: number | null;
  };
}

export interface ComponenteSlotGuardado {
  uuid: string;
  nombre: string;
  cantidad: number;
  precio: number | null;
}

export interface SlotGuardado {
  categoria: string;
  label: string;
  componentes: ComponenteSlotGuardado[];
}

export interface ConfiguracionGuardada {
  uuid: string;
  nombre: string;
  notas: string | null;
  total: number;
  compatible: boolean;
  creada_en: string;
  slots: SlotGuardado[];
}

export interface GuardarConfiguracionPayload {
  nombre: string;
  notas: string | null;
  total: number;
  compatible: boolean;
  slots: SlotGuardado[];
}

// ── Servicio ──────────────────────────────────────────────────────────────────

@Injectable({ providedIn: 'root' })
export class GuardadoService {

  private readonly base = '/api/v1';

  constructor(private http: HttpClient) {}

  // ── Componentes guardados ─────────────────────────────────────

  listar(): Observable<ComponenteGuardado[]> {
    return this.http.get<ComponenteGuardado[]>(`${this.base}/guardados`);
  }

  guardar(componenteUuid: string, tiendaUuid?: string | null): Observable<{ uuid: string }> {
    const body: any = { componente_uuid: componenteUuid };
    if (tiendaUuid) body.tienda_uuid = tiendaUuid;
    return this.http.post<{ uuid: string }>(`${this.base}/guardados`, body);
  }

  eliminar(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.base}/guardados/${uuid}`);
  }

  actualizarNotas(uuid: string, notas: string | null): Observable<void> {
    return this.http.patch<void>(`${this.base}/guardados/${uuid}`, { notas });
  }

  // ── Alertas de precio ─────────────────────────────────────────

  listarAlertas(): Observable<AlertaPrecio[]> {
    return this.http.get<AlertaPrecio[]>(`${this.base}/alertas`);
  }

  crearAlerta(componenteUuid: string, precioObjetivo: number): Observable<{ uuid: string }> {
    return this.http.post<{ uuid: string }>(`${this.base}/alertas`, {
      componente_uuid:  componenteUuid,
      precio_objetivo:  precioObjetivo,
    });
  }

  eliminarAlerta(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.base}/alertas/${uuid}`);
  }

  toggleAlerta(uuid: string, activa: boolean): Observable<void> {
    return this.http.patch<void>(`${this.base}/alertas/${uuid}`, { activa });
  }

  // ── Configuraciones guardadas ─────────────────────────────────

  listarConfiguraciones(): Observable<ConfiguracionGuardada[]> {
    return this.http.get<ConfiguracionGuardada[]>(`${this.base}/configuraciones`);
  }

  obtenerConfiguracion(uuid: string): Observable<ConfiguracionGuardada> {
    return this.http.get<ConfiguracionGuardada>(`${this.base}/configuraciones/${uuid}`);
  }

  guardarConfiguracion(payload: GuardarConfiguracionPayload): Observable<{ uuid: string }> {
    return this.http.post<{ uuid: string }>(`${this.base}/configuraciones`, payload);
  }

  eliminarConfiguracion(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.base}/configuraciones/${uuid}`);
  }

  actualizarNotasConfiguracion(uuid: string, notas: string | null): Observable<void> {
    return this.http.patch<void>(`${this.base}/configuraciones/${uuid}`, { notas });
  }
}