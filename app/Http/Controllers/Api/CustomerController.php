<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view_environments'), 403);

        return CustomerResource::collection(Customer::query()->orderBy('name')->paginate(100))->response();
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        return CustomerResource::make(Customer::query()->create($request->validated()))->response()->setStatusCode(201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return CustomerResource::make($customer->refresh())->response();
    }
}
