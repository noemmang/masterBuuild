import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface ComponenteGuardado {
  uuid: string;
  notas: string | null;
  guardado_en: string;
  componente: {
    uuid: string;
    nombre: string;
    categoria: string;
    imagen_url: string | null;
    marca: string;
    mejor_precio: number | null;
    tienda: string | null;
    con_cupon: boolean;
    con_regalo: boolean;
  };
}

export interface AlertaPrecio {
  uuid: string;
  activa: boolean;
  precio_objetivo: number;
  disparada: boolean;
  disparada_en: string | null;
  componente: {
    uuid: string;
    nombre: string;
    categoria: string;
    imagen_url: string | null;
    precio_actual: number | null;
    tienda: string | null;
  };
}

@Injectable({ providedIn: 'root' })
export class GuardadoService {
  private readonly API       = '/api/v1/guardados';
  private readonly API_ALERT = '/api/v1/alertas';

  constructor(private http: HttpClient) {}

  // ── Guardados ──────────────────────────────────────────────
  listar(): Observable<ComponenteGuardado[]> {
    return this.http.get<ComponenteGuardado[]>(this.API);
  }

  guardar(componente_uuid: string, notas?: string): Observable<{ message: string; uuid: string }> {
    return this.http.post<{ message: string; uuid: string }>(this.API, { componente_uuid, notas });
  }

  actualizarNotas(uuid: string, notas: string | null): Observable<{ message: string }> {
    return this.http.patch<{ message: string }>(`${this.API}/${uuid}`, { notas });
  }

  eliminar(uuid: string): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.API}/${uuid}`);
  }

  // ── Alertas ────────────────────────────────────────────────
  listarAlertas(): Observable<AlertaPrecio[]> {
    return this.http.get<AlertaPrecio[]>(this.API_ALERT);
  }

  crearAlerta(componente_uuid: string, precio_objetivo: number): Observable<{ message: string; uuid: string }> {
    return this.http.post<{ message: string; uuid: string }>(this.API_ALERT, { componente_uuid, precio_objetivo });
  }

  toggleAlerta(uuid: string, activa: boolean): Observable<{ message: string }> {
    return this.http.patch<{ message: string }>(`${this.API_ALERT}/${uuid}`, { activa });
  }

  eliminarAlerta(uuid: string): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.API_ALERT}/${uuid}`);
  }
}