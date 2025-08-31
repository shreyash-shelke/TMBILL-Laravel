<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;


class CustomerController extends Controller
{
    


public function index(Request $request)
    {
        $query = Customer::query();

        
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        
        $customers = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
                'last_page'    => $customers->lastPage(),
            ]
        ], 200);
    }



    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => [
                'required',
                'digits:10',
                'regex:/^[7-9][0-9]{9}$/',
                'unique:customers,phone'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $customer = Customer::create($request->only(['name', 'email', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data'    => $customer
        ], 201);
    }

    
    public function show($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $customer
        ], 200);
    }

    
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'  => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:customers,email,' . $customer->id,
            'phone' => [
                'sometimes',
                'required',
                'digits:10',
                'regex:/^[7-9][0-9]{9}$/',
                'unique:customers,phone,' . $customer->id
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $customer->update($request->only(['name', 'email', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data'    => $customer
        ], 200);
    }

    
    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ], 200);
    }
public function import(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|mimes:csv,txt|max:2048',
    ]);

    if ($validator->fails()) {
        Log::error("Import failed - validation errors", $validator->errors()->toArray());
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $file = $request->file('file');
    $handle = fopen($file->getPathname(), "r");

    $header = fgetcsv($handle); 
    $imported = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $name  = $row[0] ?? null;
        $email = $row[1] ?? null;
        $phone = $row[2] ?? null;

        if ($name && $email && $phone) {
            $exists = Customer::where('email', $email)->orWhere('phone', $phone)->first();
            if (!$exists) {
                Customer::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                ]);
                $imported++;
            }
        }
    }

    fclose($handle);

    Log::info("Import completed - {$imported} customers imported.");

    return response()->json([
        'success' => true,
        'message' => "{$imported} customers imported successfully",
    ], 200);
}

public function export()
{
    $customers = Customer::all(['id','name','email','phone']);
    $filename = "customers_" . date('Y-m-d_H-i-s') . ".csv";

    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, ['ID', 'Name', 'Email', 'Phone']);

    foreach ($customers as $customer) {
        fputcsv($handle, [$customer->id, $customer->name, $customer->email, $customer->phone]);
    }

    rewind($handle);
    $csvOutput = stream_get_contents($handle);
    fclose($handle);

    Log::info("Export completed - " . count($customers) . " customers exported.");

    return response($csvOutput, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename={$filename}",
    ]);
}

    
}
