import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss'
})
export class RegisterComponent {
  nombre = '';
  email = '';
  password = '';
  password_confirmation = '';
  cargando = false;
  error = '';

  constructor(private auth: AuthService, private router: Router) {}

  submit() {
    this.error = '';
    if (this.password !== this.password_confirmation) {
      this.error = 'Las contraseñas no coinciden';
      return;
    }
    this.cargando = true;
    this.auth.registro(this.nombre, this.email, this.password, this.password_confirmation).subscribe({
      next: () => this.router.navigate(['/home']),
      error: (err) => {
        // Laravel devuelve { message, errors: { campo: [mensajes] } } en un 422.
        // Priorizamos el primer error de campo (ej. "El correo... no existe")
        // sobre el mensaje genérico "The given data was invalid.".
        const errores = err.error?.errors;
        const primerError = errores ? (Object.values(errores)[0] as string[] | undefined)?.[0] : undefined;
        this.error = primerError || err.error?.message || 'Error al crear la cuenta';
        this.cargando = false;
      }
    });
  }
}