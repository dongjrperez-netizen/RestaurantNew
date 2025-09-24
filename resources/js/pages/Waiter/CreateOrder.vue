<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import { Plus, Minus, Users, Clock, AlertTriangle, ChefHat } from 'lucide-vue-next';

interface Table {
  id: number;
  table_number: string;
  table_name: string;
  seats: number;
  status: string;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description: string;
  price: number;
  allergens?: string[];
  dietary_tags?: string[];
  preparation_time: number;
  is_available: boolean;
  category: {
    category_id: number;
    category_name: string;
  };
}

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  email: string;
  role: {
    role_name: string;
  };
}

interface OrderItem {
  dish_id: number;
  dish: Dish;
  quantity: number;
  special_instructions: string;
}

interface Props {
  table: Table;
  dishes: Dish[];
  employee: Employee;
}

const props = defineProps<Props>();

const orderForm = useForm({
  table_id: props.table.id,
  customer_name: '',
  notes: '',
  order_items: [] as OrderItem[],
});

const orderItems = ref<OrderItem[]>([]);
const searchQuery = ref('');
const selectedCategory = ref<number | null>(null);
const selectedDish = ref<Dish | null>(null);
const showQuantityModal = ref(false);
const modalQuantity = ref(1);
const modalSpecialInstructions = ref('');

const categories = computed(() => {
  const categoryMap = new Map();
  props.dishes.forEach(dish => {
    if (dish.category && !categoryMap.has(dish.category.category_id)) {
      categoryMap.set(dish.category.category_id, dish.category);
    }
  });
  return Array.from(categoryMap.values());
});

const filteredDishes = computed(() => {
  return props.dishes.filter(dish => {
    if (!dish.is_available) return false;

    const matchesSearch = dish.dish_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         dish.description.toLowerCase().includes(searchQuery.value.toLowerCase());

    const matchesCategory = selectedCategory.value === null ||
                          dish.category?.category_id === selectedCategory.value;

    return matchesSearch && matchesCategory;
  });
});

const totalAmount = computed(() => {
  return orderItems.value.reduce((total, item) => {
    return total + (item.dish.price * item.quantity);
  }, 0);
});

const totalItems = computed(() => {
  return orderItems.value.reduce((total, item) => total + item.quantity, 0);
});

const openQuantityModal = (dish: Dish) => {
  selectedDish.value = dish;
  modalQuantity.value = 1;
  modalSpecialInstructions.value = '';

  // Check if dish already exists in order to pre-fill quantity and instructions
  const existingItem = orderItems.value.find(item => item.dish_id === dish.dish_id);
  if (existingItem) {
    modalQuantity.value = existingItem.quantity;
    modalSpecialInstructions.value = existingItem.special_instructions;
  }

  showQuantityModal.value = true;
};

const addDishToOrder = () => {
  if (!selectedDish.value || modalQuantity.value < 1) return;

  const existingItemIndex = orderItems.value.findIndex(item => item.dish_id === selectedDish.value!.dish_id);

  if (existingItemIndex > -1) {
    // Update existing item
    orderItems.value[existingItemIndex].quantity = modalQuantity.value;
    orderItems.value[existingItemIndex].special_instructions = modalSpecialInstructions.value;
  } else {
    // Add new item
    orderItems.value.push({
      dish_id: selectedDish.value.dish_id,
      dish: selectedDish.value,
      quantity: modalQuantity.value,
      special_instructions: modalSpecialInstructions.value,
    });
  }

  showQuantityModal.value = false;
};

const closeQuantityModal = () => {
  showQuantityModal.value = false;
  selectedDish.value = null;
  modalQuantity.value = 1;
  modalSpecialInstructions.value = '';
};

const removeDishFromOrder = (dishId: number) => {
  const itemIndex = orderItems.value.findIndex(item => item.dish_id === dishId);
  if (itemIndex > -1) {
    if (orderItems.value[itemIndex].quantity > 1) {
      orderItems.value[itemIndex].quantity--;
    } else {
      orderItems.value.splice(itemIndex, 1);
    }
  }
};

const updateQuantity = (dishId: number, quantity: number) => {
  const item = orderItems.value.find(item => item.dish_id === dishId);
  if (item) {
    if (quantity > 0) {
      item.quantity = quantity;
    } else {
      removeDishFromOrder(dishId);
    }
  }
};

const submitOrder = () => {
  orderForm.order_items = orderItems.value;

  orderForm.post(route('waiter.orders.store'), {
    onSuccess: () => {
      // Redirect back to dashboard or show success message
    },
    onError: (errors) => {
      console.error('Order submission failed:', errors);
    },
  });
};

const getAllergenBadgeColor = (allergen: string) => {
  const colors: { [key: string]: string } = {
    'dairy': 'bg-blue-100 text-blue-800',
    'gluten': 'bg-yellow-100 text-yellow-800',
    'nuts': 'bg-red-100 text-red-800',
    'shellfish': 'bg-orange-100 text-orange-800',
    'soy': 'bg-green-100 text-green-800',
    'eggs': 'bg-purple-100 text-purple-800',
  };
  return colors[allergen.toLowerCase()] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
  <Head title="Create Order" />

  <WaiterLayout :employee="employee">
    <template #title>Create Order - {{ table.table_name }}</template>

    <div class="absolute top-16 left-0 right-0 bottom-0 flex flex-row gap-2 sm:gap-4 p-2 sm:p-4">
      <!-- Menu Section -->
      <div class="flex-1 space-y-3 sm:space-y-4 min-w-0">
        <!-- Search and Filter -->
        <Card>
          <CardHeader class="pb-3">
            <div class="flex flex-col space-y-3">
              <div class="flex items-center justify-between">
                <CardTitle class="text-lg">Menu Items</CardTitle>
                <Badge variant="outline" class="text-xs">
                  {{ filteredDishes.length }} items
                </Badge>
              </div>

              <!-- Search -->
              <Input
                v-model="searchQuery"
                placeholder="Search dishes..."
                class="w-full"
              />

              <!-- Category Filter -->
              <div class="flex flex-wrap gap-2">
                <Button
                  @click="selectedCategory = null"
                  :variant="selectedCategory === null ? 'default' : 'outline'"
                  size="sm"
                >
                  All
                </Button>
                <Button
                  v-for="category in categories"
                  :key="category.category_id"
                  @click="selectedCategory = category.category_id"
                  :variant="selectedCategory === category.category_id ? 'default' : 'outline'"
                  size="sm"
                >
                  {{ category.category_name }}
                </Button>
              </div>
            </div>
          </CardHeader>
        </Card>

        <!-- Dishes Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 overflow-y-auto max-h-[calc(100vh-300px)]">
          <Card
            v-for="dish in filteredDishes"
            :key="dish.dish_id"
            class="cursor-pointer transition-all hover:shadow-md"
            @click="openQuantityModal(dish)"
          >
            <CardContent class="p-3 sm:p-4">
              <div class="space-y-3">
                <!-- Dish Header -->
                <div class="flex justify-between items-start">
                  <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-base truncate">{{ dish.dish_name }}</h3>
                    <p class="text-sm text-muted-foreground mt-1">{{ dish.description || 'No description available' }}</p>
                  </div>
                  <div class="ml-2 text-right">
                    <p class="font-bold text-lg">₱{{ dish.price || 0 }}</p>
                  </div>
                </div>

                <!-- Allergens -->
                <div v-if="dish.allergens && Array.isArray(dish.allergens) && dish.allergens.length > 0" class="flex flex-wrap gap-1">
                  <Badge
                    v-for="allergen in dish.allergens"
                    :key="allergen"
                    :class="getAllergenBadgeColor(allergen)"
                    class="text-xs"
                  >
                    ⚠️ {{ allergen }}
                  </Badge>
                </div>

                <!-- Preparation Time -->
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                  <Clock class="h-4 w-4" />
                  <span>{{ dish.preparation_time }} min</span>
                  <ChefHat class="h-4 w-4 ml-2" />
                  <span>{{ dish.category?.category_name }}</span>
                </div>

                <!-- Add Button -->
                <Button class="w-full" size="sm">
                  <Plus class="h-4 w-4 mr-1" />
                  Select Quantity
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="w-72 sm:w-80 min-w-72 sm:min-w-80 space-y-3 sm:space-y-4 flex-shrink-0">
        <!-- Table Info -->
        <Card>
          <CardHeader class="pb-2 sm:pb-3">
            <CardTitle class="flex items-center gap-2 text-base sm:text-lg">
              <Users class="h-5 w-5" />
              {{ table.table_name }}
            </CardTitle>
            <CardDescription>
              Table #{{ table.table_number }} • {{ table.seats }} seats
            </CardDescription>
          </CardHeader>
        </Card>

        <!-- Customer Info -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Customer Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-2 sm:space-y-3">
            <div>
              <Label for="customer_name">Customer Name</Label>
              <Input
                id="customer_name"
                v-model="orderForm.customer_name"
                placeholder="Enter customer name"
              />
            </div>
            <div>
              <Label for="notes">Order Notes</Label>
              <Textarea
                id="notes"
                v-model="orderForm.notes"
                placeholder="Special requests or notes..."
                rows="2"
              />
            </div>
          </CardContent>
        </Card>

        <!-- Order Items -->
        <Card class="flex-1">
          <CardHeader>
            <CardTitle class="flex items-center justify-between text-base">
              <span>Order Items</span>
              <Badge>{{ totalItems }} items</Badge>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="orderItems.length === 0" class="text-center py-8 text-muted-foreground">
              <ChefHat class="h-12 w-12 mx-auto mb-3 opacity-50" />
              <p>No items added yet</p>
              <p class="text-sm">Click on dishes to add them</p>
            </div>

            <div v-else class="space-y-3 max-h-72 overflow-y-auto">
              <div
                v-for="item in orderItems"
                :key="item.dish_id"
                class="flex items-start gap-3 p-3 border rounded-lg"
              >
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-sm">{{ item.dish.dish_name }}</p>
                  <p class="text-xs text-muted-foreground">₱{{ item.dish.price }} each</p>
                  <p v-if="item.special_instructions" class="text-xs text-muted-foreground mt-1 italic">
                    Special: {{ item.special_instructions }}
                  </p>
                </div>

                <div class="flex items-center gap-1">
                  <Button
                    @click="removeDishFromOrder(item.dish_id)"
                    variant="outline"
                    size="icon"
                    class="h-6 w-6"
                  >
                    <Minus class="h-3 w-3" />
                  </Button>

                  <Input
                    :model-value="item.quantity"
                    @update:model-value="(value) => updateQuantity(item.dish_id, parseInt(value) || 0)"
                    type="number"
                    min="0"
                    class="w-12 h-6 text-center text-xs"
                  />

                  <Button
                    @click="openQuantityModal(item.dish)"
                    variant="outline"
                    size="sm"
                    class="h-6 px-2 text-xs"
                  >
                    Edit
                  </Button>
                </div>
              </div>
            </div>

            <!-- Order Total -->
            <div v-if="orderItems.length > 0" class="border-t pt-3 mt-3">
              <div class="flex justify-between items-center font-semibold">
                <span>Total:</span>
                <span class="text-lg">₱{{ totalAmount.toFixed(2) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Submit Order -->
        <div class="space-y-2">
          <Button
            @click="submitOrder"
            :disabled="orderItems.length === 0 || orderForm.processing"
            class="w-full"
            size="lg"
          >
            {{ orderForm.processing ? 'Submitting...' : 'Submit Order' }}
          </Button>

          <Button
            @click="() => window.history.back()"
            variant="outline"
            class="w-full"
          >
            Cancel
          </Button>
        </div>
      </div>
    </div>

    <!-- Quantity Selection Modal -->
    <Dialog :open="showQuantityModal" @update:open="closeQuantityModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Add to Order</DialogTitle>
          <DialogDescription v-if="selectedDish">
            {{ selectedDish.dish_name }}
          </DialogDescription>
        </DialogHeader>

        <div v-if="selectedDish" class="space-y-4">
          <!-- Dish Details -->
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <h4 class="font-semibold">{{ selectedDish.dish_name }}</h4>
              <span class="font-bold text-lg">₱{{ selectedDish.price }}</span>
            </div>
            <p class="text-sm text-muted-foreground">{{ selectedDish.description || 'No description available' }}</p>

            <!-- Allergens -->
            <div v-if="selectedDish.allergens && Array.isArray(selectedDish.allergens) && selectedDish.allergens.length > 0" class="flex flex-wrap gap-1">
              <Badge
                v-for="allergen in selectedDish.allergens"
                :key="allergen"
                :class="getAllergenBadgeColor(allergen)"
                class="text-xs"
              >
                ⚠️ {{ allergen }}
              </Badge>
            </div>

            <!-- Preparation Time -->
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <Clock class="h-4 w-4" />
              <span>{{ selectedDish.preparation_time }} min</span>
              <ChefHat class="h-4 w-4 ml-2" />
              <span>{{ selectedDish.category?.category_name }}</span>
            </div>
          </div>

          <!-- Quantity Selection -->
          <div class="space-y-2">
            <Label for="quantity">Quantity</Label>
            <div class="flex items-center gap-2">
              <Button
                @click="modalQuantity = Math.max(1, modalQuantity - 1)"
                variant="outline"
                size="icon"
                class="h-10 w-10"
              >
                <Minus class="h-4 w-4" />
              </Button>
              <Input
                v-model.number="modalQuantity"
                type="number"
                min="1"
                class="w-20 text-center"
                id="quantity"
              />
              <Button
                @click="modalQuantity++"
                variant="outline"
                size="icon"
                class="h-10 w-10"
              >
                <Plus class="h-4 w-4" />
              </Button>
            </div>
          </div>

          <!-- Special Instructions -->
          <div class="space-y-2">
            <Label for="special-instructions">Special Instructions (Optional)</Label>
            <Textarea
              v-model="modalSpecialInstructions"
              id="special-instructions"
              placeholder="Any special requests or modifications..."
              rows="3"
            />
          </div>

          <!-- Total Price -->
          <div class="flex justify-between items-center p-3 bg-muted rounded-lg">
            <span class="font-medium">Total:</span>
            <span class="font-bold text-lg">₱{{ (selectedDish.price * modalQuantity).toFixed(2) }}</span>
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button variant="outline" @click="closeQuantityModal">
            Cancel
          </Button>
          <Button @click="addDishToOrder">
            Add to Order
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </WaiterLayout>
</template>