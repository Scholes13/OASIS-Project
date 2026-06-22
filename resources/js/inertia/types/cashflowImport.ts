type ImportPreviewSummary = {
    total_rows: number;
    ready_rows: number;
    new_rows: number;
    update_rows: number;
    no_change_rows: number;
    need_review_rows: number;
    invalid_rows: number;
};

export type ImportPreviewRow = {
    row_number: number;
    status: 'new' | 'update' | 'no_change' | 'need_review' | 'invalid';
    business_unit_code: string;
    department_code: string | null;
    action_code: string | null;
    action_label: string | null;
    flow_type: 'in' | 'out' | null;
    transaction_date: string | null;
    due_date: string | null;
    amount: number | string | null;
    description: string | null;
    keterangan: string | null;
    no_dokumen?: string | null;
    nama_vendor?: string | null;
    notes: string | null;
    is_estimated_date: boolean;
    match: { line_item_id: number } | null;
    changes: Array<{ field: string; old: unknown; new: unknown }>;
    errors: Array<{ field: string; message: string }>;
};

export type ImportPreviewPayload = {
    summary: ImportPreviewSummary;
    rows: ImportPreviewRow[];
};
