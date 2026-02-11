export type Birthday = {
    id: number;
    name: string;
    date: string;
    age: number;
    added_by: number;
    uuid: string;
    created_at: string;
    updated_at: string;
    added_by_user?: { id: number; name: string };
};

export type TodayBirthday = {
    id: number;
    name: string;
    age: number;
};
