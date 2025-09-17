<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import Badge from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, onMounted } from 'vue';
import { Plus, Trash2, Calculator, Clock, Users, Save } from 'lucide-vue-next';
import ImageUpload from '@/components/ImageUpload.vue';

interface MenuCategory {
  category_id: number;
  category_name: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  unit_of_measure: string;
  cost_per_unit: number;
  current_stock: number;
}

interface DishIngredient {
  ingredient_id: number;
  ingredient_name?: string;
  quantity: number;
  unit: string;
  is_optional: boolean;
  preparation_note: string;
  cost_per_unit?: number;
}

interface DishPricing {
  pricing_id?: number;
  price_type: string;
  base_price: number;
  promotional_price?: number;
  promo_start_date?: string;
  promo_end_date?: string;
  min_profit_margin: number;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  category_id: number;
  preparation_time?: number;
  serving_size?: number;
  serving_unit?: string;
  image_url?: string;
  calories?: number;
  allergens?: string[];
  dietary_tags?: string[];
  status: 'draft' | 'active' | 'inactive' | 'archived';
  ingredients?: DishIngredient[];
  pricing?: DishPricing[];
}

interface Props {
  dish: Dish;
  categories: MenuCategory[];
  ingredients: Ingredient[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
  { title: props.dish.dish_name, href: `/menu/${props.dish.dish_id}` },
  { title: 'Edit', href: `/menu/${props.dish.dish_id}/edit` },
];

interface EditDishIngredient {
  ingredient_id: number;
  quantity: number;
  unit: string;
  is_optional: boolean;
  preparation_note: string;
}

interface EditPricing {
  pricing_id?: number;
  price_type: string;
  base_price: number;
  promotional_price?: number;
  promo_start_date?: string;
  promo_end_date?: string;
  min_profit_margin: number;
}

const form = useForm({
  dish_name: props.dish.dish_name,
  description: props.dish.description || '',
  category_id: props.dish.category_id.toString(),
  preparation_time: props.dish.preparation_time || null as number | null,
  serving_size: props.dish.serving_size || null as number | null,
  serving_unit: props.dish.serving_unit || '',
  image_url: props.dish.image_url || '',
  calories: props.dish.calories || null as number | null,
  allergens: props.dish.allergens || [] as string[],
  dietary_tags: props.dish.dietary_tags || [] as string[],
  ingredients: [] as EditDishIngredient[],
  pricing: [] as EditPricing[],
});

// Initialize form data from existing dish
onMounted(() => {
  // Initialize ingredients
  if (props.dish.ingredients) {
    form.ingredients = props.dish.ingredients.map(ingredient => ({
      ingredient_id: ingredient.ingredient_id,
      quantity: ingredient.quantity,
      unit: ingredient.unit,
      is_optional: ingredient.is_optional,
      preparation_note: ingredient.preparation_note || '',
    }));
  }

  // Initialize pricing
  if (props.dish.pricing) {
    form.pricing = props.dish.pricing.map(pricing => ({
      pricing_id: pricing.pricing_id,
      price_type: pricing.price_type,
      base_price: pricing.base_price,
      promotional_price: pricing.promotional_price,
      promo_start_date: pricing.promo_start_date,
      promo_end_date: pricing.promo_end_date,
      min_profit_margin: pricing.min_profit_margin,
    }));
  } else {
    // Default pricing structure if none exists
    form.pricing = [
      { price_type: 'dine_in', base_price: 0, min_profit_margin: 25 },
      { price_type: 'takeout', base_price: 0, min_profit_margin: 20 },
      { price_type: 'delivery', base_price: 0, min_profit_margin: 15 },
    ];
  }
});

// Available options
const allergenOptions = [
  'Gluten', 'Dairy', 'Eggs', 'Fish', 'Shellfish', 'Tree Nuts', 'Peanuts', 'Soy', 'Sesame'
];

const dietaryTagOptions = [
  'Vegetarian', 'Vegan', 'Gluten-Free', 'Dairy-Free', 'Keto', 'Paleo', 'Low-Carb', 'Halal'
];

const priceTypes = [
  { value: 'dine_in', label: 'Dine In' },
  { value: 'takeout', label: 'Takeout' },
  { value: 'delivery', label: 'Delivery' }
];

// Computed values
const totalIngredientCost = computed(() => {
  return form.ingredients.reduce((total, ingredient) => {
    const ingredientData = props.ingredients.find(i => i.ingredient_id === ingredient.ingredient_id);
    if (ingredientData) {
      return total + (ingredientData.cost_per_unit * ingredient.quantity);
    }
    return total;
  }, 0);
});

const suggestedPrices = computed(() => {
  const baseCost = totalIngredientCost.value;
  return form.pricing.map(pricing => ({
    ...pricing,
    suggested_price: baseCost / (1 - pricing.min_profit_margin / 100)
  }));
});

// Methods
const addIngredient = () => {
  form.ingredients.push({
    ingredient_id: 0,
    quantity: 0,
    unit: '',
    is_optional: false,
    preparation_note: '',
  });
};

const removeIngredient = (index: number) => {
  form.ingredients.splice(index, 1);
};

const getIngredientData = (ingredientId: number) => {
  return props.ingredients.find(i => i.ingredient_id === ingredientId);
};

const updateIngredientUnit = (index: number, ingredientId: number) => {
  const ingredient = getIngredientData(ingredientId);
  if (ingredient) {
    form.ingredients[index].unit = ingredient.unit_of_measure;
  }
};

const toggleAllergen = (allergen: string) => {
  const index = form.allergens.indexOf(allergen);
  if (index > -1) {
    form.allergens.splice(index, 1);
  } else {
    form.allergens.push(allergen);
  }
};

const toggleDietaryTag = (tag: string) => {
  const index = form.dietary_tags.indexOf(tag);
  if (index > -1) {
    form.dietary_tags.splice(index, 1);
  } else {
    form.dietary_tags.push(tag);
  }
};

const applySuggestedPrice = (index: number) => {
  const suggested = suggestedPrices.value[index];
  form.pricing[index].base_price = Math.ceil(suggested.suggested_price);
};

const addPricingType = () => {
  const existingTypes = form.pricing.map(p => p.price_type);
  const availableType = priceTypes.find(type => !existingTypes.includes(type.value));
  
  if (availableType) {
    form.pricing.push({
      price_type: availableType.value,
      base_price: 0,
      min_profit_margin: 20,
    });
  }
};

const removePricingType = (index: number) => {
  form.pricing.splice(index, 1);
};

const handleImageUpload = async (file: File) => {
  try {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', 'dish');

    const response = await fetch('/api/images/upload', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: formData
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    
    if (result.success) {
      // Replace the blob URL with the server URL
      form.image_url = result.url;
      console.log('Image uploaded successfully:', result.url);
    } else {
      console.error('Upload failed:', result.message);
      handleImageError(result.message || 'Upload failed');
    }
  } catch (error) {
    console.error('Upload error:', error);
    handleImageError('Failed to upload image. Please try again.');
  }
};

const handleImageError = (message: string) => {
  console.error('Image error:', message);
};

const submit = () => {
  form.put(`/menu/${props.dish.dish_id}`, {
    onSuccess: () => {
      // Handled by redirect
    },
    onError: () => {
      // Form errors will be displayed
    }
  });
};
</script>

<template>
  <Head :title="`Edit ${dish.dish_name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Edit Dish</h1>
          <p class="text-muted-foreground">Update dish information, ingredients, and pricing</p>
        </div>
        <div class="flex items-center gap-2">
          <Badge :variant="dish.status === 'active' ? 'default' : 'secondary'">
            {{ dish.status }}
          </Badge>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Users class="w-5 h-5" />
              Basic Information
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="dish_name">Dish Name *</Label>
                <Input
                  id="dish_name"
                  v-model="form.dish_name"
                  placeholder="Enter dish name"
                  :class="{ 'border-red-500': form.errors.dish_name }"
                />
                <p v-if="form.errors.dish_name" class="text-sm text-red-500">
                  {{ form.errors.dish_name }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="category_id">Category *</Label>
                <Select v-model="form.category_id">
                  <SelectTrigger :class="{ 'border-red-500': form.errors.category_id }">
                    <SelectValue placeholder="Select category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem 
                      v-for="category in categories" 
                      :key="category.category_id"
                      :value="category.category_id.toString()"
                    >
                      {{ category.category_name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.category_id" class="text-sm text-red-500">
                  {{ form.errors.category_id }}
                </p>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <Textarea
                id="description"
                v-model="form.description"
                placeholder="Describe the dish, its taste, and preparation method"
                class="min-h-[100px]"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="preparation_time">Prep Time (minutes)</Label>
                <Input
                  id="preparation_time"
                  v-model="form.preparation_time"
                  type="number"
                  placeholder="15"
                />
              </div>

              <div class="space-y-2">
                <Label for="serving_size">Serving Size</Label>
                <Input
                  id="serving_size"
                  v-model="form.serving_size"
                  type="number"
                  step="0.1"
                  placeholder="1"
                />
              </div>

              <div class="space-y-2">
                <Label for="serving_unit">Serving Unit</Label>
                <Input
                  id="serving_unit"
                  v-model="form.serving_unit"
                  placeholder="plate, bowl, cup"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="calories">Calories (optional)</Label>
                <Input
                  id="calories"
                  v-model="form.calories"
                  type="number"
                  placeholder="350"
                />
              </div>
            </div>

            <!-- Image Upload -->
            <ImageUpload
              v-model="form.image_url"
              @upload="handleImageUpload"
              @error="handleImageError"
              :disabled="form.processing"
            />
          </CardContent>
        </Card>

        <!-- Allergens and Dietary Tags -->
        <Card>
          <CardHeader>
            <CardTitle>Allergens & Dietary Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label class="text-base font-medium">Allergens</Label>
              <p class="text-sm text-muted-foreground mb-3">Select all allergens present in this dish</p>
              <div class="flex flex-wrap gap-2">
                <Badge
                  v-for="allergen in allergenOptions"
                  :key="allergen"
                  :variant="form.allergens.includes(allergen) ? 'default' : 'outline'"
                  class="cursor-pointer"
                  @click="toggleAllergen(allergen)"
                >
                  {{ allergen }}
                </Badge>
              </div>
            </div>

            <div>
              <Label class="text-base font-medium">Dietary Tags</Label>
              <p class="text-sm text-muted-foreground mb-3">Select dietary categories this dish fits</p>
              <div class="flex flex-wrap gap-2">
                <Badge
                  v-for="tag in dietaryTagOptions"
                  :key="tag"
                  :variant="form.dietary_tags.includes(tag) ? 'default' : 'outline'"
                  class="cursor-pointer"
                  @click="toggleDietaryTag(tag)"
                >
                  {{ tag }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recipe Builder -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Calculator class="w-5 h-5" />
              Recipe Builder
              <Badge variant="outline">Total Cost: ₱{{ totalIngredientCost.toFixed(2) }}</Badge>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-if="form.ingredients.length === 0" class="text-center py-8 text-muted-foreground">
              <p>No ingredients added yet. Click "Add Ingredient" to start building your recipe.</p>
            </div>

            <div v-for="(ingredient, index) in form.ingredients" :key="index" class="border rounded-lg p-4 space-y-3">
              <div class="flex items-center justify-between">
                <h4 class="font-medium">Ingredient {{ index + 1 }}</h4>
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  @click="removeIngredient(index)"
                >
                  <Trash2 class="w-4 h-4" />
                </Button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="space-y-2">
                  <Label>Ingredient *</Label>
                  <Select 
                    v-model="ingredient.ingredient_id" 
                    @update:model-value="updateIngredientUnit(index, ingredient.ingredient_id)"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select ingredient" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="ing in ingredients" 
                        :key="ing.ingredient_id"
                        :value="ing.ingredient_id.toString()"
                      >
                        {{ ing.ingredient_name }} (₱{{ ing.cost_per_unit }}/{{ ing.unit_of_measure }})
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="space-y-2">
                  <Label>Quantity *</Label>
                  <Input
                    v-model="ingredient.quantity"
                    type="number"
                    step="0.1"
                    min="0"
                    placeholder="0"
                  />
                </div>

                <div class="space-y-2">
                  <Label>Unit</Label>
                  <Input
                    v-model="ingredient.unit"
                    placeholder="kg, pcs, ml"
                  />
                </div>

                <div class="space-y-2">
                  <Label>Cost</Label>
                  <div class="text-sm font-medium pt-2">
                    ₱{{ getIngredientData(ingredient.ingredient_id) 
                      ? (getIngredientData(ingredient.ingredient_id)!.cost_per_unit * ingredient.quantity).toFixed(2) 
                      : '0.00' }}
                  </div>
                </div>
              </div>

              <div class="space-y-2">
                <Label>Preparation Note</Label>
                <Input
                  v-model="ingredient.preparation_note"
                  placeholder="e.g., diced, chopped, marinated overnight"
                />
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox 
                  :id="`optional-${index}`"
                  v-model="ingredient.is_optional"
                />
                <Label :for="`optional-${index}`" class="text-sm">
                  This ingredient is optional
                </Label>
              </div>
            </div>

            <Button 
              type="button" 
              variant="outline" 
              @click="addIngredient"
              class="w-full"
            >
              <Plus class="w-4 h-4 mr-2" />
              Add Ingredient
            </Button>

            <p v-if="form.errors.ingredients" class="text-sm text-red-500">
              {{ form.errors.ingredients }}
            </p>
          </CardContent>
        </Card>

        <!-- Pricing Strategy -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Calculator class="w-5 h-5" />
              Pricing Strategy
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-for="(pricing, index) in form.pricing" :key="`${pricing.price_type}-${index}`" class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium">{{ priceTypes.find(p => p.value === pricing.price_type)?.label }}</h4>
                <div class="flex items-center gap-2">
                  <Badge variant="outline">
                    Suggested: ₱{{ suggestedPrices[index]?.suggested_price.toFixed(2) }}
                  </Badge>
                  <Button 
                    v-if="form.pricing.length > 1"
                    type="button"
                    variant="outline" 
                    size="sm"
                    @click="removePricingType(index)"
                  >
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="space-y-2">
                  <Label>Base Price *</Label>
                  <Input
                    v-model="pricing.base_price"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                  />
                </div>

                <div class="space-y-2">
                  <Label>Promotional Price</Label>
                  <Input
                    v-model="pricing.promotional_price"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                  />
                </div>

                <div class="space-y-2">
                  <Label>Min Profit Margin (%)</Label>
                  <Input
                    v-model="pricing.min_profit_margin"
                    type="number"
                    step="1"
                    min="0"
                    max="100"
                    placeholder="25"
                  />
                </div>

                <div class="flex items-end">
                  <Button 
                    type="button"
                    variant="outline" 
                    size="sm"
                    @click="applySuggestedPrice(index)"
                  >
                    Use Suggested Price
                  </Button>
                </div>
              </div>

              <div v-if="pricing.promotional_price" class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                <div class="space-y-2">
                  <Label>Promo Start Date</Label>
                  <Input
                    v-model="pricing.promo_start_date"
                    type="date"
                  />
                </div>

                <div class="space-y-2">
                  <Label>Promo End Date</Label>
                  <Input
                    v-model="pricing.promo_end_date"
                    type="date"
                  />
                </div>
              </div>
            </div>

            <Button 
              v-if="form.pricing.length < 3"
              type="button" 
              variant="outline" 
              @click="addPricingType"
              class="w-full"
            >
              <Plus class="w-4 h-4 mr-2" />
              Add Pricing Type
            </Button>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-end space-x-2">
          <Button type="button" variant="outline" @click="$inertia.visit(`/menu/${dish.dish_id}`)">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Save v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ form.processing ? 'Updating...' : 'Update Dish' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>