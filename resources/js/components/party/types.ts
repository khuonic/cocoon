export type PartySharedProps = {
    party: {
        title: string;
        isClosed: boolean;
        player: { pseudo: string } | null;
    };
};

export type QuizStep = {
    key: string;
    chapter: 'enfance' | 'ado' | 'adulte';
    context: string;
    scene: string;
    question: string;
    choices: Record<string, string>;
};

export type VariantAxis = {
    key: string;
    label: string;
    options: Record<string, string>;
};

export type GameMeta = {
    slug: string;
    title: string;
    description: string;
    ranking: 'points' | 'speed';
    max_score: number;
    variant_axes: VariantAxis[];
    default_variant: string | null;
};

export type AnswerResult = {
    correct: boolean;
    answer: string;
    reveal: string;
    finished: boolean;
    score: number;
};
