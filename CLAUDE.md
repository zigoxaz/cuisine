# Instructions — Recettes de cuisine

## Contexte
Application web de recettes hébergée sur un NAS Synology, synchronisée depuis GitHub (repo : zigoxaz/cuisine).
- Fichier des recettes : `pwa/recettes.json`
- Images : `pwa/images/`
- Config (thème, nav) : `pwa/config.json`
- Admin : `pwa/admin.html` (mot de passe : clgo)
- Après chaque modification, committer et pusher sur GitHub — le NAS se met à jour automatiquement en moins d'une minute.
- URL de l'appli : zigoxaz.ddns.net:8080/pwa/

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
- Chercher une image JPG ou WebP via WebSearch + WebFetch
- Télécharger avec curl dans `pwa/images/`
- Nom de fichier : `nom-de-la-recette.jpg` (minuscules, tirets)
- Vérifier que c'est bien un JPEG ou PNG (commande `file`)

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
```bash
git add pwa/recettes.json pwa/images/nom.jpg
git commit -m "Ajout : Titre de la recette"
git push origin master
```
