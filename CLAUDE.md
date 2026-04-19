# Instructions — Recettes de cuisine

## Contexte
Application web de recettes hébergée sur un NAS Synology, synchronisée depuis GitHub (repo : zigoxaz/cuisine).
- Fichier des recettes : `pwa/recettes.json`
- Images : `pwa/images/`
- Config (thème, nav) : `pwa/config.json`
- Admin : `pwa/admin.html` (mot de passe : clgo)
- Le NAS se met à jour automatiquement depuis `master` en moins d'une minute.
- URL de l'appli : zigoxaz.ddns.net:8080/pwa/

## Git — Process obligatoire
L'environnement Claude Code web crée automatiquement une branche de session (ex: `claude/pwa-website-review-XXXX`).
Après chaque modification, toujours merger sur `master` :
```bash
git add ...
git commit -m "..."
git push origin <branche-session>
git checkout master
git pull origin master
git merge <branche-session>
git push origin master
```
La branche de session peut être supprimée manuellement sur GitHub en fin de session.

## Ajouter une recette

### 1. Rédaction
- **Titre** : court et direct
- **Catégorie** : Poisson, Porc, Poulet, Bœuf, Végétarien, Pâtes, Dessert, Entrée, Accompagnement
- **Ingrédients** : quantité + nom + préparation courte. Pas de phrases.
- **Étapes** : phrases courtes, impératives. Supprimer tout blabla.
- **Tags** : ingrédients principaux en minuscules, séparés par des virgules.
- **Abréviations** : c. à soupe, c. à café, min, g, ml

### 2. Ordre des ingrédients
Viandes → légumes/féculents → herbes fraîches → épices → condiments → produits laitiers → huile → sel/poivre

### 3. Image
- Demander d'abord à l'utilisateur s'il est sur **téléphone** ou **PC**.
- Chercher une image JPG ou WebP via WebSearch + WebFetch.
- Télécharger avec curl dans `pwa/images/`.
- Nom de fichier : `nom-de-la-recette.jpg` (minuscules, tirets).
- Vérifier que c'est bien un JPEG ou PNG (commande `file`).
- **Si sur téléphone et image introuvable** : sauter l'image, ajouter la recette sans champ `image`. L'utilisateur pourra l'ajouter plus tard depuis son PC.
- **Si sur PC et image introuvable** : demander à l'utilisateur de fournir une photo.

### 4. JSON
Ajouter la recette à la fin du tableau `recettes` dans `pwa/recettes.json` avec le prochain id disponible :
```json
{
  "id": X,
  "titre": "...",
  "categorie": "...",
  "image": "images/nom.jpg",
  "ingredients": ["...", "..."],
  "etapes": ["...", "..."],
  "tags": ["...", "..."]
}
```

### 5. Commit et push
Suivre le process Git ci-dessus pour que les modifications soient visibles sur le site.
