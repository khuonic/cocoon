export type NoteColor = 'default' | 'yellow' | 'green' | 'blue' | 'pink' | 'purple';

export type Note = {
    id: number;
    title: string;
    content: string;
    is_pinned: boolean;
    color: NoteColor | null;
    created_by: number;
    uuid: string;
    created_at: string;
    updated_at: string;
    creator?: { id: number; name: string };
};