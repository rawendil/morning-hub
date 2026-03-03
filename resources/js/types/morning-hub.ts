export type BlockType =
    | 'clickup'
    | 'braindump'
    | 'notes'
    | 'plan'
    | 'custom';

export type ClickUpConnection = {
    id: number;
    name: string;
    workspace_id: string | null;
    default_space_id: string | null;
    default_folder_id: string | null;
    default_list_id: string | null;
    default_filters: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type RoutineBlock = {
    id: number;
    type: BlockType;
    name: string;
    sort_order: number;
    timer_minutes: number | null;
    clickup_connection_id: number | null;
    clickup_connection?: ClickUpConnection;
    config: Record<string, unknown> | null;
};

export type ClickUpWorkspace = {
    id: string;
    name: string;
};

export type ClickUpSpace = {
    id: string;
    name: string;
};

export type ClickUpFolder = {
    id: string;
    name: string;
};

export type ClickUpList = {
    id: string;
    name: string;
};
