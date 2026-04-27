# Déploiement de PDP SALTI

## Phase 1 : Test sur O2switch

### Pré-requis
- Un compte O2switch actif avec accès cPanel
- Un sous-domaine ou domaine (ex. `pdp.salti.fr` ou `pdp.spatiocom.com`)
- Un dépôt Git (GitHub / GitLab) avec le code

### Étapes

#### 1. Préparer le dépôt Git
```bash
cd /Users/dylanmandiau/Desktop/Claude/PDP/pdp-salti
git init
git add .
git commit -m "Initial commit: PDP SALTI v1"
git remote add origin https://github.com/Dylan-mandiau/salti-pdp.git
git push -u origin main
```

#### 2. Cloner sur O2switch via cPanel
1. Connectez-vous au cPanel O2switch
2. Allez dans **Git Version Control**
3. Cliquez sur **Create**
4. Renseignez :
   - URL : `https://github.com/Dylan-mandiau/salti-pdp.git`
   - Repository Path : `/home/USER/repositories/pdp-salti`
   - Repository Name : `pdp-salti`

#### 3. Pointer le domaine vers le dossier `public`
1. Dans cPanel → **Domains** → **Subdomains** (ou Domains)
2. Créer ou éditer `pdp.spatiocom.com`
3. Document Root : `/home/USER/repositories/pdp-salti/public`

#### 4. Installer les dépendances PHP
SSH vers O2switch :
```bash
ssh USER@USER.o2switch.net
cd ~/repositories/salti-pdp
composer install --no-dev --optimize-autoloader
```

> O2switch fournit Composer en CLI. Si manquant : `curl -sS https://getcomposer.org/installer | php`

#### 5. Configurer l'environnement
```bash
cp .env.example .env
nano .env
```

Modifier les valeurs critiques :
```ini
APP_NAME="PDP SALTI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pdp.spatiocom.com

# Base de données — créer en cPanel > MySQL Databases
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USER_pdpsalti
DB_USERNAME=USER_pdpadmin
DB_PASSWORD=mot-de-passe-fort

# Mail — utiliser le SMTP O2switch
MAIL_MAILER=smtp
MAIL_HOST=USER.o2switch.net
MAIL_PORT=465
MAIL_USERNAME=no-reply@salti.fr
MAIL_PASSWORD=mot-de-passe-mailbox
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=no-reply@salti.fr
MAIL_FROM_NAME="PDP SALTI"
```

#### 6. Générer la clé d'app + lancer les migrations
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force      # crée les comptes d'agence par défaut
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 7. Permissions des dossiers
```bash
chmod -R 775 storage bootstrap/cache
```

#### 8. Vérifier que ça fonctionne
Aller sur `https://pdp.spatiocom.com/login` et se connecter avec :
- `qse@salti.fr` / `changeme` (admin QSE)
- `bordeaux@salti.fr` / `changeme` (agence)

> ⚠ **Changer immédiatement les mots de passe** via le profil utilisateur.

### Mises à jour ultérieures
```bash
ssh USER@USER.o2switch.net
cd ~/repositories/salti-pdp
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Phase 2 : Migration vers serveur interne (MariaDB)

### Quand ?
Une fois le prototype validé en test sur O2switch.

### Pré-requis serveur interne
- Linux (Debian/Ubuntu/CentOS)
- PHP 8.3+ avec extensions : `curl`, `mbstring`, `xml`, `bcmath`, `gd`, `pdo_mysql`, `zip`, `fileinfo`
- MariaDB 10.6+
- Apache 2.4 ou Nginx 1.18+
- Composer 2+
- Git
- Certificat SSL (Let's Encrypt ou interne)

### Étapes

#### 1. Exporter la BDD depuis O2switch
```bash
# Sur O2switch (SSH)
mysqldump --single-transaction USER_pdpsalti > /tmp/pdp-salti-export.sql

# Récupérer en local ou directement vers le serveur interne
scp USER@USER.o2switch.net:/tmp/pdp-salti-export.sql ./
```

#### 2. Préparer le serveur interne
```bash
# Installer les paquets nécessaires
sudo apt update
sudo apt install -y php8.3 php8.3-{curl,mbstring,xml,bcmath,gd,mysql,zip,fileinfo,cli,fpm} \
                    mariadb-server nginx git composer

# Créer la BDD
sudo mariadb -e "CREATE DATABASE pdp_salti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                 CREATE USER 'pdp_salti'@'localhost' IDENTIFIED BY 'mot-de-passe-fort';
                 GRANT ALL ON pdp_salti.* TO 'pdp_salti'@'localhost';
                 FLUSH PRIVILEGES;"

# Importer les données depuis O2switch
sudo mariadb pdp_salti < pdp-salti-export.sql
```

#### 3. Cloner et configurer
```bash
cd /var/www
sudo git clone https://github.com/salti/pdp-salti.git
cd pdp-salti
sudo chown -R www-data:www-data .

sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data cp .env.example .env

# Éditer .env
sudo -u www-data nano .env
```

`.env` adapté pour interne :
```ini
APP_NAME="PDP SALTI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pdp.salti-interne.lan

DB_CONNECTION=mysql            # MariaDB est compatible MySQL driver
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pdp_salti
DB_USERNAME=pdp_salti
DB_PASSWORD=mot-de-passe-fort

MAIL_MAILER=smtp
MAIL_HOST=smtp.salti-interne.lan
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@salti.fr
MAIL_FROM_NAME="PDP SALTI"
```

#### 4. Recopier les fichiers `storage/`
```bash
# Depuis O2switch, archiver
ssh USER@USER.o2switch.net 'tar czf /tmp/storage.tar.gz -C ~/repositories/salti-pdp storage'
scp USER@USER.o2switch.net:/tmp/storage.tar.gz .

# Extraire sur le serveur interne
sudo tar xzf storage.tar.gz -C /var/www/pdp-salti --overwrite
sudo chown -R www-data:www-data /var/www/pdp-salti
```

#### 5. Configurer Nginx
```nginx
# /etc/nginx/sites-available/pdp-salti
server {
    listen 80;
    listen 443 ssl http2;
    server_name pdp.salti-interne.lan;

    ssl_certificate /etc/ssl/certs/salti.crt;
    ssl_certificate_key /etc/ssl/private/salti.key;

    root /var/www/pdp-salti/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    client_max_body_size 20M; # pour les uploads de CACES, habilitations
}
```

```bash
sudo ln -s /etc/nginx/sites-available/pdp-salti /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### 6. Caches Laravel
```bash
cd /var/www/pdp-salti
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

#### 7. Sauvegarde automatique (cron)
```cron
# /etc/cron.d/pdp-salti-backup
0 2 * * * www-data mysqldump pdp_salti | gzip > /backup/pdp-$(date +\%Y\%m\%d).sql.gz
0 3 * * * root tar czf /backup/storage-$(date +\%Y\%m\%d).tar.gz -C /var/www/pdp-salti storage
```

---

## Annexe : Variables `.env` clés

| Variable | Valeur | Notes |
|----------|--------|-------|
| `APP_NAME` | `"PDP SALTI"` | Affiché dans le titre |
| `APP_ENV` | `production` | Désactive le debug |
| `APP_DEBUG` | `false` | **Toujours false en prod** |
| `APP_URL` | URL complète | Pour les liens magiques |
| `DB_CONNECTION` | `mysql` | Compatible MariaDB |
| `MAIL_*` | SMTP | Pour les liens prestataires |
| `SESSION_DRIVER` | `database` (par défaut) | OK partout |
| `LOG_CHANNEL` | `stack` | Logs combinés |

## Annexe : Vérifications post-déploiement

- [ ] La page de login s'affiche et fonctionne
- [ ] Connexion avec un compte d'agence
- [ ] Création d'un PDP en mode présentiel
- [ ] Création d'un PDP en mode distance (avec envoi du lien par mail)
- [ ] Réception du mail de lien magique
- [ ] Le prestataire peut ouvrir le lien et remplir
- [ ] Auto-save fonctionne (regarder Network dans DevTools)
- [ ] Signature SALTI puis EE → génération du PDF final
- [ ] PDF final visuellement identique au modèle officiel
- [ ] Téléchargement et impression du PDF
- [ ] Cloisonnement : un compte agence ne voit pas les PDP des autres agences
- [ ] Le compte QSE central voit tout
