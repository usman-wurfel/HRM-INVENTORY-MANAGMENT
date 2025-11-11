<?php

namespace Modules\Essentials\Http\Controllers;

use App\User;
use App\Utils\ModuleUtil;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsLoan;
use Yajra\DataTables\Facades\DataTables;

class LoanController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    protected $loan_statuses;

    /**
     * Constructor
     *
     * @param  ModuleUtil  $moduleUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->loan_statuses = [
            'pending' => [
                'name' => __('lang_v1.pending'),
                'class' => 'bg-yellow',
            ],
            'approved' => [
                'name' => __('essentials::lang.approved'),
                'class' => 'bg-green',
            ],
            'rejected' => [
                'name' => __('essentials::lang.rejected'),
                'class' => 'bg-red',
            ],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }
        
        $can_manage_loan = auth()->user()->can('essentials.loan_manage');
        $can_request_loan = auth()->user()->can('essentials.loan_request');

        if (! $can_manage_loan && ! $can_request_loan) {
            abort(403, 'Unauthorized action.');
        }
        
        if (request()->ajax()) {
            $loans = EssentialsLoan::where('essentials_loans.business_id', $business_id)
                        ->join('users as u', 'u.id', '=', 'essentials_loans.user_id')
                        ->select([
                            'essentials_loans.id',
                            DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user"),
                            'loan_amount',
                            'ref_no',
                            'essentials_loans.status',
                            'essentials_loans.business_id',
                            'reason',
                            'status_note',
                            'essentials_loans.created_at',
                            'total_deduction_paid',
                            DB::raw('(loan_amount - COALESCE(total_deduction_paid, 0)) as remaining_loan'),
                        ]);

            if (! empty(request()->input('user_id'))) {
                $loans->where('essentials_loans.user_id', request()->input('user_id'));
            }

            if (! $can_manage_loan && $can_request_loan) {
                $loans->where('essentials_loans.user_id', auth()->user()->id);
            }

            if (! empty(request()->input('status'))) {
                $loans->where('essentials_loans.status', request()->input('status'));
            }

            if (! empty(request()->input('tab_type'))) {
                if (request()->input('tab_type') == 'loan_request') {
                    $loans->where('essentials_loans.status', 'pending');
                }
            }

            return Datatables::of($loans)
                ->addColumn(
                    'action',
                    function ($row) use ($can_manage_loan) {
                        $html = '';
                        if ($can_manage_loan) {
                            $html .= '<button class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete-loan" data-href="'.action([\Modules\Essentials\Http\Controllers\LoanController::class, 'destroy'], [$row->id]).'"><i class="fa fa-trash"></i> '.__('messages.delete').'</button>';
                        }

                        if ($can_manage_loan && $row->status == 'pending') {
                            $html .= '&nbsp;<a href="#" class="change_status tw-dw-btn tw-dw-btn-info tw-text-white tw-dw-btn-xs" data-status_note="'.($row->status_note ?? '').'" data-loan-id="'.$row->id.'" data-orig-value="'.$row->status.'" data-status-name="'.$this->loan_statuses[$row->status]['name'].'"><i class="fa fa-edit"></i> '.__('essentials::lang.change_status').'</a>';
                        }

                        return $html;
                    }
                )
                ->editColumn('loan_amount', function ($row) {
                    return $this->moduleUtil->num_f($row->loan_amount, true);
                })
                ->addColumn('remaining_loan', function ($row) {
                    $remaining = $row->remaining_loan ?? ($row->loan_amount - ($row->total_deduction_paid ?? 0));
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->moduleUtil->num_f($remaining, true) . '</span>';
                })
                ->editColumn('status', function ($row) use ($can_manage_loan) {
                    $status = '<span class="label '.$this->loan_statuses[$row->status]['class'].'">'
                    .$this->loan_statuses[$row->status]['name'].'</span>';

                    if ($can_manage_loan && $row->status == 'pending') {
                        $status = '<a href="#" class="change_status" data-status-note="'.($row->status_note ?? '').'" data-loan-id="'.$row->id.'" data-orig-value="'.$row->status.'" data-status-name="'.$this->loan_statuses[$row->status]['name'].'"> '.$status.'</a>';
                    }

                    return $status;
                })
                ->editColumn('created_at', function ($row) {
                    return $this->moduleUtil->format_date($row->created_at, true);
                })
                ->filterColumn('user', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'status', 'remaining_loan'])
                ->make(true);
        }
        
        $users = [];
        if ($can_manage_loan) {
            $users = User::forDropdown($business_id, false);
        }
        $loan_statuses = $this->loan_statuses;

        return view('essentials::loan.index')->with(compact('loan_statuses', 'users', 'can_manage_loan', 'can_request_loan'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! auth()->user()->can('essentials.loan_request')) {
            abort(403, 'Unauthorized action.');
        }

        return view('essentials::loan.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }
        
        if (! auth()->user()->can('essentials.loan_request')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['loan_amount', 'reason']);

            $input['business_id'] = $business_id;
            $input['status'] = 'pending';
            $input['user_id'] = auth()->user()->id;

            DB::beginTransaction();
            
            //Update reference count
            $ref_count = $this->moduleUtil->setAndGetReferenceCount('loan');
            //Generate reference number
            if (empty($input['ref_no'])) {
                $input['ref_no'] = $this->moduleUtil->generateReferenceNumber('loan', $ref_count, null, '');
            }

            EssentialsLoan::create($input);

            DB::commit();

            $output = ['success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('essentials.loan_manage')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                EssentialsLoan::where('business_id', $business_id)->where('id', $id)->delete();

                $output = ['success' => true,
                    'msg' => __('lang_v1.deleted_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function changeStatus(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')) || ! auth()->user()->can('essentials.loan_manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['status', 'loan_id', 'status_note', 'monthly_deduction']);

            $loan = EssentialsLoan::where('business_id', $business_id)
                            ->find($input['loan_id']);

            $loan->status = $input['status'];
            $loan->status_note = $input['status_note'];
            
            if ($input['status'] == 'approved' && !empty($input['monthly_deduction'])) {
                $loan->monthly_deduction = $this->moduleUtil->num_uf($input['monthly_deduction']);
            }
            
            $loan->approved_by = auth()->user()->id;
            $loan->approved_at = now();
            $loan->save();

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}

