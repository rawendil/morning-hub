export type BlockType =
    | 'clickup'
    | 'braindump'
    | 'habits'
    | 'feed'
    | 'notes'
    | 'plan'
    | 'custom'
    | 'google_calendar';

export type ClickUpConnectionFilters = {
    assignees?: number[];
    statuses?: string[];
};

export type ClickUpConnection = {
    id: number;
    name: string;
    workspace_id: string | null;
    default_space_id: string | null;
    default_folder_id: string | null;
    default_list_id: string | null;
    default_list_ids: string[] | null;
    default_filters: ClickUpConnectionFilters | null;
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
    google_calendar_connection_id: number | null;
    google_calendar_connection?: GoogleCalendarConnection;
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

export type ClickUpFolderWithLists = {
    id: string;
    name: string;
    lists: ClickUpList[];
};

export type ClickUpAllListsResponse = {
    folders: ClickUpFolderWithLists[];
    folderless: ClickUpList[];
};

export type ClickUpTask = {
    id: string;
    name: string;
    status: { status: string; color: string };
    priority: { id: string; priority: string; color: string } | null;
    start_date: string | null;
    due_date: string | null;
    date_created: string;
    url: string;
    list: { id: string; name: string };
};

export type ClickUpTaskDetail = ClickUpTask & {
    description: string;
    subtasks?: ClickUpTask[];
    tags: { name: string; tag_bg: string }[];
    list: { id: string; name: string };
};

export type BlockTasksData = {
    tasks: ClickUpTask[];
    error: string | null;
};

export type ClickUpStatus = {
    status: string;
    color: string;
    orderindex: number;
    type: string;
};

export type ClickUpComment = {
    id: string;
    comment_text: string;
    user: {
        id: number;
        username: string;
        initials: string;
        profilePicture: string | null;
    };
    date: string;
};

export type UpdateTaskPayload = {
    status?: string;
    priority?: number | null;
    due_date?: number | null;
    name?: string;
};

export type CreateTaskPayload = {
    list_id: string;
    name: string;
    description?: string;
};

export type TodaysTasksConnectionGroup = {
    connectionId: number;
    connectionName: string;
    tasks: ClickUpTask[];
    statuses: ClickUpStatus[];
    error: string | null;
};

export type BlockTodaysTasksData = {
    groups: TodaysTasksConnectionGroup[];
    error: string | null;
};

export type TodaysTasksConfig = {
    id: number;
    connection_ids: number[] | null;
};

export type FeedItem = {
    title: string;
    link: string;
    source: string;
    published_at: string;
};

export type BlockFeedData = {
    items: FeedItem[];
    error: string | null;
};

export type GoogleCalendarConnection = {
    id: number;
    google_id: string;
    name: string;
    calendar_ids: string[] | null;
    token_expires_at: string;
    created_at: string;
    updated_at: string;
};

export type GoogleCalendarEvent = {
    id: string;
    title: string;
    start: string;
    end: string;
    all_day: boolean;
    location: string | null;
    calendar_color: string;
    calendar_name: string;
};

export type BlockGoogleCalendarData = {
    events: GoogleCalendarEvent[];
    error: string | null;
};

export type TimelineItem =
    | {
          type: 'task';
          sortTime: number;
          connectionId: number;
          task: ClickUpTask;
          statuses: ClickUpStatus[];
      }
    | { type: 'event'; sortTime: number; event: GoogleCalendarEvent };

export type GoogleCalendarListItem = {
    id: string;
    name: string;
    color: string;
};
