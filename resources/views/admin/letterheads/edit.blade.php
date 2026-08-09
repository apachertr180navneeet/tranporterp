@extends('admin.layouts.app')

@section('title', 'Edit Dynamic Letterhead')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row page-titles mx-0 mb-3 align-items-center">
        <div class="col-sm-6 p-md-0">
            <div class="welcome-text">
                <h4 class="text-primary font-weight-bold"><i class="bx bx-edit-alt me-2"></i>Edit Dynamic Letterhead</h4>
                <p class="mb-0">Update letterhead details, dynamic header/footer, and contents.</p>
            </div>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
            <a href="{{ route('admin.letterheads.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bx bx-arrow-back me-1"></i> Back to Letterheads List
            </a>
        </div>
    </div>

    <form action="{{ route('admin.letterheads.update', $letterhead->id) }}" method="POST" id="letterheadEditForm">
        @csrf
        @method('PUT')
        <div class="row">
            <!-- Form Column -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-white mb-0"><i class="bx bx-edit-alt me-2"></i>Edit Letter Details (#{{ $letterhead->letter_no }})</h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Company Selection -->
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Select Company <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                                <option value="">-- Choose Company --</option>
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}" {{ old('company_id', $letterhead->company_id) == $comp->id ? 'selected' : '' }}>
                                        {{ $comp->name }} {{ $comp->gst_number ? "({$comp->gst_number})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Header and Footer will dynamically adapt based on selected company.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Letter Ref No. <span class="text-danger">*</span></label>
                                    <input type="text" name="letter_no" id="letter_no" class="form-control @error('letter_no') is-invalid @enderror" value="{{ old('letter_no', $letterhead->letter_no) }}" required>
                                    @error('letter_no')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Letter Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="letter_date" id="letter_date" class="form-control @error('letter_date') is-invalid @enderror" value="{{ old('letter_date', \Carbon\Carbon::parse($letterhead->letter_date)->format('Y-m-d\TH:i')) }}" required>
                                    @error('letter_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-primary font-weight-bold mb-3"><i class="bx bx-user-pin me-2"></i>Recipient Details</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Recipient / To Name <span class="text-danger">*</span></label>
                                    <input type="text" name="recipient_name" id="recipient_name" class="form-control @error('recipient_name') is-invalid @enderror" value="{{ old('recipient_name', $letterhead->recipient_name) }}" required>
                                    @error('recipient_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Recipient Designation</label>
                                    <input type="text" name="recipient_designation" id="recipient_designation" class="form-control @error('recipient_designation') is-invalid @enderror" value="{{ old('recipient_designation', $letterhead->recipient_designation) }}">
                                    @error('recipient_designation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Recipient Company / Org</label>
                                    <input type="text" name="recipient_company" id="recipient_company" class="form-control @error('recipient_company') is-invalid @enderror" value="{{ old('recipient_company', $letterhead->recipient_company) }}">
                                    @error('recipient_company')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Recipient Mail ID (Email)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                                        <input type="email" name="recipient_email" id="recipient_email" class="form-control @error('recipient_email') is-invalid @enderror" value="{{ old('recipient_email', $letterhead->recipient_email) }}" placeholder="client@example.com">
                                    </div>
                                    @error('recipient_email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Recipient Address</label>
                            <textarea name="recipient_address" id="recipient_address" class="form-control @error('recipient_address') is-invalid @enderror" rows="2">{{ old('recipient_address', $letterhead->recipient_address) }}</textarea>
                            @error('recipient_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>
                        <h6 class="text-primary font-weight-bold mb-3"><i class="bx bx-detail me-2"></i>Letter Content</h6>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', $letterhead->subject) }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Letter Content / Body <span class="text-danger">*</span></label>
                            <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror" rows="8" required>{{ old('content', $letterhead->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Signatory Name</label>
                                    <input type="text" name="signatory_name" id="signatory_name" class="form-control @error('signatory_name') is-invalid @enderror" value="{{ old('signatory_name', $letterhead->signatory_name) }}">
                                    @error('signatory_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Signatory Designation</label>
                                    <input type="text" name="signatory_designation" id="signatory_designation" class="form-control @error('signatory_designation') is-invalid @enderror" value="{{ old('signatory_designation', $letterhead->signatory_designation) }}">
                                    @error('signatory_designation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm w-100">
                                <i class="bx bx-refresh me-1"></i> Update Letterhead Document
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-time Live Preview Column -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 sticky-top" style="top: 20px; z-index: 10;">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-white mb-0"><i class="bx bx-show me-2"></i>Live Dynamic Preview</h5>
                        <span class="badge badge-info">A4 Paper Simulation</span>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <!-- Paper Sheet -->
                        <div id="paper-preview" class="bg-white p-4 shadow-sm border rounded" style="min-height: 650px; font-family: sans-serif; font-size: 13px; position: relative;">
                            
                            <!-- Dynamic Header -->
                            <div id="preview-header" class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div id="preview-logo-container">
                                        <h3 id="preview-company-name" class="text-primary font-weight-bold mb-0">COMPANY NAME</h3>
                                    </div>
                                    <div id="preview-company-details" class="text-right small text-muted" style="line-height: 1.3;">
                                        <div id="preview-company-address">Company Address</div>
                                        <div id="preview-company-phone">Phone: -</div>
                                        <div id="preview-company-email">Email: -</div>
                                        <div id="preview-company-gst" class="font-weight-bold text-dark">GSTIN: -</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ref No & Date -->
                            <div class="d-flex justify-content-between mb-3 font-weight-bold small text-secondary">
                                <div>Ref No: <span id="preview-ref-no" class="text-dark">{{ $letterhead->letter_no }}</span></div>
                                <div>Date & Time: <span id="preview-date" class="text-dark">{{ \Carbon\Carbon::parse($letterhead->letter_date)->format('d M, Y h:i A') }}</span></div>
                            </div>

                            <!-- Recipient Box -->
                            <div class="mb-3 small">
                                <div class="font-weight-bold text-secondary">To,</div>
                                <div id="preview-recipient-name" class="font-weight-bold text-dark" style="font-size: 14px;">{{ $letterhead->recipient_name }}</div>
                                <div id="preview-recipient-designation" class="text-muted">{{ $letterhead->recipient_designation }}</div>
                                <div id="preview-recipient-company" class="font-weight-bold text-dark">{{ $letterhead->recipient_company }}</div>
                                <div id="preview-recipient-address" class="text-dark">{{ $letterhead->recipient_address }}</div>
                                <div id="preview-recipient-email" class="text-info">{{ $letterhead->recipient_email ? 'Email: '.$letterhead->recipient_email : '' }}</div>
                            </div>

                            <!-- Subject Box -->
                            <div id="preview-subject-container" class="bg-light p-2 my-3 border-left border-primary font-weight-bold text-dark small text-uppercase">
                                SUBJECT: <span id="preview-subject">{{ $letterhead->subject }}</span>
                            </div>

                            <!-- Content Body -->
                            <div id="preview-body-content" class="text-justify my-3 small text-dark" style="white-space: pre-wrap; min-height: 200px;">
{{ $letterhead->content }}
                            </div>

                            <!-- Signatory Area -->
                            <div id="preview-signatory-area" class="mt-4 pt-3 text-right float-right" style="width: 220px;">
                                <div class="small">Yours faithfully,</div>
                                <div id="preview-signatory-company" class="font-weight-bold small text-dark">For Company</div>
                                
                                <div id="preview-signature-container" class="my-2 text-right" style="height: 45px;">
                                    <!-- Signature Image placed here -->
                                </div>

                                <div id="preview-signatory-name" class="font-weight-bold text-dark small">{{ $letterhead->signatory_name }}</div>
                                <div id="preview-signatory-designation" class="small text-muted">{{ $letterhead->signatory_designation }}</div>
                                <div id="preview-signature-datetime" class="small text-muted font-italic mt-1" style="font-size: 11px;">
                                    Date & Time: <span id="preview-sig-datetime-val">{{ \Carbon\Carbon::parse($letterhead->letter_date)->format('d M, Y h:i A') }}</span>
                                </div>
                            </div>

                            <div class="clearfix"></div>

                            <!-- Dynamic Footer -->
                            <div id="preview-footer" class="border-top pt-2 mt-4 text-center text-muted small">
                                <div id="preview-footer-contact" class="font-weight-bold text-dark"></div>
                                <div id="preview-footer-disclaimer" class="font-italic text-muted" style="font-size: 10px;"></div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {

    function updateCompanyPreview(companyId) {
        if (!companyId) {
            $('#preview-company-name').text('COMPANY NAME');
            $('#preview-logo-container').html('<h3 id="preview-company-name" class="text-primary font-weight-bold mb-0">COMPANY NAME</h3>');
            $('#preview-company-address').text('Company Address');
            $('#preview-company-phone').text('Phone: -');
            $('#preview-company-email').text('Email: -');
            $('#preview-company-gst').text('GSTIN: -');
            $('#preview-signatory-company').text('For Company');
            $('#preview-signature-container').html('');
            $('#preview-footer-contact').text('');
            $('#preview-footer-disclaimer').text('');
            return;
        }

        var url = "{{ route('admin.companies.details', ['company' => ':id']) }}".replace(':id', companyId);

        $.ajax({
            url: url,
            type: "GET",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    var comp = response.company;
                    
                    if (comp.logo_url) {
                        $('#preview-logo-container').html('<img src="' + comp.logo_url + '" style="max-height: 55px; max-width: 170px;" alt="Logo">');
                    } else {
                        $('#preview-logo-container').html('<h3 class="text-primary font-weight-bold mb-0">' + comp.name + '</h3>');
                    }

                    var addr = comp.address ? comp.address : '';
                    if (comp.state) addr += (addr ? ', ' : '') + comp.state;
                    $('#preview-company-address').text(addr);
                    $('#preview-company-phone').text(comp.phone ? 'Phone: ' + comp.phone : '');
                    $('#preview-company-email').text(comp.email ? 'Email: ' + comp.email : '');
                    
                    var gstPan = '';
                    if (comp.gst_number) gstPan += 'GSTIN: ' + comp.gst_number + ' ';
                    if (comp.pan_number) gstPan += '| PAN: ' + comp.pan_number;
                    $('#preview-company-gst').text(gstPan);

                    $('#preview-signatory-company').text('For ' + comp.name);

                    if (comp.digital_signature_url) {
                        $('#preview-signature-container').html('<img src="' + comp.digital_signature_url + '" style="max-height: 40px; max-width: 140px;" alt="Signature">');
                    } else {
                        $('#preview-signature-container').html('');
                    }

                    var footerContact = comp.name;
                    if (comp.address) footerContact += ' | ' + comp.address;
                    if (comp.phone) footerContact += ' | Tel: ' + comp.phone;
                    $('#preview-footer-contact').text(footerContact);
                    $('#preview-footer-disclaimer').text(comp.disclaimer ? comp.disclaimer : '');
                }
            }
        });
    }

    // Trigger on Company Change
    $(document).on('change', '#company_id', function() {
        updateCompanyPreview($(this).val());
    });

    if ($('#company_id').val()) {
        updateCompanyPreview($('#company_id').val());
    }

    $('#letter_no').on('input change', function() {
        $('#preview-ref-no').text($(this).val() || '{{ $letterhead->letter_no }}');
    });

    $('#letter_date').on('change input', function() {
        var d = $(this).val();
        if (d) {
            var dateObj = new Date(d);
            if (!isNaN(dateObj.getTime())) {
                var dateStr = dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                var timeStr = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                var fullFormatted = dateStr + ' ' + timeStr;
                $('#preview-date').text(fullFormatted);
                $('#preview-sig-datetime-val').text(fullFormatted);
            }
        }
    });

    $('#recipient_name').on('input change', function() {
        $('#preview-recipient-name').text($(this).val() || '[Recipient Name]');
    });

    $('#recipient_designation').on('input change', function() {
        $('#preview-recipient-designation').text($(this).val());
    });

    $('#recipient_company').on('input change', function() {
        $('#preview-recipient-company').text($(this).val());
    });

    $('#recipient_address').on('input change', function() {
        $('#preview-recipient-address').text($(this).val());
    });

    $('#recipient_email').on('input change', function() {
        var email = $(this).val();
        $('#preview-recipient-email').text(email ? 'Email: ' + email : '');
    });

    $('#subject').on('input change', function() {
        $('#preview-subject').text($(this).val() || '[LETTER SUBJECT]');
    });

    $('#content').on('input change', function() {
        $('#preview-body-content').text($(this).val());
    });

    $('#signatory_name').on('input change', function() {
        $('#preview-signatory-name').text($(this).val());
    });

    $('#signatory_designation').on('input change', function() {
        $('#preview-signatory-designation').text($(this).val());
    });
});
</script>
@endsection
