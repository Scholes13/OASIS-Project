export interface StockRequestItemFormData {
    id?: number;
    item_name: string;
    item_description?: string;
    quantity: number;
    unit: string;
    image_path?: string;
    image_file?: File;
}

export interface StockRequestFormData {
    business_unit_id: string;
    department_id: string;
    purpose: string;
    request_date: string;
    expected_date?: string;
    items: StockRequestItemFormData[];
    offline_approval_document?: File;
    approval_notes?: string;
}
