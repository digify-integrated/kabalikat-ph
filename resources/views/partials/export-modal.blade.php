<div id="export-modal" class="modal fade" tabindex="-1" aria-labelledby="export-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Export Data</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body">
                <label class="fs-6 fw-semibold required mb-2" for="export_to">
                    Export To
                </label>
                <div class="row g-9 mb-4" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
                    <div class="col-6">
                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary active d-flex text-start p-6" data-kt-button="true">
                            <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                <input class="form-check-input" type="radio" name="export_to" value="csv" checked="checked" />
                            </span>

                            <span class="ms-5">
                                <span class="fs-4 fw-bold text-gray-800 d-block">CSV</span>
                            </span>
                        </label>
                    </div>

                    <div class="col-6">
                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary  d-flex text-start p-6" data-kt-button="true">
                            <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                <input class="form-check-input" type="radio" name="export_to" value="xlsx" />
                            </span>
                            <span class="ms-5">
                                <span class="fs-4 fw-bold text-gray-800 d-block">Excel</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <select multiple="multiple" size="20" id="table_column" name="table_column[]"></select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="submit-export">Export</button>
            </div>
        </div>
    </div>
</div>