<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tax_price' => 'required',
            'shipping_price' => 'required',
            'total_price' => 'required',
            'payment_method' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $user  = auth()->user();

        $order = Order::create(array_merge(
            $validator->validated(),
            ['user_id' => $user->id]
        ));

        foreach ($request->orderItems as $item) {
            DB::table('order_items')->insert([
                'order_id' => $order->id,
                'product_slug' => $item['product'],
                'name' => $item['name'],
                'image' => $item['image'],
                'price' => $item['price'],
                'qty' => $item['qty'],
            ]);
        }

        $shipping = $request->shippingAddress;
        DB::table('order_addresses')->insert([
            'order_id' => $order->id,
            'address' => $shipping['address'],
            'city' => $shipping['city'],
            'country' => $shipping['country'],
            'postal_code' => $shipping['postal_code'],
        ]);

        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = DB::table('orders')
            ->join('order_addresses', 'order_addresses.order_id', '=', 'orders.id')
            ->where('orders.id', $id)
            ->first();

        if (!$order) {
            return response()->json(["message" => 'Order Not Found'], 404);
        }
        $order->orderItems = DB::table('order_items')
            ->where('order_id', $id)
            ->get();
        $user = auth()->user();
        if ($user->id != $order->user_id) {
            return response()->json(["message" => 'Order Not Found'], 404);
        }
        $order->user = $user;
        return response()->json($order, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required',
            'update_time' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $order = Order::findOrFail($id);

        if ($order) {
            $order->is_paid = true;
            $order->paid_at = Carbon::now();
            $order->save();

            DB::table('order_payments')->insert(array_merge(
                $validator->validated(),
                [
                    'order_id' => $order->id,
                    'email_address' => $request->payer['email_address']
                ]
            ));
            return response()->json($order, 200);
        }
        return response()->json(['message' => 'Something went wrong'], 400);
    }

    /**
     * Get loggined user orders
     *
     * @route GET /api/orders/userorders
     * @param null
     * @return json
     */
    public function myOrders()
    {
        $user = auth()->user();
        $order = Order::where('user_id', '=', $user->id)->get();

        return response()->json($order, 200);
    }

    /**
     * Update deliver.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateDeliver(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order) {
            $order->is_delivered = true;
            $order->delivered_at = Carbon::now();
            $order->save();

            return response()->json($order, 200);
        }
        return response()->json(['message' => 'Something went wrong'], 400);
    }
}
