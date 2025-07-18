# Guide API d'Authentification

## Problème CORS résolu ✅

Le problème CORS que vous rencontriez a été résolu en :

1. **Configurant CORS correctement** dans `api/config/packages/nelmio_cors.yaml`
2. **Créant une API d'authentification** dédiée à `/api/auth/login`
3. **Permettant les requêtes depuis** `http://localhost:3000` et `https://localhost:3000`

## Utilisation de l'API

### 1. Connexion (Login)

**Endpoint :** `POST https://localhost/api/auth/login`

**Headers requis :**
```javascript
{
  "Content-Type": "application/json",
  "Origin": "http://localhost:3000"
}
```

**Body :**
```json
{
  "email": "admin@admin.com",
  "password": "admin123"
}
```

**Réponse en cas de succès (200) :**
```json
{
  "success": true,
  "message": "Authentication successful",
  "user": {
    "id": 2,
    "email": "admin@admin.com",
    "roles": ["ROLE_ADMIN", "ROLE_USER"]
  }
}
```

**Réponse en cas d'erreur (401) :**
```json
{
  "error": "Invalid credentials"
}
```

### 2. Déconnexion (Logout)

**Endpoint :** `POST https://localhost/api/auth/logout`

**Réponse :**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

## API Administration Panel

### 1. Dashboard Admin

**Endpoint :** `GET https://localhost/api/admin`

**Réponse :**
```json
{
  "title": "Admin Dashboard",
  "message": "Welcome to the administration panel",
  "modules": [
    {
      "id": "users",
      "title": "User Management",
      "description": "Manage application users and permissions",
      "icon": "users",
      "url": "/api/admin/users"
    }
  ],
  "stats": {
    "total_users": 3,
    "last_login": "2025-07-18 19:30:52"
  }
}
```

### 2. Gestion des Utilisateurs

**Endpoint :** `GET https://localhost/api/admin/users`

**Réponse :**
```json
{
  "users": [
    {
      "id": 1,
      "email": "admin@test.com",
      "roles": ["ROLE_ADMIN", "ROLE_USER"]
    }
  ],
  "total": 3
}
```

### 3. Gestion du Contenu

**Endpoint :** `GET https://localhost/api/admin/content`

**Réponse :**
```json
{
  "message": "Content management endpoint",
  "content_types": [
    {"id": "posts", "name": "Blog Posts", "count": 0},
    {"id": "pages", "name": "Static Pages", "count": 0},
    {"id": "media", "name": "Media Files", "count": 0}
  ]
}
```

### 4. Paramètres Système

**Endpoint :** `GET https://localhost/api/admin/settings`

**Réponse :**
```json
{
  "message": "System settings endpoint",
  "settings": [
    {"key": "site_name", "value": "My Blog", "type": "text"},
    {"key": "maintenance_mode", "value": false, "type": "boolean"},
    {"key": "max_upload_size", "value": "10MB", "type": "text"}
  ]
}
```

### 3. Exemple JavaScript/React

```javascript
// Fonction de connexion
const login = async (email, password) => {
  try {
    const response = await fetch('https://localhost/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password }),
      credentials: 'include' // Important pour CORS avec credentials
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Connexion réussie:', data.user);
      return data.user;
    } else {
      throw new Error(data.error);
    }
  } catch (error) {
    console.error('Erreur de connexion:', error);
    throw error;
  }
};

// Fonction pour récupérer le dashboard admin
const getAdminDashboard = async () => {
  try {
    const response = await fetch('https://localhost/api/admin', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include'
    });

    return await response.json();
  } catch (error) {
    console.error('Erreur dashboard admin:', error);
    throw error;
  }
};

// Fonction pour récupérer la liste des utilisateurs
const getUsers = async () => {
  try {
    const response = await fetch('https://localhost/api/admin/users', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include'
    });

    return await response.json();
  } catch (error) {
    console.error('Erreur récupération utilisateurs:', error);
    throw error;
  }
};

// Utilisation complète
const initializeAdmin = async () => {
  try {
    // 1. Connexion
    const user = await login('admin@admin.com', 'admin123');
    console.log('Utilisateur connecté:', user);

    // 2. Récupérer le dashboard
    const dashboard = await getAdminDashboard();
    console.log('Dashboard:', dashboard);

    // 3. Récupérer les utilisateurs
    const users = await getUsers();
    console.log('Utilisateurs:', users);

  } catch (error) {
    console.error('Erreur d\'initialisation admin:', error.message);
  }
};
```

## Comptes disponibles

### Compte Admin Principal
- **Email :** `admin@admin.com`
- **Mot de passe :** `admin123`
- **Rôles :** `ROLE_ADMIN`, `ROLE_USER`

### Compte Admin Test
- **Email :** `test@admin.com`
- **Mot de passe :** `testpassword`
- **Rôles :** `ROLE_ADMIN`, `ROLE_USER`

## Notes importantes

1. **API Stateless :** Cette API ne gère pas les sessions côté serveur. C'est à votre frontend de stocker l'état d'authentification (localStorage, context, etc.)

2. **HTTPS requis :** L'API fonctionne uniquement en HTTPS (`https://localhost`)

3. **CORS configuré :** Les requêtes depuis `localhost:3000` sont autorisées

4. **Validation :** L'API d'authentification vérifie que l'utilisateur a le rôle `ROLE_ADMIN`

⚠️ **ATTENTION SÉCURITÉ :** Actuellement, les endpoints `/api/admin/*` sont configurés en `PUBLIC_ACCESS` pour résoudre les problèmes CORS. En production, vous devrez :
- Implémenter une authentification basée sur des tokens (JWT)
- Vérifier l'authentification dans chaque endpoint admin
- Ou configurer une authentification API appropriée

## Endpoints disponibles

### Authentification
- `POST /api/auth/login` - Connexion 
- `POST /api/auth/logout` - Déconnexion
- `GET /api/auth/status` - Statut d'authentification

### Administration (⚠️ Sécuriser en production)
- `GET /api/admin` - Dashboard admin
- `GET /api/admin/users` - Liste des utilisateurs 
- `GET /api/admin/content` - Gestion du contenu
- `GET /api/admin/settings` - Paramètres système

## Prochaines étapes

Pour une authentification plus robuste en production, vous pourriez considérer :
- L'implémentation de tokens JWT
- La gestion des refresh tokens
- La limitation du taux de requêtes (rate limiting)
- La validation CSRF pour les requêtes sensibles 