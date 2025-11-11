<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                @lang('lang_v1.upload_documents') - @lang('purchase.ref_no'): {{ $payroll->ref_no }}
            </h4>
        </div>
        <div class="modal-body">
            @if($can_upload)
                <div class="form-group">
                    <label for="fileupload">
                        @lang('lang_v1.upload_documents'):
                    </label>
                    <div class="dropzone" id="payroll_documents_dropzone"></div>
                    {{-- params for media upload --}}
                    <input type="hidden" id="payroll_media_upload_url" value="{{route('attach.medias.to.model')}}">
                    <input type="hidden" id="payroll_model_id" value="{{$payroll->id}}">
                    <input type="hidden" id="payroll_model_type" value="App\Transaction">
                    <input type="hidden" id="payroll_model_media_type" value="payroll_document">
                </div>
            @endif

            @php
                $payroll_documents = $payroll->media->where('model_media_type', 'payroll_document');
            @endphp
            @if($payroll_documents && $payroll_documents->count() > 0)
                <div class="form-group">
                    <label>@lang('lang_v1.uploaded_documents'):</label>
                    <ul class="list-group">
                        @foreach($payroll_documents as $media)
                            <li class="list-group-item">
                                <a href="{{ $media->display_url }}" target="_blank" class="btn btn-xs btn-info">
                                    <i class="fa fa-download"></i> {{ $media->display_name }}
                                </a>
                                @if($can_upload)
                                    <span class="pull-right">
                                        <button type="button" class="btn btn-xs btn-danger delete-document" data-media-id="{{ $media->id }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="form-group">
                    <p class="text-muted">@lang('lang_v1.no_documents_uploaded')</p>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Initialize dropzone when modal is shown (only if user can upload)
        @if($can_upload)
        $(document).on('shown.bs.modal', '.view_modal', function(e) {
            if ($('#payroll_documents_dropzone').length) {
                // Destroy existing dropzone if any
                if (Dropzone.instances.length > 0) {
                    Dropzone.instances.forEach(function(dz) {
                        if (dz.element.id === 'payroll_documents_dropzone') {
                            dz.destroy();
                        }
                    });
                }

                $(this).find("div#payroll_documents_dropzone").dropzone({
                    url: $('#payroll_media_upload_url').val(),
                    paramName: 'file',
                    uploadMultiple: true,
                    autoProcessQueue: true,
                    addRemoveLinks: true,
                    acceptedFiles: '.pdf,.doc,.docx,.jpg,.jpeg,.png',
                    params: {
                        'model_id': $('#payroll_model_id').val(),
                        'model_type': $('#payroll_model_type').val(),
                        'model_media_type': $('#payroll_model_media_type').val()
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(file, response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            // Reload the page or update the document list
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.msg);
                            this.removeFile(file);
                        }
                    },
                    error: function(file, response) {
                        toastr.error('@lang("messages.something_went_wrong")');
                        this.removeFile(file);
                    }
                });
            }
        });
        @endif

        @if($can_upload)
        $(document).on('click', '.delete-document', function(e) {
            e.preventDefault();
            var mediaId = $(this).data('media-id');
            var row = $(this).closest('li');
            
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: '{{url("/hrm/payroll/document")}}/' + mediaId,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                row.fadeOut(300, function() {
                                    $(this).remove();
                                });
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function() {
                            toastr.error('@lang("messages.something_went_wrong")');
                        }
                    });
                }
            });
        });
        @endif
    });
</script>

