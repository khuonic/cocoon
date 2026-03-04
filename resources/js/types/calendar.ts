export type CalendarEvent = {
    id: number;
    uuid: string;
    title: string;
    description: string | null;
    location: string | null;
    category: string;
    category_color: string;
    category_label: string;
    starts_at: string;
    ends_at: string | null;
    all_day: boolean;
    is_personal: boolean;
    user_id: number | null;
    reminder_before: number | null;
    user?: { id: number; name: string };
    created_at: string;
    updated_at: string;
};

export type CalendarBirthday = {
    id: number;
    name: string;
    date: string;
    age: number;
    day: number;
    reminder_days_before: number | null;
};

export type CalendarUser = {
    id: number;
    name: string;
};
