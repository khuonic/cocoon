export type MealTag = 'rapide' | 'vege' | 'comfort' | 'leger' | 'gourmand';

export type TagOption = {
    value: MealTag;
    label: string;
};

export type MealIdea = {
    id: number;
    name: string;
    description: string | null;
    url: string | null;
    tags: MealTag[] | null;
    created_by: number;
    uuid: string;
    creator?: { id: number; name: string };
};

export type RecipeIngredient = {
    id: number;
    recipe_id: number;
    name: string;
    quantity: string | null;
    unit: string | null;
    sort_order: number;
};

export type RecipeStep = {
    id: number;
    recipe_id: number;
    instruction: string;
    sort_order: number;
};

export type Recipe = {
    id: number;
    title: string;
    description: string | null;
    url: string | null;
    prep_time: number | null;
    cook_time: number | null;
    servings: number | null;
    tags: MealTag[] | null;
    created_by: number;
    uuid: string;
    creator?: { id: number; name: string };
    ingredients?: RecipeIngredient[];
    steps?: RecipeStep[];
};
