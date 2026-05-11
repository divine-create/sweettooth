<div class="min-h-screen bg-zinc-50">

    {{-- Header --}}
    <header class="bg-white border-b border-zinc-200 sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-zinc-900">
                    {{ \App\Helpers\Settings::businessConfiguration('business_name', config('app.name')) }}
                </h1>
                <p class="text-sm text-zinc-500">{{ $branch->name }} — Place an Order</p>
            </div>
            @if(count($this->cart) > 0)
            <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-full px-4 py-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-sm font-semibold text-emerald-700">{{ count($this->cart) }} item(s)</span>
            </div>
            @endif
        </div>
    </header>

    @if($orderPlaced)
        {{-- Confirmation Screen --}}
        <div class="max-w-md mx-auto px-4 py-16 text-center">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-zinc-900 mb-2">Order Received!</h2>
            <p class="text-zinc-600 mb-6">
                Your order <span class="font-semibold text-zinc-900">{{ $confirmedOrderNumber }}</span> has been submitted.
                Our team will review it and get back to you shortly.
            </p>
            <div class="bg-white border border-zinc-200 rounded-xl p-5 text-left text-sm text-zinc-700 space-y-3">
                <p class="font-semibold text-zinc-900">What happens next?</p>
                <div class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                    <p>Our production team reviews your order.</p>
                </div>
                <div class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                    <p>You'll be contacted via phone or email once it's approved.</p>
                </div>
                <div class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                    <p>Production begins and we'll notify you when it's ready.</p>
                </div>
            </div>
            <button wire:click="$set('orderPlaced', false)" class="mt-6 text-emerald-600 hover:text-emerald-700 text-sm font-medium underline">
                Place another order
            </button>
        </div>
    @else
        <div class="max-w-5xl mx-auto px-4 py-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Product Listing --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-900">Available Products</h2>
                    <span class="text-sm text-zinc-400">{{ $products->count() }} item(s)</span>
                </div>

                {{-- Search --}}
                <div class="relative">
                    <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input wire:model.live.debounce.300ms="search"
                           type="text"
                           placeholder="Search products…"
                           class="w-full pl-9 pr-10 py-2.5 bg-white border border-zinc-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition">
                    @if($search)
                    <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    @endif
                </div>

                @if($products->isEmpty())
                    <div class="bg-white rounded-xl border border-zinc-200 p-10 text-center text-zinc-400">
                        <svg class="w-10 h-10 mx-auto mb-3 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                        </svg>
                        @if($search)
                            <p class="font-medium">No results for "{{ $search }}"</p>
                            <button wire:click="$set('search', '')" class="text-xs text-emerald-600 hover:underline mt-1">Clear search</button>
                        @else
                            <p class="font-medium">No products available</p>
                            <p class="text-xs mt-1">Check back soon or contact us directly.</p>
                        @endif
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($products as $product)
                        @php
                            $initial = strtoupper(substr($product->name, 0, 1));
                            $colors = [
                                'A' => 'bg-rose-100 text-rose-700',
                                'B' => 'bg-orange-100 text-orange-700',
                                'C' => 'bg-amber-100 text-amber-700',
                                'D' => 'bg-yellow-100 text-yellow-700',
                                'E' => 'bg-lime-100 text-lime-700',
                                'F' => 'bg-green-100 text-green-700',
                                'G' => 'bg-emerald-100 text-emerald-700',
                                'H' => 'bg-teal-100 text-teal-700',
                                'I' => 'bg-cyan-100 text-cyan-700',
                                'J' => 'bg-sky-100 text-sky-700',
                                'K' => 'bg-blue-100 text-blue-700',
                                'L' => 'bg-indigo-100 text-indigo-700',
                                'M' => 'bg-violet-100 text-violet-700',
                                'N' => 'bg-purple-100 text-purple-700',
                                'O' => 'bg-fuchsia-100 text-fuchsia-700',
                                'P' => 'bg-pink-100 text-pink-700',
                                'Q' => 'bg-rose-100 text-rose-700',
                                'R' => 'bg-red-100 text-red-700',
                                'S' => 'bg-orange-100 text-orange-700',
                                'T' => 'bg-amber-100 text-amber-700',
                                'U' => 'bg-green-100 text-green-700',
                                'V' => 'bg-teal-100 text-teal-700',
                                'W' => 'bg-sky-100 text-sky-700',
                                'X' => 'bg-blue-100 text-blue-700',
                                'Y' => 'bg-indigo-100 text-indigo-700',
                                'Z' => 'bg-violet-100 text-violet-700',
                            ];
                            $colorClass = $colors[$initial] ?? 'bg-zinc-100 text-zinc-600';
                            $inCart = isset($this->cart[$product->id]);
                        @endphp
                        <div class="bg-white rounded-xl border {{ $inCart ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-zinc-200' }} px-4 py-3 flex items-center gap-4 transition-all">

                            {{-- Monogram avatar --}}
                            <div class="w-11 h-11 rounded-xl {{ $colorClass }} flex items-center justify-center font-bold text-lg flex-shrink-0">
                                {{ $initial }}
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-zinc-900 leading-tight">{{ $product->name }}</p>
                                @if($product->description)
                                <p class="text-xs text-zinc-400 mt-0.5 truncate">{{ $product->description }}</p>
                                @endif
                                <p class="text-sm font-bold text-emerald-700 mt-1">₦{{ number_format((float)$product->price, 2) }}</p>
                            </div>

                            {{-- Cart controls --}}
                            @if($inCart)
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button wire:click="updateQty('{{ $product->id }}', {{ $this->cart[$product->id] - 1 }})"
                                            class="w-8 h-8 rounded-full bg-zinc-100 hover:bg-zinc-200 flex items-center justify-center text-zinc-700 font-bold text-lg leading-none transition">
                                        −
                                    </button>
                                    <span class="text-sm font-bold w-5 text-center text-zinc-900">{{ $this->cart[$product->id] }}</span>
                                    <button wire:click="addToCart('{{ $product->id }}')"
                                            class="w-8 h-8 rounded-full bg-emerald-600 hover:bg-emerald-700 flex items-center justify-center text-white font-bold text-lg leading-none transition">
                                        +
                                    </button>
                                </div>
                            @else
                                <button wire:click="addToCart('{{ $product->id }}')"
                                        class="flex-shrink-0 px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition">
                                    Add
                                </button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Order Summary + Form --}}
            <div class="space-y-4">

                {{-- Cart --}}
                @if(count($this->cart) > 0)
                <div class="bg-white rounded-xl border border-zinc-200 p-4">
                    <h3 class="font-semibold text-zinc-900 mb-3">Your Order</h3>
                    <div class="space-y-0 divide-y divide-zinc-100">
                        @foreach($cartProducts as $product)
                        @php $qty = $this->cart[$product->id] ?? 0; @endphp
                        <div class="flex items-center justify-between py-2.5">
                            <div class="flex-1 min-w-0 pr-2">
                                <p class="text-sm font-medium text-zinc-800 truncate">{{ $product->name }}</p>
                                <p class="text-xs text-zinc-400 mt-0.5">₦{{ number_format((float)$product->price, 2) }} × {{ $qty }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-zinc-900">₦{{ number_format((float)$product->price * $qty, 2) }}</span>
                                <button wire:click="removeFromCart('{{ $product->id }}')" class="text-zinc-300 hover:text-red-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-2 pt-3 border-t border-zinc-200 flex justify-between items-center">
                        <span class="text-sm font-semibold text-zinc-600">Total</span>
                        <span class="text-lg font-bold text-zinc-900">₦{{ number_format($this->getCartTotal(), 2) }}</span>
                    </div>
                </div>
                @endif

                {{-- Contact Form --}}
                <div class="bg-white rounded-xl border border-zinc-200 p-4">
                    <h3 class="font-semibold text-zinc-900 mb-3">Your Details</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-zinc-600 mb-1">Full Name *</label>
                            <input wire:model="customerName" type="text" placeholder="John Doe"
                                   class="w-full border border-zinc-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition">
                            @error('customerName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-600 mb-1">Email *</label>
                            <input wire:model="customerEmail" type="email" placeholder="you@example.com"
                                   class="w-full border border-zinc-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition">
                            @error('customerEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-600 mb-1">Phone *</label>
                            <input wire:model="customerPhone" type="tel" placeholder="+234 800 000 0000"
                                   class="w-full border border-zinc-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition">
                            @error('customerPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-600 mb-1">Notes <span class="text-zinc-400">(optional)</span></label>
                            <textarea wire:model="notes" rows="2" placeholder="Any special instructions…"
                                      class="w-full border border-zinc-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition resize-none"></textarea>
                        </div>
                    </div>

                    @error('cart') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror

                    <button wire:click="submitOrder"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-60 cursor-not-allowed"
                            class="mt-4 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                        <span wire:loading.remove wire:target="submitOrder">Place Order</span>
                        <span wire:loading wire:target="submitOrder">Submitting…</span>
                    </button>
                </div>

                <p class="text-xs text-center text-zinc-400 px-2">
                    By placing an order you agree to be contacted by our team to confirm the details.
                </p>
            </div>
        </div>
    @endif
</div>
