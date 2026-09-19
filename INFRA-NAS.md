# Infrastructure NAS — référence

> Document de reprise pour une nouvelle session Claude.
> Dernière mise à jour : 19 septembre 2026.
>
> **Aucun secret dans ce fichier** (dépôt git). Les emplacements sont indiqués,
> les valeurs sont à demander à l'utilisateur.

---

## 1. Matériel et accès

| Élément | Valeur |
|---|---|
| NAS | Synology DS225+ (x86_64, geminilake), DSM 7.4.1-90080 |
| IP locale | `192.168.1.172` |
| Nom | `NAS_CLGO` |
| Compte admin | `clgo` (uid 1026, groupe `users` gid 100) |
| Box | Freebox Pop — `192.168.1.254` |
| IP publique | fixe — `82.67.203.111` |
| DDNS | `zigoxaz.ddns.net` (pas de wildcard : les sous-domaines ne résolvent pas) |
| Volume | `/volume1` — 3,5 To, ~677 Go utilisés |

**DNS réseau** : Quad9 (`9.9.9.10`) configuré manuellement sur la Freebox.
Conséquence : `mafreebox.freebox.fr` ne résout pas → utiliser `192.168.1.254`.

### Accès SSH

**Le port 22 n'est PAS redirigé** (fermé volontairement le 13/09/2026 pour couper
les ~11 000 tentatives d'intrusion quotidiennes).

- Clé : `C:\Users\CLGO\.ssh\nas_key` (ed25519), présente dans
  `/volume1/homes/clgo/.ssh/authorized_keys`
- En local uniquement : `ssh -i C:/Users/CLGO/.ssh/nas_key clgo@192.168.1.172`
- **Pour redonner l'accès distant** : remettre la redirection `22 → 22` vers
  `192.168.1.172` sur la Freebox.

### sudo

L'utilisateur `clgo` est dans le groupe `administrators`. `sudo` exige le mot de
passe DSM — à demander à l'utilisateur, jamais stocké ici.

```bash
# Forme qui fonctionne (stdin consommé par sudo) :
ssh -i "C:/Users/CLGO/.ssh/nas_key" clgo@192.168.1.172 'echo "MDP" | sudo -S <commande>'
```

⚠️ **Piège** : un heredoc Python/bash après `sudo -S` ne s'exécute pas — le mot de
passe consomme stdin. Copier le script via `cat > /tmp/x.py` puis l'exécuter.

---

## 2. Services exposés

Quatre services, **tous en HTTPS** avec certificat Let's Encrypt.

| Service | URL externe | Port interne |
|---|---|---|
| Jellyfin | `https://zigoxaz.ddns.net` (443) | conteneur 8096 |
| Navidrome | `https://zigoxaz.ddns.net:4534` | conteneur 4533 |
| DSM + Files | `https://zigoxaz.ddns.net:5001` | natif (2FA actif) |
| Site cuisine | `https://zigoxaz.ddns.net:8080/pwa/` | Web Station |

### Redirections Freebox (les seules actives)

```
443  → 443   NAS_CLGO   (Jellyfin via reverse proxy)
4534 → 4534  NAS_CLGO   (Navidrome via reverse proxy)
5001 → 5001  NAS_CLGO   (DSM + Files)
8080 → 8080  NAS_CLGO   (site cuisine)
```

Pas de DMZ. Le port 445 (SMB) n'est **pas** exposé.

### Services locaux uniquement

| Service | Adresse |
|---|---|
| Transmission | `http://192.168.1.172:9092` (identifiants dans le conteneur) |
| SSH | `192.168.1.172:22` |
| DSM en HTTP | `http://192.168.1.172:5000` |

---

## 3. Certificats

Trois certificats dans DSM :

| Nom | Émetteur | Usage |
|---|---|---|
| `zigoxaz.ddns.net` | Let's Encrypt | **tous les services exposés** |
| `zigoxaz.direct.quickconnect.to` | Let's Encrypt | QuickConnect |
| `synology` | auto-signé | services internes (FTPS, KMIP…) |

Le certificat Let's Encrypt couvre **uniquement** `zigoxaz.ddns.net` — pas les
sous-domaines. Il se renouvelle automatiquement.

**Association** : Panneau de configuration → Sécurité → Certificat → bouton
**Paramètres**. La ligne DSM s'appelle « Paramètres système par défaut ».

⚠️ Accéder à DSM par `https://192.168.1.172:5001` déclenche un avertissement
navigateur (le certificat ne vaut que pour le nom). **Utiliser le nom DNS**, même
en local.

---

## 4. Reverse proxy

Panneau de configuration → **Portail de connexion → Avancé → Proxy inversé**.

Deux règles :

| Nom | Source | Destination |
|---|---|---|
| Jellyfin | HTTPS · zigoxaz.ddns.net · 443 | HTTP · localhost · 8096 |
| Navidrome | HTTPS · zigoxaz.ddns.net · 4534 | HTTP · localhost · 4533 |

**Toujours ajouter l'en-tête WebSocket** (onglet « En-tête personnalisé » →
Créer → WebSocket), sinon la lecture vidéo échoue.

### Contraintes apprises

1. **Ne pas éditer nginx à la main.** Les fichiers de
   `/usr/local/etc/nginx/sites-available/*.w3conf` sont générés par DSM et
   régénérés à chaque mise à jour.
2. **L'API `synowebapi` n'est pas disponible** en ligne de commande sur cette
   version → la configuration passe obligatoirement par l'interface web.
3. **Le port source doit différer du port de destination** si un conteneur occupe
   déjà ce port (DSM refuse sinon).
4. **Les champs grisés du formulaire ne sont que des suggestions** — il faut taper
   chaque valeur, y compris `localhost` et les ports.
5. Après création d'une règle, **associer le certificat** (une nouvelle ligne
   apparaît dans Sécurité → Certificat → Paramètres).

### Web Station (site cuisine)

Le site n'utilise pas le reverse proxy : Web Station sert directement en HTTPS.
**Web Station → Portail Web** → portail `cuisinephp84` → HTTPS coché sur 8080,
HTTP décoché, HSTS activé.

---

## 5. Conteneurs Docker

Binaire : `/var/packages/ContainerManager/target/usr/bin/docker` (pas dans le PATH).

| Conteneur | Image | Ports | Volumes |
|---|---|---|---|
| `jellyfin` | `jellyfin/jellyfin:latest` | 8096 | config, cache, `/films`, `/series` |
| `navidrome-navidrome-1` | `deluan/navidrome:latest` | 4533 | — |
| `transmission` | `lscr.io/linuxserver/transmission` | 9092→9091, 51413 | config, `/downloads` |

### Jellyfin

Monté ainsi :
```
/volume1/docker/jellyfin/config → /config
/volume1/docker/jellyfin/cache  → /cache
/volume1/downloads/FILMS        → /films
/volume1/downloads/SERIES       → /series
```
Médiathèques configurées sur `/films` et `/series`.

### Transmission

Authentification activée via les **variables d'environnement** `USER` et `PASS`
du conteneur — c'est le seul moyen fiable : éditer `settings.json` ne suffit pas,
le script d'init de l'image linuxserver écrase `rpc-authentication-required` au
démarrage.

---

## 6. Sauvegarde

**Hyper Backup** → tâche quotidienne à 03:00.

| Paramètre | Valeur |
|---|---|
| Destination | SSD USB 250 Go, **ext4**, monté sur `/volumeUSB2/usbshare` |
| Répertoire | `NAS_CLGO_1` |
| Dossiers | `homes` (35 Go), `music` (124 Go), `docker` (480 Mo), `cuisine` (24 Mo) |
| Rotation | Smart Recycle, 30 versions |

### Leçons

- **Ne jamais utiliser exFAT** : une clé exFAT retirée sans éjection propre a
  produit une sauvegarde de 60 Go totalement corrompue
  (`Input/output error` à la lecture). ext4 est obligatoire.
- **Éjecter proprement** avant tout retrait (DSM → icône du support → Éjecter).
- Container Manager n'apparaît pas dans la liste des applications sauvegardables
  sur DSM 7.4 — sauvegarder le dossier partagé `docker` suffit (les conteneurs se
  recréent, les données sont préservées).

---

## 7. Sécurité

### En place

- ✅ 2FA (OTP) actif sur DSM pour le groupe administrateurs + Adaptive MFA
- ✅ Blocage automatique : 10 échecs en 5 min → bannissement permanent
  (`autoblock_attempts=10`, `autoblock_attempt_min=5`, `expriedday=0`)
- ✅ Tout le trafic externe en HTTPS
- ✅ SSH fermé depuis Internet
- ✅ VPN (OpenVPN Routé) désactivé — il était actif sur le port 20446, jamais
  utilisé, zéro client

### Audit du 5 septembre 2026 — aucune compromission

Vérifié : pas de clé SSH étrangère, pas de crontab suspecte, pas de mineur, pas
de connexion sortante anormale. `kdevtmpfs` (entre crochets, 0 % CPU) est un
thread noyau légitime, à ne pas confondre avec le malware `kdevtmpfsi`.

**Les sessions SSH `anonymous`** visibles dans `/var/log/auth.log` toutes les 4-5 h
sont **bénignes** : ce compte a `/usr/bin/nologin` comme shell, les sessions durent
2 à 11 secondes et n'exécutent rien. Ce sont des scanners.

### Mot de passe DSM transmis en clair — décision assumée

Le mot de passe DSM a été transmis en clair dans une session Claude (compte
précédent) et figure dans son historique. **Décision du 19/09/2026 : ne pas le
changer**, risque estimé quasi nul.

Justification : aucun acteur ne prospecte les historiques de conversation pour
y trouver des identifiants ; le 2FA est actif sur DSM, donc le mot de passe
seul ne permet pas d'ouvrir une session ; le SSH est fermé depuis Internet ;
ce mot de passe ne protège ni Jellyfin, ni Navidrome, ni le site cuisine.

À réévaluer si l'une de ces conditions change — **en particulier une
désactivation du 2FA**, qui redonnerait au mot de passe son rôle de barrière
unique. Idem si ce mot de passe venait à être réutilisé sur un autre service.

### Piège de diagnostic : le NAT loopback

**Tester un port depuis le réseau local donne des résultats faux.** La Freebox
renvoie directement vers le NAS en court-circuitant les règles de redirection.

Symptômes : un port paraît ouvert alors qu'il n'est pas redirigé ; le 443 alterne
entre succès et échec d'un test à l'autre.

→ **Pour vérifier l'exposition réelle** : consulter la liste des redirections sur
la Freebox, ou tester depuis une connexion 4G. Ne jamais conclure sur un test
local.

Une alerte SMB/445 a été émise à tort sur cette base au début de l'audit.

---

## 8. Application cuisine

### Dépôt et synchronisation

- GitHub : `zigoxaz/cuisine`, branche `master`
- Sur le NAS : `/volume1/cuisine/`
- Le script `/volume1/cuisine/git-pull.sh` synchronise depuis GitHub (cron, toutes
  les minutes) :

```bash
TOKEN=$(cat /volume1/cuisine/.github-token)
git clone "https://zigoxaz:${TOKEN}@github.com/zigoxaz/cuisine.git" /tmp/cuisine_tmp
rsync -av --delete --exclude=garde-manger.json /tmp/cuisine_tmp/pwa/ /volume1/cuisine/pwa/
```

**Le token n'est plus en dur** (corrigé le 9/09/2026) : il est lu depuis
`/volume1/cuisine/.github-token` (chmod 600, propriétaire `http:http`).
Ce fichier n'est ni versionné ni présent dans ce dépôt.

`git-pull.sh` est dans `.gitignore` pour ne pas être écrasé par une synchro.

### garde-manger.json — spécificité importante

`pwa/garde-manger.json` vit **uniquement sur le NAS**, jamais dans git :
- `rsync` l'exclut explicitement
- `save_garde_manger.php` l'écrit (serveur web = utilisateur `http`)
- Modifier `garde-manger.html` ne change **pas** les données : les articles sont
  dans le JSON. Pour ajouter des articles en masse, éditer le JSON sur le NAS
  (script Python via SSH).

### Système de stock (4 états)

```
qte : 0 = IGNORÉ   1 = ÉPUISÉ   2 = PEU   3 = OK
```

- **IGNORÉ** : exclu de la liste « à racheter », mais **présent** dans les courses
  générées par recette (sinon on oublierait de l'acheter).
- Les boutons `−` / `+` font défiler le cycle.
- La liste « à racheter » ne contient que les états 1 et 2.

### Format des ingrédients

`ingrédient / quantité / préparation` — le séparateur `/` est significatif :
c'est le premier segment qui sert au rapprochement avec le garde-manger.

Conventions : `c. à soupe`, `c. à café`, `4 épices` (pas « quatre-épices »),
`sel` et `poivre` sur des lignes séparées.

### Rapprochement ingrédient ↔ stock

`statutIngredient()` retient le **match le plus long** pour éviter que « citron »
l'emporte sur « citron confit » :

```js
const item = inventaire.items
  .filter(i => norm.includes(normaliser(i.nom)))
  .sort((a, b) => b.nom.length - a.nom.length)[0];
```

---

## 9. Secrets — où ils se trouvent

**Rien n'est stocké dans ce dépôt.**

| Secret | Emplacement |
|---|---|
| Mot de passe DSM | à demander à l'utilisateur |
| Token GitHub | `/volume1/cuisine/.github-token` sur le NAS |
| Identifiants Transmission | variables `USER`/`PASS` du conteneur |
| Mot de passe admin PWA | en dur dans `garde-manger.html` et `admin.html` |
| Clé SSH | `C:\Users\CLGO\.ssh\nas_key` |

⚠️ **Ne jamais coller un secret dans la conversation.** Méthode retenue : l'écrire
dans un fichier temporaire hors dépôt, le transférer par SSH, puis le supprimer.

---

## 10. Reste à faire

- [ ] Vérifier que la première sauvegarde s'est terminée (elle était à 149/160 Go)
- [ ] Confirmer que `music` figure bien dans les dossiers sauvegardés

---

## 11. Notes pratiques

**Windows / Git Bash** : les chemins avec espaces doivent être entre guillemets.
Les commits en français passent mal en PowerShell → préférer un message ASCII ou
une variable.

**Filtrer le bruit SSH** : chaque connexion affiche un avertissement
post-quantique. Ajouter en fin de commande :
```bash
| grep -v WARNING | grep -v "session may" | grep -v "may need" | grep -v Password
```

**`docker ps --format`** tronque parfois la sortie et donne l'impression qu'un
conteneur a disparu. Vérifier avec `docker ps -a` sans `--format`.

**Symfonium** : connexion principale `zigoxaz.ddns.net` / `4534` / HTTPS ;
connexion secondaire `192.168.1.172` / `4533` / HTTP (plus direct à la maison).
