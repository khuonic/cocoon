export type Todo = {
    id: number;
    uuid: string;
    todo_list_id: number;
    title: string;
    is_done: boolean;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
};

export type TodoList = {
    id: number;
    uuid: string;
    title: string;
    is_personal: boolean;
    user_id: number | null;
    todos?: Todo[];
    created_at: string;
    updated_at: string;
};
