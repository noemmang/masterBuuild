import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  { path: '', redirectTo: 'home', pathMatch: 'full' },
  {
    path: 'auth',
    loadChildren: () => import('./features/auth/auth.routes').then(m => m.AUTH_ROUTES)
  },
  {
    path: 'home',
    loadComponent: () => import('./features/home/home.component').then(m => m.HomeComponent)
  },
  {
    path: 'buscar',
    loadComponent: () => import('./features/search/search.component').then(m => m.SearchComponent),
  },
  {
    path: 'configurador',
    loadComponent: () => import('./features/configurator/configurator.component').then(m => m.ConfiguratorComponent),
    canActivate: [authGuard]
  },
  {
    path: 'gabinetes',
    loadComponent: () => import('./features/case-viewer/case-viewer.component').then(m => m.CaseViewerComponent)
  },
  {
    path: 'comparar',
    loadComponent: () =>
      import('./features/spec-compare/spec-compare.component').then(m => m.SpecCompareComponent),
  },
  {
    path: 'guardados',
    loadComponent: () => import('./features/saved/saved.component').then(m => m.SavedComponent),
    canActivate: [authGuard]
  },
  {
    path: 'perfil',
    loadComponent: () => import('./features/profile/profile.component').then(m => m.ProfileComponent),
    canActivate: [authGuard]
  },
  { path: '**', redirectTo: 'home' }
];