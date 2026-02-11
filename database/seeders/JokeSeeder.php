<?php

namespace Database\Seeders;

use App\Models\Joke;
use Illuminate\Database\Seeder;

class JokeSeeder extends Seeder
{
    public function run(): void
    {
        if (Joke::query()->exists()) {
            return;
        }

        $jokes = [
            'Pourquoi les plongeurs plongent-ils toujours en arrière et jamais en avant ? Parce que sinon ils tomberaient dans le bateau.',
            "C'est un mec qui rentre dans un café... et plouf !",
            "Qu'est-ce qu'un crocodile qui surveille la cour de récré ? Un surveillant général.",
            'Quel est le comble pour un électricien ? De ne pas être au courant.',
            'Pourquoi les vaches ferment-elles les yeux pendant la traite ? Pour faire du lait concentré.',
            "Que dit une imprimante dans l'eau ? J'ai papier !",
            'Comment appelle-t-on un chat tombé dans un pot de peinture le jour de Noël ? Un chat peint de Noël.',
            "Pourquoi est-ce que les pêcheurs ne sont jamais gros ? Parce qu'ils surveillent leur ligne.",
            'Quel est le sport le plus fruité ? La boxe, parce que quand tu prends un coup, tu tombes dans les pommes.',
            "Qu'est-ce qu'un canif ? Un petit fien.",
            'Que fait un geek quand il descend du bus ? Il libère la mémoire.',
            "Pourquoi les fantômes sont-ils de mauvais menteurs ? Parce qu'on voit à travers eux.",
            "C'est l'histoire d'un pingouin qui respire par les fesses. Un jour il s'assoit et il meurt.",
            'Que dit un oignon quand il se cogne ? Aïe !',
            'Quel est le fruit le plus guerrier ? La groseille à macro.',
            "Pourquoi les moutons n'arrivent pas à sortir de leur champ ? Parce qu'ils font toujours demi-tour.",
            "Qu'est-ce qu'un cadeau qui arrive en retard ? Un pré-sent.",
            'Quel est le comble pour un jardinier ? Raconter des salades en ramenant sa fraise.',
            "Pourquoi les Bretons ne jouent-ils pas aux cartes ? Parce qu'ils ont peur de la dame de pique.",
            "Qu'est-ce qu'une fraise sur un cheval ? Un jockey fruit.",
            'Quelle est la femme la plus proche des étoiles ? Madame Astro.',
            "Pourquoi les coiffeurs ne se battent-ils jamais ? Parce qu'ils ne veulent pas se crêper le chignon.",
            "Qu'est-ce qu'un poisson sans yeux ? Un psson.",
            "Pourquoi les maths sont-elles tristes ? Parce qu'elles ont trop de problèmes.",
            'Comment appelle-t-on un boomerang qui ne revient pas ? Un bout de bois.',
            "Qu'est-ce qu'un squelette dans un placard ? Un mec qui a gagné à cache-cache.",
            "Pourquoi les chats n'aiment-ils pas les ordinateurs ? Parce qu'ils ont peur de la souris.",
            'Que fait une vache avec un marteau ? Vache-ment de bruit.',
            "Pourquoi les escargots n'aiment pas jouer au foot ? Parce qu'ils se font toujours marcher dessus.",
            "Un homme dit à sa femme : « Chérie, est-ce que je suis le seul homme que tu aies aimé ? ». Elle répond : « Oui, les autres c'était des 7 et des 8. »",
            "Qu'est-ce qui est petit, carré et jaune ? Un petit carré jaune.",
            "Pourquoi les oiseaux ne portent-ils pas de lunettes ? Parce qu'ils ont des lentilles.",
            "Qu'est-ce qu'un Américain qui perd son gras ? Un slim Américain.",
            "Comment s'appelle un chat qui fait la loi ? Un chat-rif.",
            "Pourquoi les vampires sont-ils toujours malades ? Parce qu'ils sont tout le temps à courant d'air.",
            "Quel animal a le plus de mémoire ? L'éléphant… parce qu'il n'oublie jamais.",
            "Qu'est-ce qu'un cochon qui rit ? Du jambon fumé.",
            "Que dit un informaticien quand il s'ennuie ? Je me fichier.",
            "Quel est le comble pour un prof d'histoire ? Ne pas avoir de dates.",
            "Pourquoi le livre de maths est-il triste ? Parce qu'il a trop de problèmes.",
            "Qu'est-ce qu'un Schtroumpf dans un micro-ondes ? Un Schtroumpf chaud.",
            "Quelle est la différence entre un crocodile et un alligator ? C'est caïman la même chose.",
            "Pourquoi les abeilles ont-elles les cheveux collants ? Parce qu'elles utilisent des peignes à miel.",
            "Que se passe-t-il quand deux poissons s'énervent ? Le ton monte.",
            "Qu'est-ce qu'une chauve-souris avec une perruque ? Une souris.",
            "Pourquoi les étoiles ne font-elles pas de bruit ? Parce qu'elles ont des années-lumière d'avance.",
            "Qu'est-ce qu'un chien sans pattes ? On ne sait pas, il n'est jamais venu quand on l'a appelé.",
            'Que dit un escargot quand il croise une limace ? Oh la belle décapotable !',
            "Quel est le légume le plus drôle ? Le chou-fleur, parce qu'il fait rire tout le jardin.",
            "Pourquoi est-ce que les girafes n'existent pas ? Parce que c'est trop long à expliquer.",
        ];

        $now = now();

        Joke::query()->insert(
            collect($jokes)->map(fn (string $joke) => [
                'content' => $joke,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }
}
