<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import SupplierLayout from '@/layouts/SupplierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Restaurant {
  id: number;
  restaurant_name: string;
  address: string;
}

interface Props {
  restaurants: Restaurant[];
}

defineProps<Props>();

const form = useForm({
  restaurant_id: null as number | null,
  ingredient_name: '',
  base_unit: '',
  package_unit: '',
  package_quantity: 1,
  package_price: 0,
  lead_time_days: 0,
  minimum_order_quantity: 1,
});


const submit = () => {
  form.post('/supplier/ingredients');
};
</script>

<template>
  <Head title="Add Ingredient Offer" />

  <SupplierLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Add Ingredient Offer</h1>
        <p class="text-muted-foreground">Offer your ingredients to restaurants</p>
      </div>

      <!-- Form -->
      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>New Ingredient Offer</CardTitle>
          <CardDescription>
            Add your own ingredients to offer to restaurants with your pricing and terms
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Restaurant Selection -->
            <div class="space-y-2">
              <Label for="restaurant">Restaurant *</Label>
              <Select v-model="form.restaurant_id" required>
                <SelectTrigger>
                  <SelectValue placeholder="Select a restaurant to offer ingredient to" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="restaurant in restaurants" 
                    :key="restaurant.id"
                    :value="restaurant.id"
                  >
                    <div>
                      <div class="font-medium">{{ restaurant.restaurant_name }}</div>
                      <div class="text-sm text-muted-foreground">{{ restaurant.address }}</div>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.restaurant_id" class="text-sm text-red-600">
                {{ form.errors.restaurant_id }}
              </div>
            </div>

            <!-- Custom Ingredient Details -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="ingredient_name">Ingredient Name *</Label>
                <Input
                  id="ingredient_name"
                  v-model="form.ingredient_name"
                  placeholder="e.g., Fresh Tomatoes, Olive Oil, etc."
                  required
                />
                <div v-if="form.errors.ingredient_name" class="text-sm text-red-600">
                  {{ form.errors.ingredient_name }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="base_unit">Base Unit *</Label>
                <Input
                  id="base_unit"
                  v-model="form.base_unit"
                  placeholder="kg, lbs, pcs, liters, etc."
                  required
                />
                <div v-if="form.errors.base_unit" class="text-sm text-red-600">
                  {{ form.errors.base_unit }}
                </div>
              </div>
            </div>

            <!-- Package Details -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="package_quantity">Package Quantity *</Label>
                <Input
                  id="package_quantity"
                  v-model.number="form.package_quantity"
                  type="number"
                  step="0.01"
                  min="0.01"
                  required
                />
                <div v-if="form.errors.package_quantity" class="text-sm text-red-600">
                  {{ form.errors.package_quantity }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="package_unit">Package Unit *</Label>
                <Input
                  id="package_unit"
                  v-model="form.package_unit"
                  placeholder="kg, lbs, pcs, etc."
                  required
                />
                <div v-if="form.errors.package_unit" class="text-sm text-red-600">
                  {{ form.errors.package_unit }}
                </div>
              </div>
            </div>

            <!-- Pricing and Terms -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="package_price">Package Price (₱) *</Label>
                <Input
                  id="package_price"
                  v-model.number="form.package_price"
                  type="number"
                  step="0.01"
                  min="0.01"
                  required
                />
                <div v-if="form.errors.package_price" class="text-sm text-red-600">
                  {{ form.errors.package_price }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="lead_time_days">Lead Time (days) *</Label>
                <Input
                  id="lead_time_days"
                  v-model.number="form.lead_time_days"
                  type="number"
                  min="0"
                  required
                />
                <div v-if="form.errors.lead_time_days" class="text-sm text-red-600">
                  {{ form.errors.lead_time_days }}
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="minimum_order_quantity">Minimum Order Quantity *</Label>
              <Input
                id="minimum_order_quantity"
                v-model.number="form.minimum_order_quantity"
                type="number"
                step="0.01"
                min="0.01"
                required
              />
              <div v-if="form.errors.minimum_order_quantity" class="text-sm text-red-600">
                {{ form.errors.minimum_order_quantity }}
              </div>
            </div>

            <!-- Actions -->
            <div class="flex space-x-4">
              <Button 
                type="submit" 
                :disabled="form.processing || !form.restaurant_id || !form.ingredient_name || !form.base_unit"
              >
                {{ form.processing ? 'Adding...' : 'Add Ingredient Offer' }}
              </Button>
              
              <Button type="button" variant="outline" onclick="history.back()">
                Cancel
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </SupplierLayout>
</template>