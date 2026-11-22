<?php

namespace App\Party\Games;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Contracts\QuizGame;
use App\Party\Ranking;

/**
 * « La voie de la sagesse » : retracer la vie d'Antoine, étape par étape.
 *
 * Pour modifier le contenu : éditer steps(). `scene` correspond au décor côté front
 * (resources/js/components/party/scenes), `answer` à la clé du bon choix.
 */
class VoieDeLaSagesse extends BaseGame implements QuizGame
{
    public const SLUG = 'voie-de-la-sagesse';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function title(): string
    {
        return 'La voie de la sagesse';
    }

    public function description(): string
    {
        return "Retrace la vie d'Antoine : 6 situations, un seul bon chemin.";
    }

    public function component(): string
    {
        return 'Party/Games/VoieDeLaSagesse';
    }

    public function ranking(): Ranking
    {
        return Ranking::Points;
    }

    public function maxScore(?string $variant = null): int
    {
        return count($this->steps());
    }

    /**
     * Étapes envoyées au navigateur : jamais la bonne réponse ni la révélation.
     */
    public function pageProps(Player $player): array
    {
        return [
            'steps' => collect($this->steps())
                ->map(fn (array $step) => collect($step)->except(['answer', 'reveal'])->all())
                ->values(),
        ];
    }

    public function verdictFor(GameRun $run): string
    {
        $score = $run->score;

        return match (true) {
            $score >= $this->maxScore() => 'Sans faute. Tu connais Antoine mieux que sa propre mère.',
            $score >= 4 => 'Pas mal du tout ! Tu as dû partager quelques verres avec lui.',
            $score >= 2 => 'Tu as croisé Antoine… de loin.',
            default => "Tu es sûr d'être invité à la bonne fête ?",
        };
    }

    public function steps(): array
    {
        return [
            [
                'key' => 'chambre',
                'chapter' => 'enfance',
                'context' => 'La chambre',
                'scene' => 'chambre',
                'question' => 'Vous avez plein de crottes de nez mais aucun mouchoir à portée de main, que faites-vous ?',
                'choices' => [
                    'a' => 'Vous collez vos crottes de nez derrière le lit',
                    'b' => 'Vous les jetez par la fenêtre',
                    'c' => 'Vous les mangez',
                ],
                'answer' => 'a',
                'reveal' => 'Derrière le lit, évidemment. La cachette idéale.',
            ],
            [
                'key' => 'bsr',
                'chapter' => 'ado',
                'context' => 'Le BSR',
                'scene' => 'bsr',
                'question' => 'Vous passez votre BSR, un mauvais virage et vous vous blessez au genou : que faites-vous ?',
                'choices' => [
                    'a' => 'Vous appelez votre mère',
                    'b' => "Vous pleurez et demandez d'appeler les urgences",
                    'c' => "Vous continuez l'examen, l'échec n'est pas une option",
                ],
                'answer' => 'c',
                'reveal' => "Genou amoché, il termine l'examen. L'échec n'est pas une option.",
            ],
            [
                'key' => 'cap-caval',
                'chapter' => 'adulte',
                'context' => 'Le Cap Caval',
                'scene' => 'cap-caval',
                'question' => 'Vous essayez de rejoindre le bar depuis la piste, plusieurs options s\'offrent à vous :',
                'choices' => [
                    'a' => 'Vous rampez au sol tel Rambo',
                    'b' => 'Vous rampez au sol tel un ver de terre',
                    'c' => 'Vous rampez au sol tel un zombie',
                ],
                'answer' => 'a',
                'reveal' => 'Façon Rambo, à travers le sable du Cap Caval, jusqu\'au bar.',
            ],
            [
                'key' => 'bresil',
                'chapter' => 'adulte',
                'context' => 'Grand reporter au Brésil',
                'scene' => 'bresil',
                'question' => 'Vous dormez chez une inconnue et une grosse envie finit mal aux toilettes, vous les bouchez : que faites-vous ?',
                'choices' => [
                    'a' => 'Vous avouez directement à la propriétaire',
                    'b' => 'Vous récupérez le caca avec la ventouse et le disposez sur du PQ, puis dans votre poche',
                    'c' => 'Vous vous servez de la ventouse correctement et débouchez les toilettes',
                ],
                'answer' => 'b',
                'reveal' => 'La ventouse, le PQ… et direction la poche.',
            ],
            [
                'key' => 'falafel',
                'chapter' => 'adulte',
                'context' => 'Bordeaux, nouveau job au resto de falafels',
                'scene' => 'falafel',
                'question' => 'Votre patron vous tend un falafel, quelle est votre réaction ?',
                'choices' => [
                    'a' => 'Vous gobez le falafel et ses doigts avec.',
                    'b' => 'Vous déclinez, les pois chiches ça fait péter',
                    'c' => 'Vous le saisissez, l\'emballez dans du PQ et le mettez dans votre poche',
                ],
                'answer' => 'a',
                'reveal' => 'Le falafel… et les doigts du patron avec.',
            ],
            [
                'key' => 'mariage',
                'chapter' => 'adulte',
                'context' => 'Le mariage des patrons, dans le Finistère',
                'scene' => 'mariage',
                'question' => 'Ivre, vous dansez collé-serré avec la mère de la mariée avant de vous faire virer par le marié (votre patron), quelle est votre réaction ?',
                'choices' => [
                    'a' => 'Vous appelez Marie-Framboise pour vous ramener',
                    'b' => 'Vous prenez la voiture de vos parents, défoncez le parc automobile et niez tout le lendemain',
                    'c' => 'Vous dormez avec la belle-mère, soirée réussie',
                ],
                'answer' => 'b',
                'reveal' => 'La voiture des parents, le parking défoncé… et le lendemain : « C\'était pas moi. »',
            ],
        ];
    }
}
