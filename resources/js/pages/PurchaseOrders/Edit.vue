<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  ingredients: {
    ingredient_id: number;
    ingredient_name: string;
    pivot: {
      cost_per_unit: number;
      unit_of_measure: string;
    };
  }[];
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  suppliers: {
    supplier_id: number;
    supplier_name: string;
    pivot: {
      cost_per_unit: number;
      unit_of_measure: string;
    };
  }[];
}

interface PurchaseOrderItem {
  purchase_order_item_id?: number;
  ingredient_id: number;
  ingredient: {
    ingredient_name: string;
  };
  ordered_quantity: number;
  unit_price: number;
  unit_of_measure: string;
  notes: string;
}

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  supplier_id: number;
  expected_delivery_date: string;
  notes: string;
  delivery_instructions: string;
  supplier: {
    supplier_name: string;
  };
  items: PurchaseOrderItem[];
}

interface OrderItem {
  ingredient_id: number | null;
  ordered_quantity: number;
  unit_price: number;
  unit_of_measure: string;
  notes: string;
  [key: string]: any;
}

interface Props {
  purchaseOrder: PurchaseOrder;
  suppliers: Supplier[];
  ingredients: Ingredient[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: `PO ${props.purchaseOrder.po_number}`, href: `/purchase-orders/${props.purchaseOrder.purchase_order_id}` },
  { title: 'Edit', href: '#' },
];

const selectedSupplier = ref<number>(props.purchaseOrder.supplier_id);
const orderItems = ref<OrderItem[]>([]);

const form = useForm({
  supplier_id: props.purchaseOrder.supplier_id,
  expected_delivery_date: props.purchaseOrder.expected_delivery_date || '',
  notes: props.purchaseOrder.notes || '',
  delivery_instructions: props.purchaseOrder.delivery_instructions || '',
  items: [] as OrderItem[]
});

const availableIngredients = computed(() => {
  if (!selectedSupplier.value) return [];
  
  const supplier = props.suppliers.find(s => s.supplier_id === selectedSupplier.value);
  return supplier?.ingredients || [];
});

const subtotal = computed(() => {
  return orderItems.value.reduce((sum, item) => {
    return sum + (item.ordered_quantity * item.unit_price);
  }, 0);
});

const addOrderItem = () => {
  orderItems.value.push({
    ingredient_id: null,
    ordered_quantity: 0,
    unit_price: 0,
    unit_of_measure: '',
    notes: ''
  });
};

const removeOrderItem = (index: number) => {
  if (orderItems.value.length > 1) {
    orderItems.value.splice(index, 1);
  }
};

const onIngredientSelect = (itemIndex: number, ingredientId: number | null) => {
  if (!ingredientId) return;
  
  const ingredient = availableIngredients.value.find(ing => ing.ingredient_id === ingredientId);
  if (ingredient) {
    orderItems.value[itemIndex].ingredient_id = ingredientId;
    // Only set price and unit if it's a new ingredient (not editing existing)
    if (!orderItems.value[itemIndex].unit_price) {
      orderItems.value[itemIndex].unit_price = ingredient.pivot.cost_per_unit;
      orderItems.value[itemIndex].unit_of_measure = ingredient.pivot.unit_of_measure;
    }
  }
};

const getIngredientName = (ingredientId: number) => {
  const ingredient = availableIngredients.value.find(ing => ing.ingredient_id === ingredientId);
  return ingredient?.ingredient_name || '';
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

// Initialize form with existing data
onMounted(() => {
  orderItems.value = props.purchaseOrder.items.map(item => ({
    ingredient_id: item.ingredient_id,
    ordered_quantity: item.ordered_quantity,
    unit_price: item.unit_price,
    unit_of_measure: item.unit_of_measure,
    notes: item.notes || ''
  }));
});

watch(selectedSupplier, (newValue) => {
  form.supplier_id = newValue;
  // Don't reset items when editing - just update the supplier
});

const submit = () => {
  // Filter out empty items and validate
  const validItems = orderItems.value.filter(item => 
    item.ingredient_id && 
    item.ordered_quantity > 0 && 
    item.unit_price > 0
  );

  form.items = validItems;
  form.put(`/purchase-orders/${props.purchaseOrder.purchase_order_id}`);
};
</script>

<template>
  <Head :title="`Edit Purchase Order ${purchaseOrder.po_number}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Edit Purchase Order {{ purchaseOrder.po_number }}</h1>
        <p class="text-muted-foreground">Update purchase order details</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information -->
        <Card>
          <CardHeader>
            <CardTitle>Order Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="supplier">Supplier *</Label>
                <Select v-model="selectedSupplier" required>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a supplier" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem 
                      v-for="supplier in suppliers" 
                      :key="supplier.supplier_id"
                      :value="supplier.supplier_id"
                    >
                      {{ supplier.supplier_name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.supplier_id" class="text-sm text-red-600">
                  {{ form.errors.supplier_id }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="expected_delivery_date">Expected Delivery Date</Label>
                <Input
                  id="expected_delivery_date"
                  v-model="form.expected_delivery_date"
                  type="date"
                  :min="new Date().toISOString().split('T')[0]"
                />
                <div v-if="form.errors.expected_delivery_date" class="text-sm text-red-600">
                  {{ form.errors.expected_delivery_date }}
                </div>
              </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="notes">Notes</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  placeholder="Any additional notes for this order..."
                  rows="3"
                />
                <div v-if="form.errors.notes" class="text-sm text-red-600">
                  {{ form.errors.notes }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="delivery_instructions">Delivery Instructions</Label>
                <Textarea
                  id="delivery_instructions"
                  v-model="form.delivery_instructions"
                  placeholder="Special delivery instructions..."
                  rows="3"
                />
                <div v-if="form.errors.delivery_instructions" class="text-sm text-red-600">
                  {{ form.errors.delivery_instructions }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Order Items -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Order Items</CardTitle>
              <Button 
                type="button" 
                @click="addOrderItem"
                variant="outline"
                size="sm"
                :disabled="!selectedSupplier"
              >
                <Plus class="w-4 h-4 mr-2" />
                Add Item
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Ingredient</TableHead>
                    <TableHead>Quantity</TableHead>
                    <TableHead>Unit Price</TableHead>
                    <TableHead>Unit</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Notes</TableHead>
                    <TableHead class="w-12"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(item, index) in orderItems" :key="index">
                    <TableCell>
                      <Select 
                        :model-value="item.ingredient_id"
                        @update:model-value="(value) => onIngredientSelect(index, value ? Number(value) : null)"
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Select ingredient" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem 
                            v-for="ingredient in availableIngredients" 
                            :key="ingredient.ingredient_id"
                            :value="ingredient.ingredient_id"
                          >
                            {{ ingredient.ingredient_name }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model.number="item.ordered_quantity"
                        type="number"
                        step="0.01"
                        min="0.01"
                        placeholder="0"
                        class="w-24"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model.number="item.unit_price"
                        type="number"
                        step="0.01"
                        min="0.01"
                        placeholder="0.00"
                        class="w-24"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model="item.unit_of_measure"
                        placeholder="kg, pcs, etc."
                        class="w-20"
                      />
                    </TableCell>
                    <TableCell class="font-medium">
                      {{ formatCurrency(item.ordered_quantity * item.unit_price) }}
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model="item.notes"
                        placeholder="Optional notes"
                        class="w-32"
                      />
                    </TableCell>
                    <TableCell>
                      <Button
                        type="button"
                        @click="removeOrderItem(index)"
                        variant="ghost"
                        size="sm"
                        :disabled="orderItems.length === 1"
                        class="h-8 w-8 p-0"
                      >
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              
              <div v-if="form.errors.items" class="text-sm text-red-600">
                {{ form.errors.items }}
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Order Summary -->
        <Card v-if="selectedSupplier && orderItems.some(item => item.ingredient_id)">
          <CardHeader>
            <CardTitle>Order Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between text-lg font-semibold">
                <span>Total Amount</span>
                <span>{{ formatCurrency(subtotal) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
          <Button 
            type="submit" 
            :disabled="form.processing || !selectedSupplier || orderItems.length === 0"
          >
            {{ form.processing ? 'Updating...' : 'Update Purchase Order' }}
          </Button>
          
          <Button 
            type="button" 
            variant="outline" 
            @click="$inertia.visit(`/purchase-orders/${purchaseOrder.purchase_order_id}`)"
          >
            Cancel
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>