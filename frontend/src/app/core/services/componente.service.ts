import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';

export interface Componente {
  uuid: string;
  nombre: string;
  categoria: string;
  imagen_url: string | null;
  marca: { nombre: string } | null;
  precio_min: number | null;
  precio_max: number | null;
  num_tiendas: number;
  tiene_cupon: boolean;
  bajada_precio: boolean;
}

export interface PaginatedResponse {
  data: Componente[];
  current_page: number;
  last_page: number;
  total: number;
}

@Injectable({ providedIn: 'root' })
export class ComponenteService {
  private readonly API = '/api/v1';

  constructor(private http: HttpClient) {}

  buscar(params: {
    categoria?: string;
    q?: string;
    page?: number;
    marca?: string;
    orden?: string;
  }): Observable<PaginatedResponse> {
    let httpParams = new HttpParams();
    if (params.categoria) httpParams = httpParams.set('categoria', params.categoria);
    if (params.q)         httpParams = httpParams.set('buscar', params.q);
    if (params.page)      httpParams = httpParams.set('page', params.page.toString());
    if (params.marca)     httpParams = httpParams.set('marca', params.marca);
    if (params.orden)     httpParams = httpParams.set('ordenar', params.orden);

    return this.http.get<any>(`${this.API}/componentes`, { params: httpParams }).pipe(
      map(res => ({
        ...res,
        data: res.data.map((c: any) => this.mapearComponente(c))
      }))
    );
  }

  getPrecios(uuid: string): Observable<any> {
    return this.http.get(`${this.API}/componentes/${uuid}/precios`);
  }

  private mapearComponente(c: any): Componente {
    const precios: number[] = (c.precios_actuales ?? []).map((p: any) => Number(p.precio));
    return {
      uuid:         c.uuid,
      nombre:       c.nombre,
      categoria:    c.categoria,
      imagen_url:   c.imagen_url,
      marca:        c.marca ?? null,
      precio_min:   precios.length > 0 ? Math.min(...precios) : null,
      precio_max:   precios.length > 0 ? Math.max(...precios) : null,
      num_tiendas:  precios.length,
      tiene_cupon:  (c.cupones_activos ?? []).length > 0,
      bajada_precio: false, // pendiente de implementar en backend
    };
  }
}