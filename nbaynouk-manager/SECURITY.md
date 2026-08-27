# Sécurité de Nbaynouk Manager

## Déploiement en production

L'application doit être servie exclusivement en HTTPS. Les variables suivantes sont recommandées :

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://manager.example.com

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true

ADMIN_EMAIL=adresse-administrateur@example.com
ADMIN_PASSWORD=un-mot-de-passe-long-unique
```

Ne jamais publier ou commiter `.env`, `APP_KEY`, les identifiants de base de données ou les mots de passe. Générer `APP_KEY` sur chaque environnement avec `php artisan key:generate`.

Le serveur web doit avoir `public/` comme document root, interdire l'accès à `.env`, `.git`, `storage/`, aux sauvegardes et aux fichiers de configuration, et désactiver l'indexation des dossiers. Les pièces jointes de services sont stockées sur le disque privé et ne sont servies que par une route Laravel authentifiée.

## Sessions et authentification

- Le login est limité à cinq échecs par combinaison adresse/IP et par minute.
- Les sessions sont régénérées après connexion et invalidées à la déconnexion.
- En production, activer les cookies `Secure`, conserver `HttpOnly` et `SameSite=Lax`.
- Utiliser un mot de passe administrateur distinct par environnement et ne pas exécuter les seeders de démonstration en production.

## Tests et données

PHPUnit force SQLite en mémoire via `phpunit.xml`. Ne retirez pas ces valeurs sans fournir une base de test dédiée. Ne jamais exécuter `migrate:fresh`, `db:wipe` ou les tests avec `RefreshDatabase` contre la base de travail.

## Maintenance

Exécuter régulièrement :

```bash
composer audit
npm audit
php artisan test
npm run build
```

Vérifier également les headers HTTPS/HSTS au niveau du reverse proxy. HSTS n'est pas envoyé par l'application afin de ne pas casser le développement local HTTP.

## Signalement

Ne pas publier de secret ou de donnée client dans un ticket. Documenter l'endpoint, l'impact et une reproduction minimale, puis révoquer immédiatement tout secret exposé.
