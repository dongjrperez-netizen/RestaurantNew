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

interface MenuCategory {
  category_id: number;
  category_name: string;
  sort_order: number;
  is_active: boolean;
}

interface DishPricing {
  pricing_id: number;
  price_type: string;
  base_price: number;
  promotional_price?: number;
  promo_start_date?: string;
  promo_end_date?: string;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  preparation_time?: number;
  serving_size?: number;
  serving_unit?: string;
  image_url?: string;
  calories?: number;
  allergens?: string[];
  dietary_tags?: string[];
  status: 'draft' | 'active' | 'inactive' | 'archived';
  category?: MenuCategory;
  pricing?: DishPricing[];
  created_at: string;
  updated_at: string;
}

interface Props {
  dishes: Dish[];
  categories: MenuCategory[];
  filters: {
    category_id?: number;
    status?: string;
    search?: string;
  };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id || '');
const selectedStatus = ref(props.filters.status || '');

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
];

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'archived', label: 'Archived' },
];

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'active': return 'default';
    case 'draft': return 'secondary';
    case 'inactive': return 'destructive';
    case 'archived': return 'outline';
    default: return 'secondary';
  }
};

const getDineInPrice = (dish: Dish) => {
  const pricing = dish.pricing?.find(p => p.price_type === 'dine_in');
  return pricing ? `₱${Number(pricing.base_price).toLocaleString()}` : 'N/A';
};

const applyFilters = () => {
  router.get('/menu', {
    search: searchQuery.value || undefined,
    category_id: selectedCategory.value || undefined,
    status: selectedStatus.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = '';
  selectedStatus.value = '';
  applyFilters();
};

const updateDishStatus = (dish: Dish, newStatus: string) => {
  router.post(`/menu/${dish.dish_id}/status`, {
    status: newStatus
  }, {
    preserveState: true,
  });
};

const activeDishes = computed(() => props.dishes.filter(dish => dish.status === 'active').length);
const draftDishes = computed(() => props.dishes.filter(dish => dish.status === 'draft').length);
</script>

<template>
  <Head title="Menu Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Menu Management</h1>
          <p class="text-muted-foreground">Manage your restaurant menu items and categories</p>
        </div>
        <div class="flex space-x-2">
          <Link href="/menu-analytics">
            <Button variant="outline">View Analytics</Button>
          </Link>
          <Link href="/menu-categories">
            <Button variant="outline">Manage Categories</Button>
          </Link>
          <Link :href="route('menu.create')">
            <Button>Add New Dish</Button>
          </Link>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Dishes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ dishes.length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Dishes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ activeDishes }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Draft Dishes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ draftDishes }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Categories</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ categories.length }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle>Filter Dishes</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
              <Input
                v-model="searchQuery"
                placeholder="Search dishes..."
                @keyup.enter="applyFilters"
              />
            </div>
            
            <div class="min-w-[150px]">
              <Select v-model="selectedCategory">
                <SelectTrigger>
                  <SelectValue placeholder="All Categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Categories</SelectItem>
                  <SelectItem 
                    v-for="category in categories" 
                    :key="category.category_id"
                    :value="category.category_id.toString()"
                  >
                    {{ category.category_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

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

            <Button @click="applyFilters">Apply Filters</Button>
            <Button variant="outline" @click="clearFilters">Clear</Button>
          </div>
        </CardContent>
      </Card>

      <!-- Dishes Table -->
      <Card>
        <CardHeader>
          <CardTitle>Menu Items</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Dish Name</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Prep Time</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="dish in dishes" :key="dish.dish_id">
                <TableCell class="font-medium">
                  <div>
                    <div class="font-semibold">{{ dish.dish_name }}</div>
                    <div v-if="dish.description" class="text-sm text-muted-foreground">
                      {{ dish.description?.substring(0, 100) }}{{ dish.description?.length > 100 ? '...' : '' }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">
                    {{ dish.category?.category_name || 'Uncategorized' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ getDineInPrice(dish) }}
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(dish.status)">
                    {{ dish.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ dish.preparation_time ? `${dish.preparation_time} min` : 'N/A' }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end space-x-2">
                    <Link :href="`/menu/${dish.dish_id}`">
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                    <Link :href="`/menu/${dish.dish_id}/edit`">
                      <Button variant="outline" size="sm">Edit</Button>
                    </Link>
                    <Button 
                      v-if="dish.status === 'draft'"
                      variant="default" 
                      size="sm"
                      @click="updateDishStatus(dish, 'active')"
                    >
                      Activate
                    </Button>
                    <Button 
                      v-else-if="dish.status === 'active'"
                      variant="secondary" 
                      size="sm"
                      @click="updateDishStatus(dish, 'inactive')"
                    >
                      Deactivate
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="dishes.length === 0">
                <TableCell colspan="6" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No dishes found</div>
                    <div class="text-sm">Get started by adding your first dish.</div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>