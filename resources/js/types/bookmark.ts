export type BookmarkCategory = 'resto' | 'voyage' | 'shopping' | 'loisirs' | 'maison' | 'autre';

export type BookmarkCategoryOption = {
    value: BookmarkCategory;
    label: string;
};

export type Bookmark = {
    id: number;
    url: string;
    title: string;
    description: string | null;
    category: BookmarkCategory | null;
    is_favorite: boolean;
    show_on_dashboard: boolean;
    added_by: number;
    uuid: string;
    created_at: string;
    updated_at: string;
    added_by_user?: { id: number; name: string };
};
