<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Plus, Eye, Clock, CheckCircle, XCircle } from 'lucide-vue-next';

interface OrderItem {
  order_item_id: number;
  dish_name: string;
  quantity: number;
  unit_price: number;
  total_price: number;
  status: string;
  inventory_deducted: boolean;
}

interface Order {
  order_id: number;
  order_number: string;
  order_type: 'dine_in' | 'takeout' | 'delivery';
  status: 'pending' | 'confirmed' | 'preparing' | 'ready' | 'served' | 'completed' | 'cancelled';
  customer_name: string;
  customer_phone?: string;
  table_number?: string;
  total_amount: number;
  order_time: string;
  estimated_ready_time?: string;
  order_items: OrderItem[];
}

interface Props {
  orders: {
    data: Order[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  statusCounts: {
    pending: number;
    confirmed: number;
    preparing: number;
    ready: number;
    completed: number;
  };
  filters: {
    status?: string;
    order_type?: string;
    date_from?: string;
    date_to?: string;
  };
}

const props = defineProps<Props>();

const selectedStatus = ref(props.filters.status || '');
const selectedOrderType = ref(props.filters.order_type || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Orders', href: '/orders' },
];

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'pending', label: 'Pending' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'preparing', label: 'Preparing' },
  { value: 'ready', label: 'Ready' },
  { value: 'served', label: 'Served' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const orderTypeOptions = [
  { value: '', label: 'All Types' },
  { value: 'dine_in', label: 'Dine In' },
  { value: 'takeout', label: 'Takeout' },
  { value: 'delivery', label: 'Delivery' },
];

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'confirmed': return 'default';
    case 'preparing': return 'secondary';
    case 'ready': return 'outline';
    case 'served':
    case 'completed': return 'default';
    case 'cancelled': return 'destructive';
    case 'pending': return 'secondary';
    default: return 'secondary';
  }
};

const getOrderTypeBadge = (orderType: string) => {
  switch (orderType) {
    case 'dine_in': return { label: 'Dine In', variant: 'default' };
    case 'takeout': return { label: 'Takeout', variant: 'secondary' };
    case 'delivery': return { label: 'Delivery', variant: 'outline' };
    default: return { label: orderType, variant: 'secondary' };
  }
};

const applyFilters = () => {
  router.get('/orders', {
    status: selectedStatus.value || undefined,
    order_type: selectedOrderType.value || undefined,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  });
};

const clearFilters = () => {
  selectedStatus.value = '';
  selectedOrderType.value = '';
  dateFrom.value = '';
  dateTo.value = '';
  applyFilters();
};

const updateOrderStatus = (order: Order, newStatus: string) => {
  router.post(`/orders/${order.order_id}/status`, {
    status: newStatus
  }, {
    preserveState: true,
    onSuccess: () => {
      // Handle success if needed
    }
  });
};

const formatTime = (dateString: string) => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};
</script>

<template>
  <Head title="Order Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Order Management</h1>
          <p class="text-muted-foreground">Manage customer orders and track inventory deductions</p>
        </div>
        <div class="flex space-x-2">
          <Link href="/orders/kitchen">
            <Button variant="outline">
              <Clock class="w-4 h-4 mr-2" />
              Kitchen Display
            </Button>
          </Link>
          <Link href="/orders/analytics">
            <Button variant="outline">View Analytics</Button>
          </Link>
          <Link href="/orders/create">
            <Button>
              <Plus class="w-4 h-4 mr-2" />
              New Order
            </Button>
          </Link>
        </div>
      </div>

      <!-- Status Overview Cards -->
      <div class="grid gap-4 md:grid-cols-5">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
            <Clock class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statusCounts.pending }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Confirmed</CardTitle>
            <CheckCircle class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statusCounts.confirmed }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Preparing</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statusCounts.preparing }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Ready</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statusCounts.ready }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Completed</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statusCounts.completed }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle>Filter Orders</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-4">
            <div class="min-w-[150px]">
              <Select v-model="selectedStatus">
                <SelectTrigger>
                  <SelectValue placeholder="All Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in statusOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="min-w-[150px]">
              <Select v-model="selectedOrderType">
                <SelectTrigger>
                  <SelectValue placeholder="All Types" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in orderTypeOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="min-w-[150px]">
              <Input
                v-model="dateFrom"
                type="date"
                placeholder="From Date"
              />
            </div>

            <div class="min-w-[150px]">
              <Input
                v-model="dateTo"
                type="date"
                placeholder="To Date"
              />
            </div>

            <Button @click="applyFilters">Apply Filters</Button>
            <Button variant="outline" @click="clearFilters">Clear</Button>
          </div>
        </CardContent>
      </Card>

      <!-- Orders Table -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Orders</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Order #</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Items</TableHead>
                <TableHead>Total</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Time</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="order in orders.data" :key="order.order_id">
                <TableCell class="font-medium">
                  <div class="flex flex-col">
                    <span class="font-semibold">{{ order.order_number }}</span>
                    <span v-if="order.table_number" class="text-xs text-muted-foreground">
                      Table: {{ order.table_number }}
                    </span>
                  </div>
                </TableCell>
                <TableCell>
                  <div>
                    <div class="font-medium">{{ order.customer_name }}</div>
                    <div v-if="order.customer_phone" class="text-sm text-muted-foreground">
                      {{ order.customer_phone }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="getOrderTypeBadge(order.order_type).variant">
                    {{ getOrderTypeBadge(order.order_type).label }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="flex flex-col">
                    <span class="font-medium">{{ order.order_items.length }} items</span>
                    <div class="text-xs text-muted-foreground">
                      <span
                        v-if="order.order_items.some(item => item.inventory_deducted)"
                        class="text-green-600"
                      >
                        ✓ Inventory deducted
                      </span>
                      <span v-else class="text-amber-600">
                        ⏳ Inventory pending
                      </span>
                    </div>
                  </div>
                </TableCell>
                <TableCell class="font-semibold">
                  {{ formatCurrency(order.total_amount) }}
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(order.status)">
                    {{ order.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ formatTime(order.order_time) }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end space-x-2">
                    <Link :href="`/orders/${order.order_id}`">
                      <Button variant="outline" size="sm">
                        <Eye class="w-3 h-3 mr-1" />
                        View
                      </Button>
                    </Link>
                    <Button
                      v-if="order.status === 'pending'"
                      variant="default"
                      size="sm"
                      @click="updateOrderStatus(order, 'confirmed')"
                    >
                      <CheckCircle class="w-3 h-3 mr-1" />
                      Confirm
                    </Button>
                    <Button
                      v-else-if="order.status === 'confirmed'"
                      variant="secondary"
                      size="sm"
                      @click="updateOrderStatus(order, 'preparing')"
                    >
                      Start Prep
                    </Button>
                    <Button
                      v-else-if="order.status === 'ready'"
                      variant="default"
                      size="sm"
                      @click="updateOrderStatus(order, 'served')"
                    >
                      Mark Served
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="orders.data.length === 0">
                <TableCell colspan="8" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No orders found</div>
                    <div class="text-sm">Orders will appear here once customers place them.</div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- Pagination -->
          <div v-if="orders.last_page > 1" class="flex justify-center mt-6">
            <div class="flex space-x-2">
              <Button
                v-for="page in orders.last_page"
                :key="page"
                :variant="page === orders.current_page ? 'default' : 'outline'"
                size="sm"
                @click="applyFilters()"
              >
                {{ page }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>