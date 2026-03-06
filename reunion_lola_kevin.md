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

## Nouvelles remarques
- calendrier
  - pouvoir swiper pour les mois
  - affichage avec cases pour jour
  - inspiré de google agenda "mois"
  - cases plus haute pour remplir la page
    - Modification evenement non fonctionnel (erreur 500)
  - manque de tests feature sur le calendrier
  - pouvoir saisir les anniversaires (sur calendrier/)
  - au click sur le FAB +
    - menu contextuel avec choix de categorie
    - permet d'adapter la modal de saisie, notamment anniversaire, retirer le choix de catégorie dans la modal
    - Propose moi une solution pour que le clavier visuel mobile soit dismiss facilement dans la modal
    - les checkbox a transformer en toggle
    - déplacer la sauvegarde peut etre en haut a droite ()

- notes
  - affiche 8 lignes max sur ecran recap
  - saisie de todolist
  - ne pas avoir de bouton ajouter
  - mais plutot a chaque ligne une case de todo a chaque fois qu'on fait entrer (s'inspier liste google keep ou alors en appuyant sur entrée que ca retourne automatiquement ensuite dans l'input de saisie)
  - pouvoir drag and drop dans la liste pour reordonner les élements
  - BUG: note pas scrollable a l'interieur
- Liste de course
  - afficher les élements en card carrées plutot que en ligne
  - bug sur retour arriere
  - ajouter couleur de fond sur les card (differentes entre cochées pas cochées)
  - Les boutons d'enregistrement sont soit sous le clavier soit collés au clavier (peut etre mettre la modal en top ?)
- Budget
  - "vous etes quittes" est moche
  - Déplacer description en haut
  - mettre date a coté du montant sur meme ligne en réduisant la hauteur de la ligne de montant
  - sur historique
    - ajouter meme selecteur année/mois que dans le calendrier 

- Bug dans parametre enregitrer
  - POST METHOD not supported 
- Remarque globale concernant les boutons d'enregistrement sur les differentes pages qui ressemblent trop aux autres tags filtres bouttons
  - peut etre mettre les bouton d'enregistrement en haut de la page (notamment dans les modal ou meme tout) ou avec plus de hauteur, pas forcément toute la longueur, fais moi des propositions
  - Mettre les modal en top

## remarques bis
- calendrier evenement plusieurs jours
  - bizarre que ce soit en absolute on ne comprend pas
  - affichage anniversaire dans calendrier calcul mal l'age
  - debuger les toggle partout
- Shopping
  - retirer "annuler de la modal de modification"
  - decaler fab micro en bas a droite
- Retirer "annuler" de toutes les modals
- anniversaires
  - agrandir la taille des card dans le listing
  - ajout bouton de tri au dessus par date / nom
  - tri date doit etre fait sans prise en compte de l'année
