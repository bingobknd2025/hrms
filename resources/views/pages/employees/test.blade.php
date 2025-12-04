<?php<?php



namespace App\Http\Controllers\Admin;namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;use App\Http\Controllers\Controller;

use App\Models\Contact;use App\Models\Contact;

use Exception;use Exception;

use Illuminate\Http\Request;use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Crypt;use Illuminate\Support\Facades\Crypt;

use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;use Illuminate\Support\Facades\Log;

use Illuminate\Validation\Rule;use Illuminate\Validation\Rule;

use Illuminate\Validation\ValidationException;use Illuminate\Validation\ValidationException;



class ContactController extends Controllerclass ContactController extends Controller

{{

    function __construct()    function __construct()

    {    {

        $this->middleware('auth');        $this->middleware('auth');

        $this->middleware('permission:contact-view|contact-create|contact-edit|contact-delete', ['only' => ['index', 'show']]);        $this->middleware('permission:contact-view|contact-create|contact-edit|contact-delete', ['only' => ['index', 'show']]);

        $this->middleware('permission:contact-create', ['only' => ['create', 'store']]);        $this->middleware('permission:contact-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:contact-edit', ['only' => ['edit', 'update']]);        $this->middleware('permission:contact-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:contact-delete', ['only' => ['destroy']]);        $this->middleware('permission:contact-delete', ['only' => ['destroy']]);

        $this->middleware('permission:contact-view', ['only' => ['show']]);        $this->middleware('permission:contact-view', ['only' => ['show']]);

    }    }



    public function index(Request $request)    public function index(Request $request)

    {    {

        try {        try {

            $request->validate([            $request->validate([

                'search' => 'nullable|string|max:255',                'search' => 'nullable|string|max:255',

                'gender' => 'nullable|string|in:Male,Female,Other,all',                'gender' => 'nullable|string|in:Male,Female,Other,all',

                'business_type' => 'nullable|string|max:255',                'business_type' => 'nullable|string|max:255',

                'fromDate' => 'nullable|date_format:d-m-Y',                'fromDate' => 'nullable|date_format:d-m-Y',

                'toDate' => 'nullable|date_format:d-m-Y|after_or_equal:fromDate',                'toDate' => 'nullable|date_format:d-m-Y|after_or_equal:fromDate',

                'records' => 'nullable|integer|min:10|max:100',                'records' => 'nullable|integer|min:10|max:100',

            ]);            ]);



            // Initialize the query for contacts            // Initialize the query for contacts

            $data = Contact::query();            $data = Contact::query();



            // Apply the filters to the query            // Apply the filters to the query

            $this->applyFilters($data, $request);            $this->applyFilters($data, $request);



            // Get the number of records per page from the request, defaulting to 10 if not provided            // Get the number of records per page from the request, defaulting to 10 if not provided

            $recordsPerPage = $request->input('records', 10); // Fetch 'records' parameter or default to 10            $recordsPerPage = $request->input('records', 10); // Fetch 'records' parameter or default to 10



            // Paginate the data            // Paginate the data

            $data = $data->paginate($recordsPerPage);            $data = $data->paginate($recordsPerPage);



            // Append filter parameters to the pagination links            // Append filter parameters to the pagination links

            $data->appends($request->except('page')); // Keep all query parameters except 'page'            $data->appends($request->except('page')); // Keep all query parameters except 'page'



            // Check if the request is made via AJAX for dynamic loading            // Check if the request is made via AJAX for dynamic loading

            if ($request->ajax()) {            if ($request->ajax()) {

                return view('admin.contacts.partials.dataTable', compact('data'))->render(); // Return rendered partial view                return view('admin.contacts.partials.dataTable', compact('data'))->render(); // Return rendered partial view

            }            }



            // For non-AJAX requests, load with the specified or default pagination            // For non-AJAX requests, load with the specified or default pagination

            return view('admin.contacts.index', compact('data'));            return view('admin.contacts.index', compact('data'));

        } catch (ValidationException $e) {        } catch (ValidationException $e) {

            // Return validation errors as JSON            // Return validation errors as JSON

            Log::error('Manage Contacts Validation Errors');            Log::error('Manage Contacts Validation Errors');

            if ($request->ajax()) {            if ($request->ajax()) {

                return response()->json([                return response()->json([

                    'success' => false,                    'success' => false,

                    'message' => 'Validation errors occurred.', // General message                    'message' => 'Validation errors occurred.', // General message

                    'errors' => $e->validator->errors()                    'errors' => $e->validator->errors()

                ], 422);                ], 422);

            }            }

            return view('errors.422');            return view('errors.422');

        } catch (Exception $e) {        } catch (Exception $e) {

            // Log the error and return with an error message            // Log the error and return with an error message

            Log::error('Manage Contacts Error: ' . $e->getMessage());            Log::error('Manage Contacts Error: ' . $e->getMessage());

            if ($request->ajax()) {            if ($request->ajax()) {

                return response()->json([                return response()->json([

                    'success' => false,                    'success' => false,

                    'message' => 'Internal server error. Please try again.', // General message                    'message' => 'Internal server error. Please try again.', // General message

                    'error' => $e->getMessage() // Include the exception message for debugging (optional)                    'error' => $e->getMessage() // Include the exception message for debugging (optional)

                ], 500);                ], 500);

            }            }

            return view('errors.500');            return view('errors.500');

        }        }

    }    }



    /**    /**

     * Apply filters to the contacts query based on request parameters.     * Apply filters to the contacts query based on request parameters.

     *      * 

     * @param  \Illuminate\Database\Eloquent\Builder $query     * @param  \Illuminate\Database\Eloquent\Builder $query

     * @param  \Illuminate\Http\Request $request     * @param  \Illuminate\Http\Request $request

     */     */

    private function applyFilters($query, Request $request)    private function applyFilters($query, Request $request)

    {    {

        // Search filter: Filter contacts by first_name, last_name, or business_name if a search term is provided        // Search filter: Filter contacts by first_name, last_name, or business_name if a search term is provided

        if ($request->filled('search')) {        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {            $query->where(function ($q) use ($request) {

                $q->where('first_name', 'like', '%' . $request->search . '%')                $q->where('first_name', 'like', '%' . $request->search . '%')

                    ->orWhere('last_name', 'like', '%' . $request->search . '%')                    ->orWhere('last_name', 'like', '%' . $request->search . '%')

                    ->orWhere('business_name', 'like', '%' . $request->search . '%');                    ->orWhere('business_name', 'like', '%' . $request->search . '%');

            });            });

        }        }



        // Gender filter: Filter by gender if not set to 'all'        // Gender filter: Filter by gender if not set to 'all'

        if ($request->filled('gender') && $request->gender !== 'all') {        if ($request->filled('gender') && $request->gender !== 'all') {

            $query->where('gender', $request->gender);            $query->where('gender', $request->gender);

        }        }



        // Business type filter: Filter by business_type if provided        // Business type filter: Filter by business_type if provided

        if ($request->filled('business_type')) {        if ($request->filled('business_type')) {

            $query->where('business_type', 'like', '%' . $request->business_type . '%');            $query->where('business_type', 'like', '%' . $request->business_type . '%');

        }        }



        // Check if fromDate and toDate are present in the request        // Check if fromDate and toDate are present in the request

        if ($request->filled('fromDate')) {        if ($request->filled('fromDate')) {

            $fromDate = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromDate);            $fromDate = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromDate);

            $query->where('updated_at', '>=', $fromDate);            $query->where('updated_at', '>=', $fromDate);

        }        }



        if ($request->filled('toDate')) {        if ($request->filled('toDate')) {

            $toDate = \Carbon\Carbon::createFromFormat('d-m-Y', $request->toDate);            $toDate = \Carbon\Carbon::createFromFormat('d-m-Y', $request->toDate);

            $query->where('updated_at', '<=', $toDate);            $query->where('updated_at', '<=', $toDate);

        }        }



        // Company ID filter: Restrict results to the user's company        // Company ID filter: Restrict results to the user's company

        $query->where('company_id', Auth::user()->company_id);        $query->where('company_id', Auth::user()->company_id);

        $query->orderBy('updated_at', 'DESC');        $query->orderBy('updated_at', 'DESC');

    }    }



    // Show the form for creating a new resource

    public function create() {

        return view('admin.contacts.partials.create');    // Show the form for creating a new resource

    }    public function create() {

        return view('admin.contacts.partials.create');

    // Store a newly created resource in storage    }

    public function store(Request $request)

    {    // Store a newly created resource in storage

        try {    public function store(Request $request)

            // Validate the incoming data    {

            $request->validate([        try {

                'first_name' => 'required|string|max:255',            // Validate the incoming data

                'middle_name' => 'nullable|string|max:255',            $request->validate([

                'last_name' => 'required|string|max:255',                'first_name' => 'required|string|max:255',

                'profile' => 'nullable|string|max:255',                'middle_name' => 'nullable|string|max:255',

                'gender' => 'nullable|in:Male,Female,Other',                'last_name' => 'required|string|max:255',

                'dob' => 'nullable|date',                'profile' => 'nullable|string|max:255',

                'business_name' => 'nullable|string|max:255',                'gender' => 'nullable|in:Male,Female,Other',

                'business_type' => 'nullable|string|max:255',                'dob' => 'nullable|date',

                'designation' => 'nullable|string|max:255',                'business_name' => 'nullable|string|max:255',

                'notes' => 'nullable|string',                'business_type' => 'nullable|string|max:255',

            ]);                'designation' => 'nullable|string|max:255',

                'notes' => 'nullable|string',

            DB::beginTransaction(); // Start transaction for atomic operation            ]);



            // Create the contact with company_id from the authenticated user            DB::beginTransaction(); // Start transaction for atomic operation

            Contact::create([

                'first_name' => $request->first_name,            // Create the contact with company_id from the authenticated user

                'middle_name' => $request->middle_name,            Contact::create([

                'last_name' => $request->last_name,                'first_name' => $request->first_name,

                'profile' => $request->profile,                'middle_name' => $request->middle_name,

                'gender' => $request->gender,                'last_name' => $request->last_name,

                'dob' => $request->dob,                'profile' => $request->profile,

                'business_name' => $request->business_name,                'gender' => $request->gender,

                'business_type' => $request->business_type,                'dob' => $request->dob,

                'designation' => $request->designation,                'business_name' => $request->business_name,

                'notes' => $request->notes,                'business_type' => $request->business_type,

                'company_id' => Auth::user()->company_id, // Get company_id from authenticated user                'designation' => $request->designation,

            ]);                'notes' => $request->notes,

                'company_id' => Auth::user()->company_id, // Get company_id from authenticated user

            DB::commit(); // Commit transaction            ]);

            return response()->json(['success' => true, 'message' => 'Contact created successfully.'], 201);

        } catch (ValidationException $e) {            DB::commit(); // Commit transaction

            DB::rollBack(); // Rollback if there's a validation error            return response()->json(['success' => true, 'message' => 'Contact created successfully.'], 201);

            Log::error('Validation errors creating contact', ['errors' => $e->validator->errors()]);        } catch (ValidationException $e) {

            DB::rollBack(); // Rollback if there's a validation error

            // Return validation errors in a structured format            Log::error('Validation errors creating contact', ['errors' => $e->validator->errors()]);

            return response()->json([

                'success' => false,            // Return validation errors in a structured format

                'message' => 'Validation errors occurred.', // General message            return response()->json([

                'errors' => $e->validator->errors()                'success' => false,

            ], 422);                'message' => 'Validation errors occurred.', // General message

        } catch (Exception $e) {                'errors' => $e->validator->errors()

            DB::rollBack(); // Rollback if there's a general error            ], 422);

            Log::error('Error creating contact: ' . $e->getMessage());        } catch (Exception $e) {

            DB::rollBack(); // Rollback if there's a general error

            // Return a structured error message            Log::error('Error creating contact: ' . $e->getMessage());

            return response()->json([

                'success' => false,            // Return a structured error message

                'message' => 'Internal server error. Please try again.', // General message            return response()->json([

                'error' => $e->getMessage() // Include the exception message for debugging (optional)                'success' => false,

            ], 500);                'message' => 'Internal server error. Please try again.', // General message

        }                'error' => $e->getMessage() // Include the exception message for debugging (optional)

    }            ], 500);

        }

    // Display the specified resource    }

    public function show($id)

    {    // Display the specified resource

        $row = Contact::where('company_id', Auth::user()->company_id)->where('id', Crypt::decrypt($id))->first();    public function show($id)

            {

        if (!$row) {        $row = Contact::where('company_id', Auth::user()->company_id)->where('id', Crypt::decrypt($id))->first();

            return response()->json(['error' => 'Contact not found'], 404);        

        }        if (!$row) {

                    return response()->json(['error' => 'Contact not found'], 404);

        echo ' <div class="modal-header">           }

                <h5 class="modal-title">Contact Details</h5>        

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>        echo ' <div class="modal-header">   

            </div>                <h5 class="modal-title">Contact Details</h5>

            <div class="modal-body">                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            <table class="table table-hover table-sm table-border">            </div>

                    <tbody>            <div class="modal-body">

                        <tr>            <table class="table table-hover table-sm table-border">

                            <th>Create Date</th>                    <tbody>

                            <td>' . $row->created_at . '</td>                        <tr>

                            <th>Last Updated</th>                            <th>Create Date</th>

                            <td>' . $row->updated_at . '</td>                            <td>' . $row->created_at . '</td>

                        </tr>                            <th>Last Updated</th>

                        <tr>                            <td>' . $row->updated_at . '</td>

                            <th>First Name</th>                        </tr>

                            <td>' . $row->first_name . '</td>                        <tr>

                            <th>Last Name</th>                            <th>First Name</th>

                            <td>' . $row->last_name . '</td>                            <td>' . $row->first_name . '</td>

                        </tr>                            <th>Last Name</th>

                        <tr>                            <td>' . $row->last_name . '</td>

                            <th>Middle Name</th>                        </tr>

                            <td>' . ($row->middle_name ?? 'N/A') . '</td>                        <tr>

                            <th>Gender</th>                            <th>Middle Name</th>

                            <td>' . ($row->gender ?? 'N/A') . '</td>                            <td>' . ($row->middle_name ?? 'N/A') . '</td>

                        </tr>                            <th>Gender</th>

                        <tr>                            <td>' . ($row->gender ?? 'N/A') . '</td>

                            <th>Date of Birth</th>                        </tr>

                            <td>' . ($row->dob ?? 'N/A') . '</td>                        <tr>

                            <th>Profile</th>                            <th>Date of Birth</th>

                            <td>' . ($row->profile ?? 'N/A') . '</td>                            <td>' . ($row->dob ?? 'N/A') . '</td>

                        </tr>                            <th>Profile</th>

                        <tr>                            <td>' . ($row->profile ?? 'N/A') . '</td>

                            <th>Business Name</th>                        </tr>

                            <td>' . ($row->business_name ?? 'N/A') . '</td>                        <tr>

                            <th>Business Type</th>                            <th>Business Name</th>

                            <td>' . ($row->business_type ?? 'N/A') . '</td>                            <td>' . ($row->business_name ?? 'N/A') . '</td>

                        </tr>                            <th>Business Type</th>

                        <tr>                            <td>' . ($row->business_type ?? 'N/A') . '</td>

                            <th>Designation</th>                        </tr>

                            <td>' . ($row->designation ?? 'N/A') . '</td>                        <tr>

                            <th></th>                            <th>Designation</th>

                            <td></td>                            <td>' . ($row->designation ?? 'N/A') . '</td>

                        </tr>                            <th></th>

                        <tr>                            <td></td>

                            <th>Notes</th>                        </tr>

                            <td colspan="3">' . ($row->notes ?? 'N/A') . '</td>                        <tr>

                        </tr>                            <th>Notes</th>

                    </tbody>                            <td colspan="3">' . ($row->notes ?? 'N/A') . '</td>

                </table>                        </tr>

            </div>';                    </tbody>

    }                </table>

            </div>';

    public function edit($id)    }

    {

        $row = Contact::where('company_id', Auth::user()->company_id)->where('id', Crypt::decrypt($id))->first();

            // Import data from a file

        if (!$row) {    public function import(Request $request)

            return response()->json(['error' => 'Contact not found'], 404);    {

        }        try {

                    $request->validate([

        echo ' <div class="modal-header">                'file' => 'required|file|mimes:xlsx,xls,csv|max:2048', // Max 2MB file size

                <h5 class="modal-title">Edit Contact</h5>            ]);

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>            // Process the uploaded file

            <form id="masterUpdateForm" action="' . route('contacts.update') . '" method="POST" enctype="multipart/form-data">            $file = $request->file('file');

            <input type="hidden" name="_token" value="' . csrf_token() . '" />            $path = $file->storeAs('imports', $file->getClientOriginalName(), 'local');

            <input type="hidden" name="updateToken" value="' . $id . '" />

                <div class="modal-body">            // Process the file data

                    <div id="successUpdateContainer"></div>            // You can use a package like Maatwebsite/Laravel-Excel to read the file

                    <div id="errorUpdateContainer"></div>            // https://docs.laravel-excel.com/3.1/getting-started/

                    <div class="row">            // $data = Excel::toCollection(new ContactImport, $path);

                        <div class="col-lg-6">

                            <div class="mb-3">            // Process the data and save to the database

                                <label class="form-label required">First Name</label>            // foreach ($data as $row) {

                                <input type="text" class="form-control" name="first_name" value="' . $row->first_name . '" placeholder="First Name" required>            //     Contact::create([

                            </div>            //         'first_name' => $row['first_name'],

                        </div>            //         'middle_name' => $row['middle_name'],

                        <div class="col-lg-6">            //         'last_name' => $row['last_name'],

                            <div class="mb-3">            //         'gender' => $row['gender'],

                                <label class="form-label required">Last Name</label>            //         'dob' => $row['dob'],

                                <input type="text" class="form-control" name="last_name" value="' . $row->last_name . '" placeholder="Last Name" required>            //         'business_name' => $row['business_name'],

                            </div>            //         'business_type' => $row['business_type'],

                        </div>            //         'designation' => $row['designation'],

                    </div>            //         'notes' => $row['notes'],

                    <div class="row">            //         'company_id' => Auth::user()->company_id,

                        <div class="col-lg-6">            //     ]);

                            <div class="mb-3">            // }

                                <label class="form-label">Middle Name</label>

                                <input type="text" name="middle_name" class="form-control" value="' . ($row->middle_name ?? '') . '" placeholder="Middle Name">            // Return a success message

                            </div>            return response()->json(['success' => true, 'message' => 'Contacts imported successfully.'], 200);

                        </div>        } catch (ValidationException $e) {

                        <div class="col-lg-6">            Log::error('Validation errors importing contacts', ['errors' => $e->validator->errors()]);

                            <div class="mb-3">

                                <label class="form-label">Gender</label>            // Return validation errors in a structured format

                                <select class="form-select" name="gender">            return response()->json([

                                    <option value="">Select Gender</option>                'success' => false,

                                    <option value="Male"' . ($row->gender == 'Male' ? ' selected' : '') . '>Male</option>                'message' => 'Validation errors occurred.', // General message

                                    <option value="Female"' . ($row->gender == 'Female' ? ' selected' : '') . '>Female</option>                'errors' => $e->validator->errors()

                                    <option value="Other"' . ($row->gender == 'Other' ? ' selected' : '') . '>Other</option>            ], 422);

                                </select>        } catch (Exception $e) {

                            </div>            Log::error('Error importing contacts: ' . $e->getMessage());

                        </div>

                    </div>            // Return a structured error message

                    <div class="row">            return response()->json([

                        <div class="col-lg-6">                'success' => false,

                            <div class="mb-3">                'message' => 'Internal server error. Please try again.', // General message

                                <label class="form-label">Date of Birth</label>                'error' => $e->getMessage() // Include the exception message for debugging (optional)

                                <input type="date" name="dob" class="form-control" value="' . ($row->dob ?? '') . '">            ], 500);

                            </div>        }

                        </div>    }

                        <div class="col-lg-6">

                            <div class="mb-3">    // Export data to a file

                                <label class="form-label">Profile</label>    public function export()

                                <input type="text" name="profile" class="form-control" value="' . ($row->profile ?? '') . '" placeholder="Profile">    {

                            </div>        try {

                        </div>            // Get the contacts data

                    </div>            $contacts = Contact::where('company_id', Auth::user()->company_id)->get();

                    <div class="row">

                        <div class="col-lg-6">            // Generate the export file

                            <div class="mb-3">            // You can use a package like Maatwebsite/Laravel-Excel to export the data

                                <label class="form-label">Business Name</label>            // https://docs.laravel-excel.com/3.1/getting-started/

                                <input type="text" name="business_name" class="form-control" value="' . ($row->business_name ?? '') . '" placeholder="Business Name">            // Excel::store(new ContactExport($contacts), 'exports/contacts.xlsx');

                            </div>

                        </div>            // Return a success message

                        <div class="col-lg-6">            return response()->json(['success' => true, 'message' => 'Contacts exported successfully.'], 200);

                            <div class="mb-3">        } catch (Exception $e) {

                                <label class="form-label">Business Type</label>            Log::error('Error exporting contacts: ' . $e->getMessage());

                                <input type="text" name="business_type" class="form-control" value="' . ($row->business_type ?? '') . '" placeholder="Business Type">

                            </div>            // Return a structured error message

                        </div>            return response()->json([

                    </div>                'success' => false,

                    <div class="row">                'message' => 'Internal server error. Please try again.', // General message

                        <div class="col-lg-6">                'error' => $e->getMessage() // Include the exception message for debugging (optional)

                            <div class="mb-3">            ], 500);

                                <label class="form-label">Designation</label>        }

                                <input type="text" name="designation" class="form-control" value="' . ($row->designation ?? '') . '" placeholder="Designation">    }

                            </div>}

                        </div>

                    </div>    public function edit($id)

                    <div class="row">    {

                        <div class="col-lg-12">        $row = Contact::where('company_id', Auth::user()->company_id)->where('id', Crypt::decrypt($id))->first();

                            <div class="mb-3">        

                                <label class="form-label">Notes</label>        if (!$row) {

                                <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes">' . ($row->notes ?? '') . '</textarea>            return response()->json(['error' => 'Contact not found'], 404);

                            </div>        }

                        </div>        

                    </div>        echo ' <div class="modal-header">

                </div>                <h5 class="modal-title">Edit Contact</h5>

                <div class="modal-footer">                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">            </div>

                        Cancel            <form id="masterUpdateForm" action="' . route('contacts.update') . '" method="POST" enctype="multipart/form-data">

                    </a>            <input type="hidden" name="_token" value="' . csrf_token() . '" />

                    <button type="submit" class="btn btn-primary ms-auto">            <input type="hidden" name="updateToken" value="' . $id . '" />

                        Save Changes                <div class="modal-body">

                    </button>                    <div id="successUpdateContainer"></div>

                </div>                    <div id="errorUpdateContainer"></div>

            </form>';                    <div class="row">

    }                        <div class="col-lg-6">

                            <div class="mb-3">

    // Update the specified resource in storage                                <label class="form-label required">First Name</label>

    public function update(Request $request)                                <input type="text" class="form-control" name="first_name" value="' . $row->first_name . '" placeholder="First Name" required>

    {                            </div>

        try {                        </div>

            // Validate the incoming data                        <div class="col-lg-6">

            $request->validate([                            <div class="mb-3">

                'first_name' => 'required|string|max:255',                                <label class="form-label required">Last Name</label>

                'middle_name' => 'nullable|string|max:255',                                <input type="text" class="form-control" name="last_name" value="' . $row->last_name . '" placeholder="Last Name" required>

                'last_name' => 'required|string|max:255',                            </div>

                'profile' => 'nullable|string|max:255',                        </div>

                'gender' => 'nullable|in:Male,Female,Other',                    </div>

                'dob' => 'nullable|date',                    <div class="row">

                'business_name' => 'nullable|string|max:255',                        <div class="col-lg-6">

                'business_type' => 'nullable|string|max:255',                            <div class="mb-3">

                'designation' => 'nullable|string|max:255',                                <label class="form-label">Middle Name</label>

                'notes' => 'nullable|string',                                <input type="text" name="middle_name" class="form-control" value="' . ($row->middle_name ?? '') . '" placeholder="Middle Name">

            ]);                            </div>

                        </div>

            DB::beginTransaction(); // Start transaction for atomic operation                        <div class="col-lg-6">

                            <div class="mb-3">

            // Find the existing contact                                <label class="form-label">Gender</label>

            $contact = Contact::where('id', Crypt::decrypt($request->updateToken))                                <select class="form-select" name="gender">

                ->where('company_id', Auth::user()->company_id)                                    <option value="">Select Gender</option>

                ->first();                                    <option value="Male"' . ($row->gender == 'Male' ? ' selected' : '') . '>Male</option>

                                    <option value="Female"' . ($row->gender == 'Female' ? ' selected' : '') . '>Female</option>

            // Check if the contact exists                                    <option value="Other"' . ($row->gender == 'Other' ? ' selected' : '') . '>Other</option>

            if (!$contact) {                                </select>

                return response()->json([                            </div>

                    'success' => false,                        </div>

                    'message' => 'Contact not found or does not belong to the current company.'                    </div>

                ], 404);                    <div class="row">

            }                        <div class="col-lg-6">

                            <div class="mb-3">

            // Update the contact                                <label class="form-label">Date of Birth</label>

            $contact->update([                                <input type="date" name="dob" class="form-control" value="' . ($row->dob ?? '') . '">

                'first_name' => $request->first_name,                            </div>

                'middle_name' => $request->middle_name,                        </div>

                'last_name' => $request->last_name,                        <div class="col-lg-6">

                'profile' => $request->profile,                            <div class="mb-3">

                'gender' => $request->gender,                                <label class="form-label">Profile</label>

                'dob' => $request->dob,                                <input type="text" name="profile" class="form-control" value="' . ($row->profile ?? '') . '" placeholder="Profile">

                'business_name' => $request->business_name,                            </div>

                'business_type' => $request->business_type,                        </div>

                'designation' => $request->designation,                    </div>

                'notes' => $request->notes,                    <div class="row">

            ]);                        <div class="col-lg-6">

                            <div class="mb-3">

            DB::commit(); // Commit transaction                                <label class="form-label">Business Name</label>

            return response()->json(['success' => true, 'message' => 'Contact updated successfully.'], 200);                                <input type="text" name="business_name" class="form-control" value="' . ($row->business_name ?? '') . '" placeholder="Business Name">

        } catch (ValidationException $e) {                            </div>

            DB::rollBack(); // Rollback if there's a validation error                        </div>

            Log::error('Validation errors updating contact', ['errors' => $e->validator->errors()]);                        <div class="col-lg-6">

                            <div class="mb-3">

            // Return validation errors in a structured format                                <label class="form-label">Business Type</label>

            return response()->json([                                <input type="text" name="business_type" class="form-control" value="' . ($row->business_type ?? '') . '" placeholder="Business Type">

                'success' => false,                            </div>

                'message' => 'Validation errors occurred.', // General message                        </div>

                'errors' => $e->validator->errors()                    </div>

            ], 422);                    <div class="row">

        } catch (Exception $e) {                        <div class="col-lg-6">

            DB::rollBack(); // Rollback if there's a general error                            <div class="mb-3">

            Log::error('Error updating contact: ' . $e->getMessage());                                <label class="form-label">Designation</label>

                                <input type="text" name="designation" class="form-control" value="' . ($row->designation ?? '') . '" placeholder="Designation">

            // Return a structured error message                            </div>

            return response()->json([                        </div>

                'success' => false,                    </div>

                'message' => 'Internal server error. Please try again.', // General message                    <div class="row">

                'error' => $e->getMessage() // Include the exception message for debugging (optional)                        <div class="col-lg-12">

            ], 500);                            <div class="mb-3">

        }                                <label class="form-label">Notes</label>

    }                                <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes">' . ($row->notes ?? '') . '</textarea>

                            </div>

    // Remove the specified resource from storage                        </div>

    public function destroy($id)                    </div>

    {                </div>

        try {                <div class="modal-footer">

            DB::beginTransaction(); // Start transaction for atomic operation                    <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">

                        Cancel

            // Find the contact to delete                    </a>

            $contact = Contact::where('id', Crypt::decrypt($id))                    <button type="submit" class="btn btn-primary ms-auto">

                ->where('company_id', Auth::user()->company_id)                        Save Changes

                ->first();                    </button>

                </div>

            // Check if the contact exists            </form>';

            if (!$contact) {    }

                return response()->json([

                    'success' => false,    // Update the specified resource in storage

                    'message' => 'Contact not found or does not belong to the current company.'    public function update(Request $request)

                ], 404);    {

            }        try {

            // Validate the incoming data

            // Delete the contact            $request->validate([

            $contact->delete();                'first_name' => 'required|string|max:255',

                'middle_name' => 'nullable|string|max:255',

            DB::commit(); // Commit transaction                'last_name' => 'required|string|max:255',

            return response()->json(['success' => true, 'message' => 'Contact deleted successfully.'], 200);                'profile' => 'nullable|string|max:255',

        } catch (Exception $e) {                'gender' => 'nullable|in:Male,Female,Other',

            DB::rollBack(); // Rollback if there's a general error                'dob' => 'nullable|date',

            Log::error('Error deleting contact: ' . $e->getMessage());                'business_name' => 'nullable|string|max:255',

                'business_type' => 'nullable|string|max:255',

            // Return a structured error message                'designation' => 'nullable|string|max:255',

            return response()->json([                'notes' => 'nullable|string',

                'success' => false,            ]);

                'message' => 'Internal server error. Please try again.', // General message

                'error' => $e->getMessage() // Include the exception message for debugging (optional)            DB::beginTransaction(); // Start transaction for atomic operation

            ], 500);

        }            // Find the existing contact

    }            $contact = Contact::where('id', Crypt::decrypt($request->updateToken))

                ->where('company_id', Auth::user()->company_id)

    // Import data from a file                ->first();

    public function import(Request $request)

    {            // Check if the contact exists

        try {            if (!$contact) {

            $request->validate([                return response()->json([

                'file' => 'required|file|mimes:xlsx,xls,csv|max:2048', // Max 2MB file size                    'success' => false,

            ]);                    'message' => 'Contact not found or does not belong to the current company.'

                ], 404);

            // Process the uploaded file            }

            $file = $request->file('file');

            $path = $file->storeAs('imports', $file->getClientOriginalName(), 'local');            // Update the contact

            $contact->update([

            // Process the file data                'first_name' => $request->first_name,

            // You can use a package like Maatwebsite/Laravel-Excel to read the file                'middle_name' => $request->middle_name,

            // https://docs.laravel-excel.com/3.1/getting-started/                'last_name' => $request->last_name,

            // $data = Excel::toCollection(new ContactImport, $path);                'profile' => $request->profile,

                'gender' => $request->gender,

            // Process the data and save to the database                'dob' => $request->dob,

            // foreach ($data as $row) {                'business_name' => $request->business_name,

            //     Contact::create([                'business_type' => $request->business_type,

            //         'first_name' => $row['first_name'],                'designation' => $request->designation,

            //         'middle_name' => $row['middle_name'],                'notes' => $request->notes,

            //         'last_name' => $row['last_name'],            ]);

            //         'gender' => $row['gender'],

            //         'dob' => $row['dob'],            DB::commit(); // Commit transaction

            //         'business_name' => $row['business_name'],            return response()->json(['success' => true, 'message' => 'Contact updated successfully.'], 200);

            //         'business_type' => $row['business_type'],        } catch (ValidationException $e) {

            //         'designation' => $row['designation'],            DB::rollBack(); // Rollback if there's a validation error

            //         'notes' => $row['notes'],            Log::error('Validation errors updating contact', ['errors' => $e->validator->errors()]);

            //         'company_id' => Auth::user()->company_id,

            //     ]);            // Return validation errors in a structured format

            // }            return response()->json([

                'success' => false,

            // Return a success message                'message' => 'Validation errors occurred.', // General message

            return response()->json(['success' => true, 'message' => 'Contacts imported successfully.'], 200);                'errors' => $e->validator->errors()

        } catch (ValidationException $e) {            ], 422);

            Log::error('Validation errors importing contacts', ['errors' => $e->validator->errors()]);        } catch (Exception $e) {

            DB::rollBack(); // Rollback if there's a general error

            // Return validation errors in a structured format            Log::error('Error updating contact: ' . $e->getMessage());

            return response()->json([

                'success' => false,            // Return a structured error message

                'message' => 'Validation errors occurred.', // General message            return response()->json([

                'errors' => $e->validator->errors()                'success' => false,

            ], 422);                'message' => 'Internal server error. Please try again.', // General message

        } catch (Exception $e) {                'error' => $e->getMessage() // Include the exception message for debugging (optional)

            Log::error('Error importing contacts: ' . $e->getMessage());            ], 500);

        }

            // Return a structured error message    }

            return response()->json([

                'success' => false,

                'message' => 'Internal server error. Please try again.', // General message    public function action_edit($id)

                'error' => $e->getMessage() // Include the exception message for debugging (optional)    {

            ], 500);        $row = Lead::where('company_id', Auth::user()->company_id)->where('id', Crypt::decrypt($id))->first();

        }        $actionOptions = $this->getLeadActionOptions();

    }        echo ' <div class="modal-header">

                <h5 class="modal-title">Lead Action Taken</h5>

    // Export data to a file                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

    public function export()            </div>

    {            <form id="masterUpdateForm" action="' . route('leads-action.update') . '" method="POST" enctype="multipart/form-data">

        try {            <input type="hidden" name="_token" value="' . csrf_token() . '" />

            // Get the contacts data            <input type="hidden" name="updateToken" value="' . $id . '" />

            $contacts = Contact::where('company_id', Auth::user()->company_id)->get();                <div class="modal-body">

                    <div id="successUpdateContainer"></div>

            // Generate the export file                    <div id="errorUpdateContainer"></div>

            // You can use a package like Maatwebsite/Laravel-Excel to export the data                    <div class="row">

            // https://docs.laravel-excel.com/3.1/getting-started/                        <div class="col-lg-6">

            // Excel::store(new ContactExport($contacts), 'exports/contacts.xlsx');                            <div class="mb-3">

                                <label class="form-label required">Action Type</label>

            // Return a success message                                <select class="form-select" name="type" required>

            return response()->json(['success' => true, 'message' => 'Contacts exported successfully.'], 200);                                 ' . $actionOptions . '

        } catch (Exception $e) {                                </select>

            Log::error('Error exporting contacts: ' . $e->getMessage());                            </div>

                        </div>

            // Return a structured error message                        <div class="col-lg-6">

            return response()->json([                            <div class="mb-3">

                'success' => false,                                <label class="form-label required">Lead ID</label>

                'message' => 'Internal server error. Please try again.', // General message                                <div class="form-control">

                'error' => $e->getMessage() // Include the exception message for debugging (optional)                                 ' . $row->lead_id . '

            ], 500);                                </div>

        }                            </div>

    }                        </div>

}                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div>
                                <label class="form-label required">Lead Action Brief</label>
                                <textarea class="form-control" name="description" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary ms-auto">
                        Save Changes
                    </button>
                </div>
            </form>';
    }


    // Update the specified resource in storage
    public function action_update(Request $request)
    {
        try {
            // Validate the incoming data
            $request->validate([
                'description' => 'required|string',
                'type' => [
                    'required',
                    Rule::exists('action_types', 'id')->where(function ($query) {
                        $query->where('company_id', Auth::user()->company_id); // Match company_id for source
                    }),
                ],
            ]);

            DB::beginTransaction(); // Start transaction for atomic operation

            // Find the existing lead
            $lead = Lead::where('id', Crypt::decrypt($request->updateToken))
                ->where('company_id', Auth::user()->company_id)
                ->first();

            // Check if the lead exists
            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found or does not belong to the current company.'
                ], 404);
            }

            $action = LeadAction::create([
                'lead_id' => $lead->id,
                'action_type' => $request->type,
                'description' => $request->description,
                'company_id' => Auth::user()->company_id, // Get company_id from authenticated user
                'created_by' => Auth::user()->id,         // Get user ID from authenticated user
            ]);

            $lead->update([
                'action_taken' => $action->id,
                'updated_by' => Auth::user()->id, // You might want to keep track of who updated it
            ]);

            DB::commit(); // Commit transaction
            return response()->json(['success' => true, 'message' => 'Lead action successfully submitted.'], 200);
        } catch (ValidationException $e) {
            DB::rollBack(); // Rollback if there's a validation error
            Log::error('Validation errors updating lead', ['errors' => $e->validator->errors()]);

            // Return validation errors in a structured format
            return response()->json([
                'success' => false,
                'message' => 'Validation errors occurred.', // General message
                'errors' => $e->validator->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack(); // Rollback if there's a general error
            Log::error('Error updating lead: ' . $e->getMessage());

            // Return a structured error message
            return response()->json([
                'success' => false,
                'message' => 'Internal server error. Please try again.', // General message
                'error' => $e->getMessage() // Include the exception message for debugging (optional)
            ], 500);
        }
    }


    // Remove the specified resource from storage
    public function destroy($id)
    {
        try {
            DB::beginTransaction(); // Start transaction for atomic operation

            // Find the contact to delete
            $contact = Contact::where('id', Crypt::decrypt($id))
                ->where('company_id', Auth::user()->company_id)
                ->first();

            // Check if the contact exists
            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact not found or does not belong to the current company.'
                ], 404);
            }

            // Delete the contact
            $contact->delete();

            DB::commit(); // Commit transaction
            return response()->json(['success' => true, 'message' => 'Contact deleted successfully.'], 200);
        } catch (Exception $e) {
            DB::rollBack(); // Rollback if there's a general error
            Log::error('Error deleting contact: ' . $e->getMessage());

            // Return a structured error message
            return response()->json([
                'success' => false,
                'message' => 'Internal server error. Please try again.', // General message
                'error' => $e->getMessage() // Include the exception message for debugging (optional)
            ], 500);
        }
    }

    // Import data from a file
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:2048', // Max 2MB file size
            ]);

            // Process the uploaded file
            $file = $request->file('file');
            $path = $file->storeAs('imports', $file->getClientOriginalName(), 'local');

            // Process the file data
            // You can use a package like Maatwebsite/Laravel-Excel to read the file
            // https://docs.laravel-excel.com/3.1/getting-started/
            // $data = Excel::toCollection(new LeadImport, $path);

            // Process the data and save to the database
            // foreach ($data as $row) {
            //     Lead::create([
            //         'name' => $row['name'],
            //         'email' => $row['email'],
            //         'phone' => $row['phone'],
            //         'brief' => $row['brief'],
            //         'lead_source' => $row['source'],
            //         'status_id' => $row['status'],
            //         'company_id' => Auth::user()->company_id,
            //         'created_by' => Auth::user()->id,
            //     ]);
            // }

            // Return a success message
            return response()->json(['success' => true, 'message' => 'Leads imported successfully.'], 200);
        } catch (ValidationException $e) {
            Log::error('Validation errors importing leads', ['errors' => $e->validator->errors()]);

            // Return validation errors in a structured format
            return response()->json([
                'success' => false,
                'message' => 'Validation errors occurred.', // General message
                'errors' => $e->validator->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error importing leads: ' . $e->getMessage());

            // Return a structured error message
            return response()->json([
                'success' => false,
                'message' => 'Internal server error. Please try again.', // General message
                'error' => $e->getMessage() // Include the exception message for debugging (optional)
            ], 500);
        }
    }

    // Export data to a file
    public function export()
    {
        try {
            // Get the leads data
            $leads = Lead::where('company_id', Auth::user()->company_id)->get();

            // Generate the export file
            // You can use a package like Maatwebsite/Laravel-Excel to export the data
            // https://docs.laravel-excel.com/3.1/getting-started/
            // Excel::store(new LeadExport($leads), 'exports/leads.xlsx');

            // Return a success message
            return response()->json(['success' => true, 'message' => 'Leads exported successfully.'], 200);
        } catch (Exception $e) {
            Log::error('Error exporting leads: ' . $e->getMessage());

            // Return a structured error message
            return response()->json([
                'success' => false,
                'message' => 'Internal server error. Please try again.', // General message
                'error' => $e->getMessage() // Include the exception message for debugging (optional)
            ], 500);
        }
    }

    public function manage(Request $request, $id)
    {
        try {
            $row = Lead::where('company_id', Auth::user()->company_id)->where('id', Crypt::decrypt($id))->first();
            if (!$row) {
                return view('errors.404');
            }
            return view('admin.leads.leadManage.index', compact('row'));
        }catch (Exception $e) {
            return view('errors.500');
        }
    }
}
