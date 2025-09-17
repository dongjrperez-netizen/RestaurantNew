<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Plus, Minus, ShoppingCart, AlertTriangle } from 'lucide-vue-next';

interface DishPricing {
  pricing_id: number;
  price_type: string;
  base_price: number;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  image_url?: string;
  status: string;
  category?: {
    category_name: string;
  };
  pricing: DishPricing[];
}

interface CartItem {
  dish_id: number;
  dish_name: string;
  unit_price: number;
  quantity: number;
  special_instructions?: string;
}

interface Props {
  dishes: Dish[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Orders', href: '/orders' },
  { title: 'Create Order', href: '/orders/create' },
];

const cart = ref<CartItem[]>([]);
const selectedDish = ref<number | null>(null);
const quantity = ref(1);
const specialInstructions = ref('');
const inventoryWarnings = ref<string[]>([]);

const form = useForm({
  order_type: 'dine_in',
  customer_name: '',
  customer_phone: '',
  customer_address: '',
  table_number: '',
  notes: '',
  items: [] as Array<{
    dish_id: number;
    quantity: number;
    special_instructions?: string;
  }>,
  tax_amount: 0,
  discount_amount: 0,
  delivery_fee: 0,
});

const availableDishes = computed(() => {
  return props.dishes.filter(dish => dish.status === 'active');
});

const cartTotal = computed(() => {
  return cart.value.reduce((total, item) => total + (item.unit_price * item.quantity), 0);
});

const finalTotal = computed(() => {
  return cartTotal.value + form.tax_amount + form.delivery_fee - form.discount_amount;
});

const getDishPrice = (dish: Dish, orderType: string): number => {
  const pricing = dish.pricing.find(p => p.price_type === orderType);
  return pricing ? pricing.base_price : 0;
};

const addToCart = async () => {
  if (!selectedDish.value || quantity.value <= 0) return;

  const dish = availableDishes.value.find(d => d.dish_id === selectedDish.value);
  if (!dish) return;

  // Check inventory availability
  try {
    const response = await fetch('/orders/check-inventory', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        dish_id: selectedDish.value,
        quantity: quantity.value,
      }),
    });

    const result = await response.json();

    if (!result.success) {
      inventoryWarnings.value = [result.message];
      return;
    }

    if (!result.data.can_fulfill) {
      const missingIngredients = result.data.ingredients
        .filter((ing: any) => !ing.is_available)
        .map((ing: any) => `${ing.ingredient_name} (need ${ing.required_quantity}, have ${ing.current_stock})`)
        .join(', ');

      inventoryWarnings.value = [`Insufficient inventory for ${dish.dish_name}: ${missingIngredients}`];
      return;
    }

    // Clear warnings if inventory check passed
    inventoryWarnings.value = [];

    // Add to cart
    const existingItemIndex = cart.value.findIndex(item =>
      item.dish_id === selectedDish.value &&
      item.special_instructions === specialInstructions.value
    );

    if (existingItemIndex !== -1) {
      cart.value[existingItemIndex].quantity += quantity.value;
    } else {
      cart.value.push({
        dish_id: selectedDish.value,
        dish_name: dish.dish_name,
        unit_price: getDishPrice(dish, form.order_type),
        quantity: quantity.value,
        special_instructions: specialInstructions.value || undefined,
      });
    }

    // Reset form
    selectedDish.value = null;
    quantity.value = 1;
    specialInstructions.value = '';
  } catch (error) {
    inventoryWarnings.value = ['Error checking inventory. Please try again.'];
  }
};

const removeFromCart = (index: number) => {
  cart.value.splice(index, 1);
};

const updateCartQuantity = (index: number, newQuantity: number) => {
  if (newQuantity <= 0) {
    removeFromCart(index);
  } else {
    cart.value[index].quantity = newQuantity;
  }
};

const submitOrder = () => {
  if (cart.value.length === 0) {
    alert('Please add items to your order');
    return;
  }

  form.items = cart.value.map(item => ({
    dish_id: item.dish_id,
    quantity: item.quantity,
    special_instructions: item.special_instructions,
  }));

  form.post('/orders', {
    onSuccess: () => {
      // Success handled by redirect
    },
    onError: () => {
      // Errors will be displayed
    }
  });
};
</script>

<template>
  <Head title="Create Order" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Create New Order</h1>
        <p class="text-muted-foreground">Add items to cart and create customer order</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Add Items -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Order Type & Customer Info -->
          <Card>
            <CardHeader>
              <CardTitle>Order Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="order_type">Order Type *</Label>
                  <Select v-model="form.order_type">
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="dine_in">Dine In</SelectItem>
                      <SelectItem value="takeout">Takeout</SelectItem>
                      <SelectItem value="delivery">Delivery</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="space-y-2">
                  <Label for="customer_name">Customer Name *</Label>
                  <Input
                    id="customer_name"
                    v-model="form.customer_name"
                    placeholder="Enter customer name"
                    :class="{ 'border-red-500': form.errors.customer_name }"
                  />
                  <p v-if="form.errors.customer_name" class="text-sm text-red-500">
                    {{ form.errors.customer_name }}
                  </p>
                </div>

                <div class="space-y-2">
                  <Label for="customer_phone">Phone Number</Label>
                  <Input
                    id="customer_phone"
                    v-model="form.customer_phone"
                    placeholder="Enter phone number"
                  />
                </div>

                <div v-if="form.order_type === 'dine_in'" class="space-y-2">
                  <Label for="table_number">Table Number *</Label>
                  <Input
                    id="table_number"
                    v-model="form.table_number"
                    placeholder="Enter table number"
                    :class="{ 'border-red-500': form.errors.table_number }"
                  />
                  <p v-if="form.errors.table_number" class="text-sm text-red-500">
                    {{ form.errors.table_number }}
                  </p>
                </div>

                <div v-if="form.order_type === 'delivery'" class="md:col-span-2 space-y-2">
                  <Label for="customer_address">Delivery Address *</Label>
                  <Textarea
                    id="customer_address"
                    v-model="form.customer_address"
                    placeholder="Enter delivery address"
                    :class="{ 'border-red-500': form.errors.customer_address }"
                  />
                  <p v-if="form.errors.customer_address" class="text-sm text-red-500">
                    {{ form.errors.customer_address }}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Add Items -->
          <Card>
            <CardHeader>
              <CardTitle>Add Items to Order</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Inventory Warnings -->
              <div v-if="inventoryWarnings.length > 0" class="space-y-2">
                <div
                  v-for="warning in inventoryWarnings"
                  :key="warning"
                  class="flex items-center p-3 bg-yellow-50 border border-yellow-200 rounded-lg"
                >
                  <AlertTriangle class="w-5 h-5 text-yellow-600 mr-2" />
                  <span class="text-yellow-800 text-sm">{{ warning }}</span>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="space-y-2">
                  <Label for="dish_select">Select Dish</Label>
                  <Select v-model="selectedDish">
                    <SelectTrigger>
                      <SelectValue placeholder="Choose a dish" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="dish in availableDishes"
                        :key="dish.dish_id"
                        :value="dish.dish_id.toString()"
                      >
                        {{ dish.dish_name }} - ₱{{ getDishPrice(dish, form.order_type).toFixed(2) }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="space-y-2">
                  <Label for="quantity">Quantity</Label>
                  <Input
                    id="quantity"
                    v-model="quantity"
                    type="number"
                    min="1"
                    placeholder="1"
                  />
                </div>

                <div class="space-y-2">
                  <Label for="special_instructions">Special Instructions</Label>
                  <Input
                    id="special_instructions"
                    v-model="specialInstructions"
                    placeholder="Optional notes"
                  />
                </div>

                <div class="flex items-end">
                  <Button @click="addToCart" class="w-full" :disabled="!selectedDish">
                    <Plus class="w-4 h-4 mr-2" />
                    Add to Order
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="space-y-6">
          <!-- Cart -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center">
                <ShoppingCart class="w-5 h-5 mr-2" />
                Order Items ({{ cart.length }})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="cart.length === 0" class="text-center py-8 text-muted-foreground">
                <ShoppingCart class="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>No items in cart</p>
                <p class="text-sm">Add dishes from the menu</p>
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="(item, index) in cart"
                  :key="`${item.dish_id}-${index}`"
                  class="flex items-center justify-between p-3 border rounded-lg"
                >
                  <div class="flex-1">
                    <div class="font-medium">{{ item.dish_name }}</div>
                    <div v-if="item.special_instructions" class="text-xs text-muted-foreground">
                      Note: {{ item.special_instructions }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                      ₱{{ item.unit_price.toFixed(2) }} each
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Button
                      variant="outline"
                      size="sm"
                      @click="updateCartQuantity(index, item.quantity - 1)"
                    >
                      <Minus class="w-3 h-3" />
                    </Button>
                    <span class="w-8 text-center">{{ item.quantity }}</span>
                    <Button
                      variant="outline"
                      size="sm"
                      @click="updateCartQuantity(index, item.quantity + 1)"
                    >
                      <Plus class="w-3 h-3" />
                    </Button>
                    <div class="w-16 text-right font-medium">
                      ₱{{ (item.unit_price * item.quantity).toFixed(2) }}
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Order Total -->
          <Card>
            <CardHeader>
              <CardTitle>Order Summary</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span>Subtotal:</span>
                  <span>₱{{ cartTotal.toFixed(2) }}</span>
                </div>

                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <Label for="tax_amount">Tax:</Label>
                    <Input
                      id="tax_amount"
                      v-model="form.tax_amount"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-24 text-right"
                    />
                  </div>

                  <div class="flex items-center justify-between">
                    <Label for="discount_amount">Discount:</Label>
                    <Input
                      id="discount_amount"
                      v-model="form.discount_amount"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-24 text-right"
                    />
                  </div>

                  <div v-if="form.order_type === 'delivery'" class="flex items-center justify-between">
                    <Label for="delivery_fee">Delivery Fee:</Label>
                    <Input
                      id="delivery_fee"
                      v-model="form.delivery_fee"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-24 text-right"
                    />
                  </div>
                </div>

                <div class="border-t pt-2">
                  <div class="flex justify-between font-bold text-lg">
                    <span>Total:</span>
                    <span>₱{{ finalTotal.toFixed(2) }}</span>
                  </div>
                </div>
              </div>

              <div class="space-y-2">
                <Label for="notes">Order Notes</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  placeholder="Additional notes for this order"
                />
              </div>

              <Button
                @click="submitOrder"
                class="w-full"
                :disabled="cart.length === 0 || form.processing"
              >
                {{ form.processing ? 'Creating Order...' : 'Create Order' }}
              </Button>

              <p v-if="form.errors.inventory" class="text-sm text-red-500">
                {{ form.errors.inventory }}
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>