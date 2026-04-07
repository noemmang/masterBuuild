import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { tap } from 'rxjs/operators';

export interface Usuario {
  uuid: string;
  nombre: string;
  email: string;
  plan: string;
}

// Lo que devuelve realmente el backend
interface AuthResponse {
  token: string;
  user: {
    uuid: string;
    name: string;
    email: string;
  };
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly API = '/api/v1';
  usuario = signal<Usuario | null>(null);

  constructor(private http: HttpClient, private router: Router) {
    this.restaurarSesion();
  }

  private restaurarSesion(): void {
    const token   = localStorage.getItem('token');
    const usuario = localStorage.getItem('usuario');

    const tokenValido   = !!token   && token   !== 'undefined' && token   !== 'null';
    const usuarioValido = !!usuario && usuario !== 'undefined' && usuario !== 'null';

    if (!tokenValido || !usuarioValido) {
      localStorage.removeItem('token');
      localStorage.removeItem('usuario');
      this.usuario.set(null);
      return;
    }

    try {
      this.usuario.set(JSON.parse(usuario!));
    } catch {
      localStorage.removeItem('token');
      localStorage.removeItem('usuario');
      this.usuario.set(null);
    }
  }

  // Mapea la respuesta del backend al modelo interno
  private mapearUsuario(user: AuthResponse['user']): Usuario {
    return {
      uuid:   user.uuid,
      nombre: user.name,   // backend: name → frontend: nombre
      email:  user.email,
      plan:   'free',      // el backend aún no devuelve plan
    };
  }

  login(email: string, password: string) {
    return this.http.post<AuthResponse>(`${this.API}/auth/login`, { email, password }).pipe(
      tap(res => this.guardarSesion(res.token, this.mapearUsuario(res.user)))
    );
  }

  registro(nombre: string, email: string, password: string, password_confirmation: string) {
    return this.http.post<AuthResponse>(`${this.API}/auth/register`, {
      name: nombre,
      email,
      password,
      password_confirmation
    }).pipe(
      tap(res => this.guardarSesion(res.token, this.mapearUsuario(res.user)))
    );
  }

  logout() {
    return this.http.post(`${this.API}/auth/logout`, {}).pipe(
      tap(() => this.limpiarSesion())
    );
  }

  private guardarSesion(token: string, usuario: Usuario): void {
    localStorage.setItem('token', token);
    localStorage.setItem('usuario', JSON.stringify(usuario));
    this.usuario.set(usuario);
  }

  private limpiarSesion(): void {
    localStorage.removeItem('token');
    localStorage.removeItem('usuario');
    this.usuario.set(null);
    this.router.navigate(['/auth/login']);
  }

  estaAutenticado(): boolean {
    const token   = localStorage.getItem('token');
    const usuario = localStorage.getItem('usuario');
    return (
      !!token   && token   !== 'undefined' && token   !== 'null' &&
      !!usuario && usuario !== 'undefined' && usuario !== 'null'
    );
  }
}