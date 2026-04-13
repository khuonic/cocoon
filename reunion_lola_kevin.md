## Notes

---


### A changer

- le FAB => le surelever un peu de la BottomNav

- Anniversaires => "Calendrier" => a mettre en deuxieme avant 
  - A mettre dans les liens principaux
  - Meme principe que google calendar 
    - Vue mensuelle avec jour cliquable en modal
    - affichage mois en cours avec pastille jour
    - afficher par défaut commun + perso + autre
    - pouvoir décocher autre (kévin peut décocher lola)
      - vue journaliere
      - ajouter lettre prénom sur envement perso
    - evenement
      - catégories d'évènements (Congés, Pro, Loisir, Rdv) avec couleur associée
      - Date et heure possible
      - Lieu
      - pouvoir saisir un rappel (j -1, jour j, si heure saisie => pouvoir saisir rappel en min -)
      - affichage dans page dédiée
      - par défaut => saisie commune
      - pouvoir saisir evenement perso
    - anniversaires
      - nom persone + date (comme actuellement) + rappel possible + notification push le jour j
      - toujours communs
- Merger taches et todo => "Notes"
    - deux onglets (todo ou note)
      - todo:
        -  similaire à liste de course (création d'une todoList avec titre  + choix liste personnelle)
        - dans la todolist => il faut que ce soit rapide de créer une tache (similaire à ajout produit course avec seulement un input texte)
      - notes
        - (similaire google note, garder saisie note actuelle mais dans page complete avec saisie texte en grand)
      - il faut pouvoir détecter si un lien est dans le texte pour pouvoir cliquer
- budget
  - catégories :
    - loyer => charges
    - santé => cadeaux (nouvelle icone)
  - historique
    - vue mensuelle par défaut (avec filtre pour choisir entre annuel, mensuel ou total
  - vous etes quittes (il manque l'acces a l'historique)
- courses
  - arriver par défaut sur derniere liste consultée
  - Saisie :
    - optionnel méga trop bien => développer un text to speech pour saisie automatique par vocal
    - affichage à modifier
      - avoir des cards et pas lignes, pour cocher/décocher => click sur la card, pour supprimer ou éditer rester appuyer
      - Enlever la saisie de quantité
      - pouvoir collapse les catégories
      - retirer la notion de favori 
- Recettes
  - virer idées et garder recettes
  - dans recettes
    - avoir choix entre lien vers site avec preview de la recette (saisie image ou bien preview grace à l'url) ou saisie de recette
    - pouvoir ajouter image
    - est ce qu'a partir de plusieurs images tu pourrais faire un seeder pour remplir les recettes ? on aurait déjà une bonne base
    - pouvoir cliquer sur lien recette si inséré
- ordre menu
    - calendrier
    - budget
    - notes
    - basculer courses dans "plus"
- Les blagues ne s'affichent pas
- Le mot mignon ne s'affiche pas 
### A ajouter

- Accueil
  - réccupérer les évenements/anniversaires du jour
  - si > 5 mettre un oeil ou plus qui renvoie dans la section calendrier
- Logo écran connexion

### a supprimer

- notes ancienne version
- anniversaires
- bookmarks

## Ne pas oublier
- Modifier tous les tests 
- Modifier le .ai/guidelines/contexte.md 
- modifier la sync 
- modifier chaque PHASE*.md si nécessaire
- modifier COCON_PLAN.md si nécessaire
- rédige un plan des que nécessaore/usa



- regarder C:\Users\kevin\Herd\cocoon\PLAN_BIOMETRIC_FIX.md  ou alors https://nativephp.com/plugins/nativephp/mobile-biometrics  pour la biométrie car toujours non fonctionnelle
    - modifier saisie courses
      - enlever micro
      - avoir une auto complétion sur les produits par rapport à ceux saisis
        - pouvoir choisir un produit "coché" et que ca le remette automatiquement dans la liste (il faudrait un visuel dans l'autocomplétion qui permet de savoir si un produit est à cocher ou non)
      - plutot une saisie en modal, sans que la modal ne se ferme apres un ajout
      - préparer un seeder pour les courses pour une liste de course "Course appartement"
      - spliter la catégorie épicerie en "épicerie salée" et "épicerie sucrée"

  - calendrier:
    - corriger affichage evenement sur plusieurs jours
      - toujours afficher en premier dans un jour les evenements qui sont sur plusieurs jours, il ne faut pas que la ligne d'un évenement puisse être "cassée" 
  - input file camera non fonctionnelle (recettes)(voir peut etre https://nativephp.com/plugins/nativephp/mobile-camera)


Probleme sur syncro

Pas de récupération de la liste de course une fois connecté
Probleme de check via timezone ??

Post mise en prod

Probleme dates anniversaires, stock j-1 par rapport a ce qu'on envoie
Ensuite affiche j-2

Probleme sweet messages recu de l'autre
bien stocké en bdd mais affiche toujours le premier stocké en base (pas le plus récent)

Calendrier/evenements:
- Apres un ajout/remplissage/modification, les input ne sont pas vidés
- date de fin, il faut partir de la date de début minimum
- Probleme d'enregistrement sur les dates (ca stocke en base h-2 donc souvent j-1)
- et du coup probleme en front a l'affichage des heures (ex, j'ai mis evenement le 12 à 10h, ca stock 8h en base, et quand je retourne sur l'évenement, ca affiche le 12 à 4h)
- filtre sur kévin / lola, possible de mettre par défaut sur la page le filtre kévin pour kévin, et le filtre lola pour lola ?

Budget :
- pareil probleme de date, saisie le 12, ca stocke le 11, et ca affiche le 10 en front

Todos:
- sync des todos list fonctionnelles mais pas des todo a l'intérieur
- SQLSTATE[23503]: Foreign key violation: 7 ERROR: insert or update on table "todos" violates foreign key constraint "todos_todo_list_id_foreign" DETAIL: Key (todo_list_id)=(1) is not present in table "todo_lists". (Connection: pgsql, Host: ep-calm-dust-a2pexugv.aws-eu-central-1.pg.laravel.cloud, Port: 5432, Database: main, SQL: insert into "todos" ("title", "uuid", "todo_list_id", "updated_at", "created_at") values (Assurance habitation + juridique + auto, 68c107c7-17aa-44cb-88ff-beeefaad4542, 1, 2026-04-12 20:50:47, 2026-04-12 20:50:47) returning "id")
- la todo en local a été créée avec l'id 1, cependant en bdd ca a inséré a 2 car dans le passé j'avais déjà fait un test et supprimé l'id , que peut on faire pour ça ?
- je vois la "liste personnelle" de Lola, ce n'est pas normal


A voir:
- reminders: pour les anniversaires, il faut les remettre tous les ans
- est ce que le scheduler est mis à jour si on modifie l'heure/date de l'event pourlequel on l'avait set ?
