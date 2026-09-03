import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TimeoutError, catchError, finalize, throwError, timeout } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';

// Si el backend no responde (contenedor arrancando en frío, caída puntual,
// problema de red), antes el observable se quedaba esperando para siempre
// y el botón "Entrar" giraba indefinidamente: el usuario acababa
// refrescando la página, y esa recarga en una ruta que no es la raíz
// (/auth/login) es lo que disparaba el 404 de Azure Static Web Apps al
// no encontrar un fichero físico con ese nombre (ver staticwebapp.config.json).
// Cortar aquí con un timeout evita que el usuario llegue a esa situación.
const TIMEOUT_LOGIN_MS = 15_000;

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent implements OnInit {
  email = '';
  password = '';

  // IMPORTANTE: esta app corre en modo zoneless (Angular 21 sin zone.js,
  // ver AuthService.usuario que ya usa signal()). En zoneless, Angular solo
  // repinta la vista cuando escribe una signal, ocurre un evento DOM nativo,
  // o llega una respuesta de HttpClient (que sí está instrumentada).
  // El timer interno de rxjs timeout() es un setTimeout normal, ajeno a todo
  // eso: si `cargando`/`error` fueran propiedades sueltas, ese setTimeout
  // las cambiaba por dentro pero la vista nunca se enteraba y el spinner se
  // quedaba girando para siempre aunque el estado ya fuera correcto.
  // Al ser signals, cualquier .set() -venga de donde venga- fuerza el repintado.
  cargando = signal(false);
  error = signal('');

  /** Ruta a la que volver tras iniciar sesión (p. ej. si veníamos de "Guardar" sin sesión) */
  returnUrl = '/home';

  constructor(private auth: AuthService, private router: Router, private route: ActivatedRoute) {}

  ngOnInit(): void {
    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/home';
  }

  submit() {
    this.error.set('');
    this.cargando.set(true);
    this.auth.login(this.email, this.password)
      .pipe(
        timeout(TIMEOUT_LOGIN_MS),
        catchError(err => {
          if (err instanceof TimeoutError) {
            this.error.set('El servidor está tardando demasiado en responder. Inténtalo de nuevo en unos segundos.');
          }
          return throwError(() => err);
        }),
        // finalize corre siempre (éxito, error o timeout): garantiza que el
        // spinner se apaga sí o sí, incluso si se añade más lógica arriba.
        finalize(() => this.cargando.set(false)),
      )
      .subscribe({
        next: () => this.router.navigateByUrl(this.returnUrl),
        error: (err) => {
          if (err instanceof TimeoutError) return; // ya gestionado arriba
          this.error.set(err.error?.message || 'Credenciales incorrectas');
        }
      });
  }
}