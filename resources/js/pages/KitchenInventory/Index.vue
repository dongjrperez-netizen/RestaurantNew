<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';
import { Progress } from '@/components/ui/progress';
import {
  ChefHat, Search, Filter, Calendar,
  AlertCircle, CheckCircle, XCircle, Clock, ArrowLeftRight
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
}

interface User {
  id: number;
  first_name: string;
  last_name: string;
}

interface KitchenInventory {
  kitchen_stock_id: number;
  ingredient_id: number;
  stock_id: number;
  quantity_transferred: number;
  quantity_remaining: number;
  unit: string;
  transfer_date: string;
  expiry_date: string | null;
  transferred_by: number;
  status: 'active' | 'used_up' | 'expired' | 'returned';
  transfer_notes: string | null;
  ingredient: Ingredient;
  transferred_by_user?: User;
}

interface Summary {
  total_transfers: number;
  active_stocks: number;
  expiring_soon: number;
  total_remaining: number;
}

const props = defineProps<{
  kitchenStocks: {
    data: KitchenInventory[];
    links: any;
    meta: any;
  };
  ingredients: Ingredient[];
  summary: Summary;
  filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Inventory', href: '/inventory' },
  { title: 'Kitchen Inventory', href: '/kitchen-inventory' },
];

const searchForm = useForm({
  search: props.filters.search || '',
  status: props.filters.status || 'all',
  ingredient_id: props.filters.ingredient_id || '',
  expiring_soon: props.filters.expiring_soon || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
});

const filterForm = () => {
  searchForm.get('/kitchen-inventory', {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  searchForm.reset();
  searchForm.get('/kitchen-inventory');
};

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'active': return 'success';
    case 'used_up': return 'secondary';
    case 'expired': return 'destructive';
    case 'returned': return 'outline';
    default: return 'secondary';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'active': return CheckCircle;
    case 'used_up': return XCircle;
    case 'expired': return AlertCircle;
    case 'returned': return ArrowLeftRight;
    default: return Clock;
  }
};

const getUsagePercentage = (transferred: number, remaining: number) => {
  if (transferred <= 0) return 0;
  return ((transferred - remaining) / transferred) * 100;
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
  <Head title="Kitchen Inventory" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Kitchen Inventory</h1>
          <p class="text-muted-foreground">Track ingredients transferred to kitchen</p>
        </div>

        <div class="flex gap-2">
          <Link :href="route('kitchen-inventory.expiring')">
            <Button variant="outline">
              <Clock class="w-4 h-4 mr-2" />
              Expiring Soon
            </Button>
          </Link>

          <Link :href="route('kitchen-inventory.usage-report')">
            <Button variant="outline">
              <ChefHat class="w-4 h-4 mr-2" />
              Usage Report
            </Button>
          </Link>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Transfers</CardTitle>
            <ArrowLeftRight class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ summary.total_transfers }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Stocks</CardTitle>
            <CheckCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ summary.active_stocks }}</div>
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
            <CardTitle class="text-sm font-medium">Total Remaining</CardTitle>
            <ChefHat class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ summary.total_remaining }}</div>
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
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="used_up">Used Up</SelectItem>
                <SelectItem value="expired">Expired</SelectItem>
                <SelectItem value="returned">Returned</SelectItem>
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

            <Input
              v-model="searchForm.date_from"
              type="date"
              placeholder="From date"
              class="w-[150px]"
            />

            <Input
              v-model="searchForm.date_to"
              type="date"
              placeholder="To date"
              class="w-[150px]"
            />

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

      <!-- Kitchen Stock List -->
      <Card>
        <CardHeader>
          <CardTitle>Kitchen Stock Items</CardTitle>
          <CardDescription>
            {{ kitchenStocks.meta.total }} total transfers
          </CardDescription>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ingredient</TableHead>
                <TableHead>Transferred</TableHead>
                <TableHead>Remaining</TableHead>
                <TableHead>Usage</TableHead>
                <TableHead>Transfer Date</TableHead>
                <TableHead>Expiry Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="stock in kitchenStocks.data" :key="stock.kitchen_stock_id">
                <TableCell>
                  <div>
                    <div class="font-medium">{{ stock.ingredient.ingredient_name }}</div>
                    <div class="text-sm text-muted-foreground" v-if="stock.transferred_by_user">
                      By: {{ stock.transferred_by_user.first_name }} {{ stock.transferred_by_user.last_name }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  {{ stock.quantity_transferred }} {{ stock.unit }}
                </TableCell>
                <TableCell>
                  <div class="flex flex-col">
                    <span>{{ stock.quantity_remaining }} {{ stock.unit }}</span>
                    <Progress
                      :value="100 - getUsagePercentage(stock.quantity_transferred, stock.quantity_remaining)"
                      class="w-16 h-2 mt-1"
                    />
                  </div>
                </TableCell>
                <TableCell>
                  <div class="text-sm">
                    <div>{{ ((stock.quantity_transferred - stock.quantity_remaining) / stock.quantity_transferred * 100).toFixed(1) }}% used</div>
                    <div class="text-muted-foreground">
                      {{ stock.quantity_transferred - stock.quantity_remaining }} {{ stock.unit }} consumed
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  {{ new Date(stock.transfer_date).toLocaleDateString() }}
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
                    <Link :href="route('kitchen-inventory.show', stock.kitchen_stock_id)">
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                  </div>
                </TableCell>
              </TableRow>

              <TableRow v-if="kitchenStocks.data.length === 0">
                <TableCell colspan="8" class="text-center text-muted-foreground py-8">
                  No kitchen stock items found
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div v-if="kitchenStocks.meta.last_page > 1" class="flex justify-center">
        <div class="flex gap-2">
          <Link
            v-for="link in kitchenStocks.links"
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