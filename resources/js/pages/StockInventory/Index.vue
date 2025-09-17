<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';
import {
  Package, Plus, Search, Filter, Calendar,
  AlertCircle, CheckCircle, XCircle, Clock
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
}

interface StockInventory {
  stock_id: number;
  ingredient_id: number;
  batch_number: string | null;
  quantity: number;
  unit: string;
  cost_per_unit: number;
  total_cost: number;
  expiry_date: string | null;
  received_date: string;
  supplier_name: string | null;
  purchase_order_number: string | null;
  status: 'available' | 'transferred' | 'expired' | 'damaged';
  notes: string | null;
  ingredient: Ingredient;
}

interface Summary {
  total_stocks: number;
  available_stocks: number;
  expiring_soon: number;
  total_value: number;
}

const props = defineProps<{
  stocks: {
    data: StockInventory[];
    links: any;
    meta: any;
  };
  ingredients: Ingredient[];
  summary: Summary;
  filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Inventory', href: '/inventory' },
  { title: 'Stock Inventory', href: '/stock-inventory' },
];

const searchForm = useForm({
  search: props.filters.search || '',
  status: props.filters.status || 'all',
  ingredient_id: props.filters.ingredient_id || '',
  expiring_soon: props.filters.expiring_soon || '',
});

const filterForm = () => {
  searchForm.get('/stock-inventory', {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  searchForm.reset();
  searchForm.get('/stock-inventory');
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'available': return 'success';
    case 'transferred': return 'secondary';
    case 'expired': return 'destructive';
    case 'damaged': return 'destructive';
    default: return 'secondary';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'available': return CheckCircle;
    case 'transferred': return Package;
    case 'expired': return XCircle;
    case 'damaged': return AlertCircle;
    default: return Clock;
  }
};

const isExpiringSoon = (expiryDate: string | null) => {
  if (!expiryDate) return false;
  const expiry = new Date(expiryDate);
  const sevenDaysFromNow = new Date();
  sevenDaysFromNow.setDate(sevenDaysFromNow.getDate() + 7);
  return expiry <= sevenDaysFromNow;
};
</script>

<template>
  <Head title="Stock Inventory" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Stock Inventory</h1>
          <p class="text-muted-foreground">Manage your stock room inventory</p>
        </div>

        <div class="flex gap-2">
          <Link :href="route('stock-inventory.expiring')">
            <Button variant="outline">
              <Clock class="w-4 h-4 mr-2" />
              Expiring Soon
            </Button>
          </Link>

          <Link :href="route('stock-inventory.create')">
            <Button>
              <Plus class="w-4 h-4 mr-2" />
              Add Stock
            </Button>
          </Link>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Stock Items</CardTitle>
            <Package class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ summary.total_stocks }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Available Items</CardTitle>
            <CheckCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ summary.available_stocks }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Expiring Soon</CardTitle>
            <AlertCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-orange-600">{{ summary.expiring_soon }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Value</CardTitle>
            <Package class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(summary.total_value) }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="filterForm" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
              <div class="relative">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="searchForm.search"
                  placeholder="Search ingredients..."
                  class="pl-10"
                />
              </div>
            </div>

            <Select v-model="searchForm.status">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="available">Available</SelectItem>
                <SelectItem value="transferred">Transferred</SelectItem>
                <SelectItem value="expired">Expired</SelectItem>
                <SelectItem value="damaged">Damaged</SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="searchForm.ingredient_id">
              <SelectTrigger class="w-[200px]">
                <SelectValue placeholder="Select ingredient" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Ingredients</SelectItem>
                <SelectItem
                  v-for="ingredient in ingredients"
                  :key="ingredient.ingredient_id"
                  :value="ingredient.ingredient_id.toString()"
                >
                  {{ ingredient.ingredient_name }}
                </SelectItem>
              </SelectContent>
            </Select>

            <div class="flex gap-2">
              <Button type="submit">
                <Filter class="w-4 h-4 mr-2" />
                Filter
              </Button>
              <Button type="button" variant="outline" @click="clearFilters">
                Clear
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Stock List -->
      <Card>
        <CardHeader>
          <CardTitle>Stock Items</CardTitle>
          <CardDescription>
            {{ stocks.meta.total }} total items
          </CardDescription>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ingredient</TableHead>
                <TableHead>Batch #</TableHead>
                <TableHead>Quantity</TableHead>
                <TableHead>Cost/Unit</TableHead>
                <TableHead>Total Cost</TableHead>
                <TableHead>Expiry Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="stock in stocks.data" :key="stock.stock_id">
                <TableCell>
                  <div>
                    <div class="font-medium">{{ stock.ingredient.ingredient_name }}</div>
                    <div class="text-sm text-muted-foreground" v-if="stock.supplier_name">
                      From: {{ stock.supplier_name }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <span class="font-mono text-sm">{{ stock.batch_number || 'N/A' }}</span>
                </TableCell>
                <TableCell>
                  {{ stock.quantity }} {{ stock.unit }}
                </TableCell>
                <TableCell>
                  {{ formatCurrency(stock.cost_per_unit) }}
                </TableCell>
                <TableCell>
                  {{ formatCurrency(stock.total_cost) }}
                </TableCell>
                <TableCell>
                  <div v-if="stock.expiry_date">
                    <div class="text-sm">{{ new Date(stock.expiry_date).toLocaleDateString() }}</div>
                    <div v-if="isExpiringSoon(stock.expiry_date)" class="text-xs text-orange-600">
                      Expiring Soon
                    </div>
                  </div>
                  <span v-else class="text-muted-foreground">No expiry</span>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(stock.status)">
                    <component :is="getStatusIcon(stock.status)" class="w-3 h-3 mr-1" />
                    {{ stock.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="flex gap-2">
                    <Link :href="route('stock-inventory.show', stock.stock_id)">
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                    <Link :href="route('stock-inventory.edit', stock.stock_id)" v-if="stock.status === 'available'">
                      <Button variant="outline" size="sm">Edit</Button>
                    </Link>
                  </div>
                </TableCell>
              </TableRow>

              <TableRow v-if="stocks.data.length === 0">
                <TableCell colspan="8" class="text-center text-muted-foreground py-8">
                  No stock items found
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div v-if="stocks.meta.last_page > 1" class="flex justify-center">
        <div class="flex gap-2">
          <Link
            v-for="link in stocks.links"
            :key="link.label"
            :href="link.url || '#'"
            :class="[
              'px-3 py-2 text-sm border rounded',
              link.active ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted',
              !link.url && 'opacity-50 cursor-not-allowed'
            ]"
          >
            <span v-html="link.label"></span>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>