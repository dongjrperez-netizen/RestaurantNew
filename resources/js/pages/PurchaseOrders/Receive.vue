<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';

interface PurchaseOrderItem {
  purchase_order_item_id: number;
  ingredient: {
    ingredient_name: string;
  };
  ordered_quantity: number;
  received_quantity: number;
  unit_price: number;
  total_price: number;
  unit_of_measure: string;
}

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  status: string;
  order_date: string;
  expected_delivery_date?: string;
  supplier: {
    supplier_name: string;
  };
  items: PurchaseOrderItem[];
}

interface ReceiveItem {
  purchase_order_item_id: number;
  received_quantity: number;
}

interface Props {
  purchaseOrder: PurchaseOrder;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: `PO ${props.purchaseOrder.po_number}`, href: `/purchase-orders/${props.purchaseOrder.purchase_order_id}` },
  { title: 'Receive', href: '#' },
];

const receiveItems = ref<ReceiveItem[]>(
  props.purchaseOrder.items.map(item => ({
    purchase_order_item_id: item.purchase_order_item_id,
    received_quantity: 0
  }))
);

const form = useForm({
  actual_delivery_date: new Date().toISOString().split('T')[0],
  items: receiveItems.value
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const getRemainingQuantity = (item: PurchaseOrderItem) => {
  return item.ordered_quantity - item.received_quantity;
};

const setFullQuantity = (index: number) => {
  const item = props.purchaseOrder.items[index];
  receiveItems.value[index].received_quantity = getRemainingQuantity(item);
};

const setAllFullQuantities = () => {
  props.purchaseOrder.items.forEach((item, index) => {
    receiveItems.value[index].received_quantity = getRemainingQuantity(item);
  });
};

const clearQuantity = (index: number) => {
  receiveItems.value[index].received_quantity = 0;
};

const clearAllQuantities = () => {
  receiveItems.value.forEach(item => {
    item.received_quantity = 0;
  });
};

const getTotalReceiving = () => {
  return receiveItems.value.reduce((sum, receiveItem, index) => {
    const item = props.purchaseOrder.items[index];
    return sum + (receiveItem.received_quantity * item.unit_price);
  }, 0);
};

const getReceiveStatus = (item: PurchaseOrderItem, receiveQuantity: number): { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' } => {
  const totalAfterReceive = item.received_quantity + receiveQuantity;
  const ordered = item.ordered_quantity;
  
  if (totalAfterReceive === 0) return { label: 'Not Received', variant: 'secondary' };
  if (totalAfterReceive < ordered) return { label: 'Partial', variant: 'warning' };
  if (totalAfterReceive === ordered) return { label: 'Complete', variant: 'success' };
  return { label: 'Over Received', variant: 'destructive' };
};

const submit = () => {
  form.items = receiveItems.value;
  form.post(`/purchase-orders/${props.purchaseOrder.purchase_order_id}/receive`);
};
</script>

<template>
  <Head :title="`Receive Delivery - PO ${purchaseOrder.po_number}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Receive Delivery</h1>
        <p class="text-muted-foreground">Record the quantities received for Purchase Order {{ purchaseOrder.po_number }}</p>
      </div>

      <!-- Order Information -->
      <Card>
        <CardHeader>
          <CardTitle>Purchase Order Information</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-4">
            <div>
              <label class="text-sm font-medium text-muted-foreground">PO Number</label>
              <div class="font-medium">{{ purchaseOrder.po_number }}</div>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Supplier</label>
              <div>{{ purchaseOrder.supplier.supplier_name }}</div>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Order Date</label>
              <div>{{ formatDate(purchaseOrder.order_date) }}</div>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Expected Delivery</label>
              <div>
                <span v-if="purchaseOrder.expected_delivery_date">
                  {{ formatDate(purchaseOrder.expected_delivery_date) }}
                </span>
                <span v-else class="text-muted-foreground">Not specified</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Delivery Date -->
        <Card>
          <CardHeader>
            <CardTitle>Delivery Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2 max-w-xs">
              <Label for="actual_delivery_date">Actual Delivery Date *</Label>
              <Input
                id="actual_delivery_date"
                v-model="form.actual_delivery_date"
                type="date"
                required
              />
              <div v-if="form.errors.actual_delivery_date" class="text-sm text-red-600">
                {{ form.errors.actual_delivery_date }}
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Receiving Items -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Items to Receive</CardTitle>
              <div class="flex space-x-2">
                <Button type="button" @click="setAllFullQuantities" variant="outline" size="sm">
                  Receive All
                </Button>
                <Button type="button" @click="clearAllQuantities" variant="outline" size="sm">
                  Clear All
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Ingredient</TableHead>
                  <TableHead>Ordered Qty</TableHead>
                  <TableHead>Already Received</TableHead>
                  <TableHead>Remaining</TableHead>
                  <TableHead>Receiving Now</TableHead>
                  <TableHead>Unit</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="(item, index) in purchaseOrder.items" :key="item.purchase_order_item_id">
                  <TableCell class="font-medium">
                    {{ item.ingredient.ingredient_name }}
                  </TableCell>
                  <TableCell>
                    {{ item.ordered_quantity }}
                  </TableCell>
                  <TableCell>
                    {{ item.received_quantity }}
                  </TableCell>
                  <TableCell class="font-medium">
                    {{ getRemainingQuantity(item) }}
                  </TableCell>
                  <TableCell>
                    <Input
                      v-model.number="receiveItems[index].received_quantity"
                      type="number"
                      step="0.01"
                      min="0"
                      :max="getRemainingQuantity(item) * 1.1"
                      class="w-24"
                    />
                  </TableCell>
                  <TableCell>
                    {{ item.unit_of_measure }}
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getReceiveStatus(item, receiveItems[index].received_quantity).variant">
                      {{ getReceiveStatus(item, receiveItems[index].received_quantity).label }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="flex space-x-1">
                      <Button 
                        type="button" 
                        @click="setFullQuantity(index)"
                        variant="outline" 
                        size="sm"
                        :disabled="getRemainingQuantity(item) === 0"
                      >
                        Full
                      </Button>
                      <Button 
                        type="button" 
                        @click="clearQuantity(index)"
                        variant="outline" 
                        size="sm"
                      >
                        Clear
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
            
            <div v-if="form.errors.items" class="text-sm text-red-600 mt-4">
              {{ form.errors.items }}
            </div>
          </CardContent>
        </Card>

        <!-- Receiving Summary -->
        <Card v-if="getTotalReceiving() > 0">
          <CardHeader>
            <CardTitle>Receiving Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span>Items being received:</span>
                <span>{{ receiveItems.filter(item => item.received_quantity > 0).length }} of {{ receiveItems.length }}</span>
              </div>
              <div class="flex justify-between text-lg font-semibold">
                <span>Total Value Receiving:</span>
                <span>{{ formatCurrency(getTotalReceiving()) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
          <Button 
            type="submit" 
            :disabled="form.processing || receiveItems.every(item => item.received_quantity === 0)"
          >
            {{ form.processing ? 'Processing...' : 'Confirm Receipt' }}
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