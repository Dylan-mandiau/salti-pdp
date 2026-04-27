# PDP SALTI — Plan de Prévention digital

Application web Laravel pour digitaliser le remplissage du Plan de Prévention SALTI 2026,
avec workflow présentiel ou à distance, signatures électroniques, et génération d'un PDF
strictement identique au modèle officiel.

## Stack

- **Laravel 13** (PHP 8.3+)
- **MySQL / MariaDB** (compatible O2switch et serveurs internes)
- **FPDI + TCPDF** pour la génération PDF (le modèle officiel sert de template)
- **Tailwind via CDN** + **Alpine.js** + **signature_pad.js** (frontend, pas de build npm)
- **SMTP** (O2switch ou interne) pour l'envoi des liens prestataires

## Comptes par défaut (après seed)

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| `qse@salti.fr` | `changeme` | Admin QSE (voit tous les PDP) |
| `bordeaux@salti.fr` | `changeme` | Agence Bordeaux |
| `lyon@salti.fr` | `changeme` | Agence Lyon |
| `paris@salti.fr` | `changeme` | Agence Paris |

> **Changer immédiatement** ces mots de passe une fois en prod.

## Modèle simplifié : 1 compte par agence

Plutôt que de gérer un compte par agent, chaque agence a **un seul compte partagé**
(ex. `bordeaux@salti.fr`). L'identité de l'agent qui rédige est saisie dans le champ
"Donneur d'ordre" du PDP — c'est cette identité qui apparaît sur le PDF signé.

Le compte QSE central est le seul à voir tous les PDP de toutes les agences.

## Mode de remplissage

À la création d'un PDP, l'utilisateur choisit entre :
- **🏢 Présentiel** — les deux parties sur le même appareil, signatures sur place
- **🌐 À distance** — un lien magique unique est envoyé au prestataire par email

## Sécurité du lien prestataire

Par défaut : **lien magique simple** (token aléatoire 64 chars, expiration 7 jours).
Activable au cas par cas : **Option B avec OTP** par email pour les chantiers sensibles.

## Génération PDF

Le PDF officiel `Plan de prévention - PDP SALTI 2026.pdf` est embarqué comme **template figé**
(`storage/app/templates/pdp-salti-2026.pdf`). L'application ne le redessine pas — elle écrit
les valeurs saisies par-dessus aux coordonnées définies dans `config/pdp_pdf_mapping.php`.

Si SALTI met à jour le PDF officiel : remplacer le fichier template + recalibrer les coordonnées.

## Installation locale (dev)

```bash
git clone https://github.com/Dylan-mandiau/salti-pdp.git
cd pdp-salti
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite        # SQLite pour dev local
php artisan migrate --seed
php artisan serve
```

> Sur macOS Sequoia, certains setups ont des soucis IO avec le `php artisan serve` builtin.
> Préférer Laravel Valet, DDEV, ou tester directement sur l'hébergement cible.

## Déploiement

Voir [DEPLOIEMENT.md](DEPLOIEMENT.md) pour la procédure complète.

Résumé express O2switch :
```bash
# Sur le serveur, après git clone
bash deploy.sh
```

## Mises à jour ultérieures

```bash
git pull
bash deploy.sh
```

## Commandes utiles

```bash
# Test de génération PDF avec un PDP factice rempli
php artisan pdp:test-generate

# Vider tous les caches
php artisan optimize:clear

# Voir les logs en temps réel
php artisan pail
```

## Structure clé

```
app/
├── Http/Controllers/
│   ├── PdpController.php             # SALTI : dashboard, wizard, signatures
│   └── PrestataireAccessController.php  # Accès public via lien magique
├── Models/
│   ├── User.php                      # Comptes agence + QSE
│   ├── Pdp.php                       # Plan de prévention
│   ├── Prestataire.php
│   ├── PdpIntervenant.php
│   ├── PdpDocument.php
│   └── PdpAuditLog.php
└── Services/
    └── PdpPdfGenerator.php           # Génération PDF avec FPDI/TCPDF

config/
└── pdp_pdf_mapping.php               # Coordonnées des champs sur le PDF officiel

resources/views/
├── layouts/pdp.blade.php             # Layout avec Tailwind CDN
├── pdp/
│   ├── dashboard.blade.php
│   ├── choose-mode.blade.php
│   └── edit.blade.php                # Wizard 6 étapes
└── prestataire/
    └── show.blade.php                # Vue prestataire (lien magique)

storage/app/templates/
└── pdp-salti-2026.pdf                # Template officiel SALTI (versionné)

database/migrations/                  # 6 tables : users, pdps, prestataires, etc.
```

## Calibration des coordonnées PDF

Les coordonnées initiales dans `config/pdp_pdf_mapping.php` sont **estimées**. Au premier rendu,
certains champs seront décalés de quelques mm. Procédure de calibration :

1. Lancer `php artisan pdp:test-generate` → génère un PDF de test rempli
2. Ouvrir le PDF généré et le superposer mentalement / visuellement au PDF officiel
3. Noter les décalages champ par champ
4. Ajuster les `x` et `y` dans `config/pdp_pdf_mapping.php` (1 unité = 1 mm)
5. Recommencer jusqu'à ce que tout tombe pile

## Licence

Application interne SALTI — tous droits réservés.
