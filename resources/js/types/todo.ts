export type Todo = {
    id: number;
    title: string;
    description: string | null;
    is_personal: boolean;
    assigned_to: number | null;
    created_by: number;
    due_date: string | null;
    is_done: boolean;
    completed_at: string | null;
    uuid: string;
    creator?: { id: number; name: string };
    assignee?: { id: number; name: string };
};
