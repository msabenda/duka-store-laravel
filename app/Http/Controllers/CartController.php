<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $products = config('catalog');
        $total = 0;

        // Build cart items with product details
        $items = [];
        foreach ($cart as $item) {
            $product = collect($products)->firstWhere('id', $item['product_id']);
            if ($product) {
                $subtotal = $product['price'] * $item['quantity'];
                $total += $subtotal;
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ];
            }
        }

        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('cart.index', compact('items', 'total', 'cartCount'));
    }

    public function add(int $productId)
    {
        $products = config('catalog');
        $product = collect($products)->firstWhere('id', $productId);

        if (!$product) {
            return redirect('/')->with('error', 'Product not found.');
        }

        $cart = session()->get('cart', []);

        // Check if product already in cart
        $found = false;
        foreach ($cart as $i => $item) {
            if ($item['product_id'] === $productId) {
                $cart[$i]['quantity']++;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $cart[] = [
                'product_id' => $productId,
                'quantity' => 1,
            ];
        }

        session()->put('cart', $cart);

        return redirect('/')->with('success', 'Item added to cart.');
    }

    public function remove(int $index)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$index])) {
            array_splice($cart, $index, 1);
            session()->put('cart', $cart);
        }

        return redirect('/cart')->with('success', 'Item removed from cart.');
    }
}
