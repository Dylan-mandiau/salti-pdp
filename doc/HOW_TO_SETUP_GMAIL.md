# Configuration Gmail Workspace pour PDP SALTI

Ce document décrit la procédure **à faire UNE FOIS** par le service QSE pour
permettre à PDP SALTI d'envoyer des emails au nom de chaque agence (`bordeaux@salti.fr`,
`lyon@salti.fr`, etc.) sans gérer aucun mot de passe SMTP.

**Principe** : on crée un compte de service Google qui a le droit d'envoyer
des emails *à la place de* n'importe quel utilisateur du domaine `@salti.fr`.

**Coût** : 0 € (la Gmail API est gratuite jusqu'à des volumes très élevés).

---

## ⏱ Temps estimé : 15-20 minutes

## Pré-requis

- Un accès admin à Google Workspace `salti.fr` (Console d'administration)
- Un accès à Google Cloud Platform (GCP) — utiliser un compte Workspace `@salti.fr`
- L'application PDP SALTI déjà en ligne sur O2switch

---

## 1. Créer un projet GCP

1. Aller sur https://console.cloud.google.com/
2. En haut, cliquer sur le sélecteur de projet → **Nouveau projet**
3. Nom : `pdp-salti`
4. Cliquer **Créer**

## 2. Activer l'API Gmail

1. Dans le menu de gauche → **APIs et services** → **Bibliothèque**
2. Chercher **Gmail API**
3. Cliquer **Activer**

## 3. Créer un Service Account

1. Menu → **APIs et services** → **Identifiants**
2. **Créer des identifiants** → **Compte de service**
3. Nom : `pdp-salti-mailer`
4. ID : `pdp-salti-mailer` (auto-rempli)
5. Description : `Envoi des emails du PDP SALTI`
6. Cliquer **Créer et continuer**
7. **Rôle** : laisser vide (pas besoin de rôle GCP)
8. Cliquer **OK**

## 4. Récupérer le JSON du Service Account

1. Toujours dans **Identifiants**, dans la liste des Comptes de service, cliquer sur `pdp-salti-mailer`
2. Onglet **Clés**
3. **Ajouter une clé** → **Créer une clé** → **JSON** → **Créer**
4. Un fichier JSON se télécharge automatiquement (genre `pdp-salti-XXXXX.json`)

⚠ **Ce fichier est confidentiel** — ne le commit jamais sur GitHub.

5. **Noter le `client_email`** dans ce JSON (ressemble à
   `pdp-salti-mailer@pdp-salti.iam.gserviceaccount.com`)
6. **Noter le `client_id`** (chiffres uniquement, ex. `123456789012345678901`)

## 5. Activer la Domain-Wide Delegation

C'est l'étape qui autorise le service account à "se faire passer pour" un user du workspace.

1. Toujours sur la page du service account dans GCP → onglet **Détails**
2. Cliquer **Afficher les détails de la délégation à l'échelle du domaine**
3. Cocher la case et **enregistrer** (peut s'appeler "Activer la délégation à l'échelle du domaine G Suite")

## 6. Autoriser dans Google Workspace Admin

1. Aller sur https://admin.google.com/ (connexion avec un admin Workspace)
2. Menu → **Sécurité** → **Contrôle des accès et des données** → **Contrôles des API**
3. Section **Délégation à l'échelle du domaine** → **Gérer la délégation à l'échelle du domaine**
4. **Ajouter** :
   - **ID client** : le `client_id` du JSON (étape 4 point 6)
   - **Champs d'application OAuth** :
     ```
     https://www.googleapis.com/auth/gmail.send
     ```
5. **Autoriser**

## 7. Déposer le JSON sur le serveur O2switch

```bash
# Depuis ton Mac, transfère le fichier :
scp ~/Downloads/pdp-salti-XXXXX.json ufaj3133@TON-SERVEUR.o2switch.net:~/repositories/salti-pdp/storage/app/private/gmail-service-account.json
```

Sur le serveur :
```bash
chmod 600 storage/app/private/gmail-service-account.json
```

## 8. Configurer le `.env` sur O2switch

Ouvre `~/repositories/salti-pdp/.env` et ajoute (ou laisse par défaut) :

```ini
GMAIL_SERVICE_ACCOUNT_PATH=/home/ufaj3133/repositories/salti-pdp/storage/app/private/gmail-service-account.json
```

Puis :
```bash
php artisan config:clear
```

## 9. Tester

1. Connecte-toi en tant que SALTI
2. Crée un PDP en mode "À distance"
3. Envoie le lien à un email de test (par exemple ton propre Gmail perso)
4. Tu dois recevoir l'email avec :
   - **Expéditeur** : `bordeaux@salti.fr` (ou l'agence connectée)
   - **Sujet** : `Plan de Prévention SALTI à compléter — ...`
   - **Corps HTML** : bandeau noir + jaune SALTI + bouton "Compléter le PDP"

---

## En cas d'échec

### Le mail n'arrive pas mais aucune erreur visible

Logs : `tail -50 ~/repositories/salti-pdp/storage/logs/laravel.log` → cherche `[GmailService]`.

### Erreur "unauthorized_client"
- L'autorisation Domain-Wide Delegation n'est pas validée. Refaire l'étape 6.
- Vérifie le `client_id` dans Workspace Admin (chiffres uniquement, pas l'email).

### Erreur "Service account not authorized to impersonate user"
- L'utilisateur impersonné (l'email de l'agence) doit exister dans le Workspace.
- Vérifie `bordeaux@salti.fr` est bien créé.

### Erreur "invalid_grant"
- L'horloge serveur est désynchronisée. Sur O2switch, peu probable, mais sur serveur interne :
  `sudo timedatectl set-ntp true`

---

## Sécurité

- Le JSON service account est dans `storage/app/private/` qui est **hors du Document Root** Apache → non accessible par URL.
- Le scope est limité à `gmail.send` (pas de lecture des emails, pas d'autres API).
- En cas de suspicion de fuite : régénérer la clé dans GCP et écraser le JSON sur le serveur.
- Le JSON N'EST PAS dans le repo Git (vérifié par `.gitignore` : `storage/app/private/*` est ignoré).

---

## Plan B (en cas de blocage GCP)

Si tu n'as pas le temps de configurer GCP, l'app reste fonctionnelle :
- Le bouton "Envoyer au prestataire" affiche le lien magique à copier-coller dans un mail manuel
- Tu envoies depuis ta boîte Gmail manuellement
- Quand tu auras configuré GCP, l'envoi auto fonctionnera tout seul (aucun changement de code requis)

C'est ce qui se passe actuellement si `GMAIL_SERVICE_ACCOUNT_PATH` est vide ou pointe sur un fichier inexistant.
