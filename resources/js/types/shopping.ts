export type ShoppingItemCategory = 'fruits_legumes' | 'frais' | 'epicerie' | 'boissons' | 'hygiene' | 'maison' | 'autre';

export type ShoppingItem = {
    id: number;
    shopping_list_id: number;
    name: string;
    category: ShoppingItemCategory;
    is_checked: boolean;
    added_by: number;
    uuid: string;
    created_at: string;
    updated_at: string;
};

export type ShoppingList = {
    id: number;
    name: string;
    is_template: boolean;
    is_active: boolean;
    uuid: string;
    created_at: string;
    updated_at: string;
    items?: ShoppingItem[];
    unchecked_items_count?: number;
    checked_items_count?: number;
    items_count?: number;
};

export type CategoryOption = {
    value: ShoppingItemCategory;
    label: string;
};
