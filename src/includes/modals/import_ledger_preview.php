<div class="modal fade" id="importLedgerPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Imported Ledger</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="card mb-3 bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Borrower Details</h6>
                        <div class="row">
                            <div class="col-md-3"><strong>Name:</strong> <span id="previewName"></span></div>
                            <div class="col-md-3"><strong>ID Number:</strong> <span id="previewId"></span></div>
                            <div class="col-md-3"><strong>Loan Amount:</strong> <span id="previewAmount"></span></div>
                            <div class="col-md-3"><strong>Terms:</strong> <span id="previewTerms"></span></div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered table-sm">
                        <thead class="table-dark" style="position: sticky; top: 0;">
                            <tr>
                                <th>No</th>
                                <th>Date</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Total Payment</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="previewLedgerTableBody">
                            </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btnConfirmLedgerSave">Confirm and Save</button>
            </div>
        </div>
    </div>
</div>