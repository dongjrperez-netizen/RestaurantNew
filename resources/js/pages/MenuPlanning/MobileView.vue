<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { ArrowLeft, Clock, Users, Utensils, AlertCircle, Plus, ShoppingCart, Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  price?: number;
  image_url?: string;
  allergens?: string[];
  dietary_tags?: string[];
  calories?: number;
  preparation_time?: number;
  planned_quantity: number;
  meal_type?: string;
  notes?: string;
}

interface MenuPlan {
  menu_plan_id: number;
  plan_name: string;
  plan_type: 'daily' | 'weekly';
  start_date: string;
  end_date: string;
  description?: string;
  is_active: boolean;
}

interface Props {
  menuPlan: MenuPlan;
  selectedDate: string;
  dishesByCategory: Record<string, Dish[]>;
  totalDishes: number;
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getMealTypeColor = (mealType?: string) => {
  switch (mealType) {
    case 'breakfast':
      return 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200';
    case 'lunch':
      return 'bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200';
    case 'dinner':
      return 'bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-200';
    case 'snack':
      return 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200';
    default:
      return 'bg-muted text-muted-foreground';
  }
};

const totalQuantity = computed(() => {
  return Object.values(props.dishesByCategory).flat().reduce((sum, dish) => sum + dish.planned_quantity, 0);
});

const categoryCount = computed(() => {
  return Object.keys(props.dishesByCategory).length;
});

const sortedCategories = computed(() => {
  return Object.keys(props.dishesByCategory).sort();
});

// Order management
interface OrderItem {
  dish_id: number;
  dish_name: string;
  price: number;
  quantity: number;
  notes?: string;
}

const orders = ref<OrderItem[]>([]);
const showOrderDialog = ref(false);

const addToOrder = (dish: Dish) => {
  const existingOrderIndex = orders.value.findIndex(item => item.dish_id === dish.dish_id);

  if (existingOrderIndex >= 0) {
    // Increase quantity if item already exists
    orders.value[existingOrderIndex].quantity += 1;
  } else {
    // Add new item to order
    orders.value.push({
      dish_id: dish.dish_id,
      dish_name: dish.dish_name,
      price: dish.price || 0,
      quantity: 1,
      notes: ''
    });
  }
};

const removeFromOrder = (dishId: number) => {
  const orderIndex = orders.value.findIndex(item => item.dish_id === dishId);
  if (orderIndex >= 0) {
    if (orders.value[orderIndex].quantity > 1) {
      orders.value[orderIndex].quantity -= 1;
    } else {
      orders.value.splice(orderIndex, 1);
    }
  }
};

const getTotalOrderAmount = computed(() => {
  return orders.value.reduce((total, item) => total + (item.price * item.quantity), 0);
});

const getTotalOrderItems = computed(() => {
  return orders.value.reduce((total, item) => total + item.quantity, 0);
});

const clearOrder = () => {
  orders.value = [];
};
</script>

<template>
  <Head :title="`Menu for ${formatDate(selectedDate)}`" />

  <!-- Mobile-First Layout without Sidebar -->
  <div class="min-h-screen bg-background">
    <!-- Header -->
    <header class="bg-card shadow-sm border-b sticky top-0 z-10">
      <div class="px-4 py-3 max-w-lg mx-auto">
        <div class="flex items-center justify-between">
          <Link :href="`/menu-planning/${menuPlan.menu_plan_id}`">
            <Button variant="ghost" size="sm" class="p-2">
              <ArrowLeft class="w-5 h-5" />
            </Button>
          </Link>
          <div class="text-center flex-1 mx-3">
            <h1 class="text-lg font-semibold text-foreground truncate">Today's Menu</h1>
            <p class="text-sm text-muted-foreground">{{ formatDate(selectedDate) }}</p>
          </div>
          <div class="flex items-center gap-2">
            <Badge :variant="menuPlan.is_active ? 'default' : 'secondary'" class="text-xs">
              {{ menuPlan.is_active ? 'Active' : 'Inactive' }}
            </Badge>
            <!-- View Orders Button -->
            <Dialog v-model:open="showOrderDialog">
              <DialogTrigger asChild>
                <Button variant="outline" size="sm" class="relative p-2">
                  <ShoppingCart class="w-4 h-4" />
                  <span v-if="getTotalOrderItems > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {{ getTotalOrderItems }}
                  </span>
                </Button>
              </DialogTrigger>
              <DialogContent class="max-w-md">
                <DialogHeader>
                  <DialogTitle>Customer Orders</DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                  <div v-if="orders.length === 0" class="text-center py-8 text-muted-foreground">
                    <ShoppingCart class="w-12 h-12 mx-auto mb-3 opacity-50" />
                    <p>No orders yet</p>
                  </div>
                  <div v-else>
                    <div v-for="order in orders" :key="order.dish_id" class="flex items-center justify-between p-3 bg-muted rounded-lg">
                      <div class="flex-1">
                        <h4 class="font-medium text-sm">{{ order.dish_name }}</h4>
                        <p class="text-xs text-muted-foreground">₱{{ order.price }} each</p>
                      </div>
                      <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="removeFromOrder(order.dish_id)" class="w-8 h-8 p-0">
                          -
                        </Button>
                        <span class="w-8 text-center">{{ order.quantity }}</span>
                        <Button variant="outline" size="sm" @click="addToOrder({ dish_id: order.dish_id, dish_name: order.dish_name, price: order.price, planned_quantity: 1 })" class="w-8 h-8 p-0">
                          +
                        </Button>
                      </div>
                    </div>
                    <div class="border-t pt-4">
                      <div class="flex justify-between items-center font-semibold">
                        <span>Total:</span>
                        <span>₱{{ getTotalOrderAmount.toFixed(2) }}</span>
                      </div>
                      <div class="flex gap-2 mt-4">
                        <Button variant="outline" @click="clearOrder" class="flex-1">
                          Clear All
                        </Button>
                        <Button class="flex-1 bg-orange-600 hover:bg-orange-700">
                          Place Order
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          </div>
        </div>
      </div>
    </header>


    <!-- Menu by Categories -->
    <div class="px-4 pb-6 max-w-lg mx-auto space-y-4">
      <div v-if="Object.keys(dishesByCategory).length === 0" class="text-center py-12">
        <div class="text-muted-foreground mb-2">
          <Utensils class="w-12 h-12 mx-auto mb-3 opacity-50" />
          <p class="text-lg font-medium">No dishes planned</p>
          <p class="text-sm">No dishes are planned for this date.</p>
        </div>
      </div>

      <div v-for="category in sortedCategories" :key="category" class="space-y-3">
        <!-- Category Header -->
        <div class="flex items-center gap-2 px-1">
          <h2 class="text-lg font-semibold text-foreground">{{ category }}</h2>
        </div>

        <!-- Dishes in Category -->
        <div class="grid grid-cols-2 gap-3">
          <div
            v-for="dish in dishesByCategory[category]"
            :key="dish.dish_id"
            class="bg-card rounded-xl shadow-sm border border-border overflow-hidden"
          >
            <!-- Card Layout -->
            <div class="flex flex-col h-full">
              <!-- Dish Image with overlays -->
              <div class="relative" style="height: 140px;">
                <img
                  v-if="dish.image_url"
                  :src="dish.image_url"
                  :alt="dish.dish_name"
                  class="w-full h-full object-cover"
                />
                <div
                  v-else
                  class="w-full h-full bg-muted flex items-center justify-center"
                >
                  <Utensils class="w-12 h-12 text-muted-foreground/50" />
                </div>

                <!-- Price Badge (Top Right) -->
                <div v-if="dish.price" class="absolute top-2 right-2">
                  <div class="bg-orange-600 text-white px-2 py-1 rounded-md text-sm font-bold shadow-sm">
                    ₱{{ dish.price }}
                  </div>
                </div>

                <!-- Meal Type Badge (Top Left) -->
                <div v-if="dish.meal_type" class="absolute top-2 left-2">
                  <Badge :class="getMealTypeColor(dish.meal_type)" class="text-xs">
                    {{ dish.meal_type }}
                  </Badge>
                </div>

                <!-- Dish Name (Bottom overlay) -->
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-3">
                  <h3 class="font-semibold text-white text-sm leading-tight overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                    {{ dish.dish_name }}
                  </h3>
                </div>
              </div>

              <!-- Additional Info (Bottom section) -->
              <div class="p-3 space-y-2">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                  <div v-if="dish.preparation_time" class="flex items-center gap-1">
                    <Clock class="w-3 h-3" />
                    <span>{{ dish.preparation_time }}m</span>
                  </div>
                  <div v-if="dish.calories">
                    <span>{{ dish.calories }} cal</span>
                  </div>
                </div>
                <!-- Add to Order Button -->
                <Button
                  @click="addToOrder(dish)"
                  size="sm"
                  class="w-full bg-orange-600 hover:bg-orange-700 text-white text-xs py-1 h-7"
                >
                  <Plus class="w-3 h-3 mr-1" />
                  Add to Order
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-4 py-6 max-w-lg mx-auto">
      <div class="text-center text-sm text-muted-foreground">
        <p>{{ menuPlan.plan_name }}</p>
        <p>{{ formatDate(menuPlan.start_date) }} - {{ formatDate(menuPlan.end_date) }}</p>
      </div>
    </div>
  </div>
</template>