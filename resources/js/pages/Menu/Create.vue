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
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Plus, Trash2, Calculator, Clock, Users, Edit, Package } from 'lucide-vue-next';
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

interface Props {
  categories: MenuCategory[];
  ingredients: Ingredient[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
  { title: 'Create Dish', href: '/menu/create' },
];

interface DishIngredient {
  ingredient_id?: number;
  ingredient_name: string;
  quantity: number;
  unit: string;
  cost_per_unit?: number;
  is_optional: boolean;
  preparation_note: string;
}

interface Pricing {
  price_type: string;
  base_price: number;
  min_profit_margin: number;
}

const form = useForm({
  dish_name: '',
  description: '',
  category_id: '',
  preparation_time: null as number | null,
  serving_size: null as number | null,
  serving_unit: '',
  image_url: '',
  calories: null as number | null,
  allergens: [] as string[],
  dietary_tags: [] as string[],
  ingredients: [] as DishIngredient[],
  pricing: [
    { price_type: 'dine_in', base_price: 0, min_profit_margin: 25 },
    { price_type: 'takeout', base_price: 0, min_profit_margin: 20 },
  ] as Pricing[],
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
  { value: 'takeout', label: 'Takeout' }
];

// Computed values
const totalIngredientCost = computed(() => {
  return form.ingredients.reduce((total, ingredient) => {
    if (ingredient.cost_per_unit) {
      return total + (ingredient.cost_per_unit * ingredient.quantity);
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
const openAddIngredientModal = () => {
  // Reset the form
  newIngredient.value = {
    ingredient_name: '',
    quantity: 1,
    unit: '',
    cost_per_unit: 0,
    is_optional: false,
    preparation_note: '',
  };
  ingredientSearchTerm.value = '';
  isAddIngredientModalOpen.value = true;
};

const addIngredient = () => {
  if (!newIngredient.value.ingredient_name?.trim()) {
    return; // No ingredient name entered
  }
  
  // Always add as new ingredient (no merging based on ID)
  form.ingredients.push({ ...newIngredient.value });
  
  // Close modal
  isAddIngredientModalOpen.value = false;
};

const removeIngredient = (index: number) => {
  form.ingredients.splice(index, 1);
};

const getIngredientData = (ingredientId?: number) => {
  if (!ingredientId || ingredientId < 0) return null; // Custom ingredient
  return props.ingredients.find(i => i.ingredient_id === ingredientId);
};

const getFilteredIngredients = (searchTerm: string) => {
  if (!searchTerm) return props.ingredients;
  
  return props.ingredients.filter(ingredient => 
    ingredient.ingredient_name.toLowerCase().includes(searchTerm.toLowerCase())
  );
};

const selectIngredientInModal = (ingredient: any) => {
  // Copy ingredient data instead of referencing it
  newIngredient.value.ingredient_id = ingredient.ingredient_id;
  newIngredient.value.ingredient_name = ingredient.ingredient_name;
  newIngredient.value.unit = ingredient.unit_of_measure;
  newIngredient.value.cost_per_unit = ingredient.cost_per_unit;
  ingredientSearchTerm.value = ingredient.ingredient_name;
};

const getNewIngredientCost = computed(() => {
  if (newIngredient.value.cost_per_unit) {
    return newIngredient.value.cost_per_unit * newIngredient.value.quantity;
  }
  return 0;
});

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

const imageUploadRef = ref();
const ingredientSearchTerms = ref<Record<number, string>>({});
const isAddIngredientModalOpen = ref(false);
const ingredientSearchTerm = ref('');

// New ingredient form state
const newIngredient = ref<DishIngredient>({
  ingredient_name: '',
  quantity: 1,
  unit: '',
  cost_per_unit: 0,
  is_optional: false,
  preparation_note: '',
});

const handleImageUpload = async (file: File) => {
  console.log('Starting upload for file:', file.name);
  
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
    console.log('Upload response:', result);
    
    if (result.success) {
      // Update the form with the server URL
      console.log('Old URL:', form.image_url);
      form.image_url = result.url;
      console.log('New URL:', result.url);
      console.log('Form image_url after update:', form.image_url);
    } else {
      console.error('Upload failed:', result.message);
      handleImageError(result.message || 'Upload failed');
    }
  } catch (error) {
    console.error('Upload error:', error);
    handleImageError('Failed to upload image. Please try again.');
  } finally {
    // Reset uploading state
    imageUploadRef.value?.resetUploadingState?.();
  }
};

const handleImageError = (message: string) => {
  console.error('Image error:', message);
};

const submit = () => {
  form.post('/menu', {
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
  <Head title="Create New Dish" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Create New Dish</h1>
        <p class="text-muted-foreground">Add a new dish to your menu with ingredients, pricing, and details</p>
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
              ref="imageUploadRef"
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
            <CardTitle class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Package class="w-5 h-5" />
                Recipe Ingredients
                <Badge variant="secondary">{{ form.ingredients.length }} items</Badge>
              </div>
              <Badge variant="outline" class="text-lg font-semibold">
                Total Cost: ₱{{ totalIngredientCost.toFixed(2) }}
              </Badge>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <!-- Add Ingredient Button -->
            <div class="flex justify-between items-center">
              <Dialog v-model:open="isAddIngredientModalOpen">
                <DialogTrigger as-child>
                  <Button @click="openAddIngredientModal" class="flex items-center gap-2">
                    <Plus class="w-4 h-4" />
                    Add Ingredient
                  </Button>
                </DialogTrigger>
                
                <!-- Add Ingredient Modal -->
                <DialogContent class="max-w-2xl">
                  <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                      <Plus class="w-5 h-5" />
                      Add New Ingredient
                    </DialogTitle>
                  </DialogHeader>
                  
                  <div class="space-y-6 py-4">
                    <!-- Ingredient Input -->
                    <div class="space-y-2">
                      <Label class="text-sm font-medium">Ingredient Name *</Label>
                      <div class="relative">
                        <Input
                          v-model="ingredientSearchTerm"
                          @input="newIngredient.ingredient_name = ingredientSearchTerm"
                          placeholder="Type ingredient name (search existing or enter new)..."
                          class="h-11"
                        />
                        
                        <!-- Search Results -->
                        <div 
                          v-if="ingredientSearchTerm && getFilteredIngredients(ingredientSearchTerm).length > 0"
                          class="absolute z-50 w-full mt-1 bg-background border rounded-lg shadow-lg max-h-60 overflow-auto"
                        >
                          <div 
                            v-for="ing in getFilteredIngredients(ingredientSearchTerm)" 
                            :key="ing.ingredient_id"
                            @click="selectIngredientInModal(ing)"
                            class="p-4 hover:bg-muted cursor-pointer border-b border-border/50 last:border-b-0"
                          >
                            <div class="flex justify-between items-start">
                              <div class="flex-1">
                                <div class="font-medium">{{ ing.ingredient_name }}</div>
                                <div class="text-sm text-muted-foreground">
                                  ₱{{ ing.cost_per_unit.toFixed(2) }} per {{ ing.unit_of_measure }}
                                </div>
                              </div>
                              <div class="text-right ml-3">
                                <div class="text-xs text-muted-foreground">Stock</div>
                                <div class="font-medium">{{ ing.current_stock }}</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Selected Ingredient Info -->
                    <div v-if="newIngredient.ingredient_id && newIngredient.ingredient_id > 0" class="p-4 bg-muted/30 rounded-lg">
                      <div class="flex items-center justify-between">
                        <div>
                          <div class="font-medium">{{ newIngredient.ingredient_name }}</div>
                          <div class="text-sm text-muted-foreground">
                            ₱{{ newIngredient.cost_per_unit?.toFixed(2) || '0.00' }} per {{ newIngredient.unit }}
                          </div>
                        </div>
                        <div class="text-right">
                          <div class="text-xs text-muted-foreground">Available Stock</div>
                          <div class="font-medium">{{ getIngredientData(newIngredient.ingredient_id)?.current_stock || 'N/A' }}</div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Custom Ingredient Info -->
                    <div v-else-if="newIngredient.ingredient_name" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                      <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        <div class="font-medium text-blue-800">Custom Ingredient: {{ newIngredient.ingredient_name }}</div>
                      </div>
                      <div class="text-sm text-blue-600 mt-1">
                        This ingredient will be added as a new item to the recipe
                      </div>
                    </div>

                    <!-- Quantity, Unit, and Cost -->
                    <div class="grid grid-cols-3 gap-4">
                      <div class="space-y-2">
                        <Label>Quantity *</Label>
                        <Input
                          v-model="newIngredient.quantity"
                          type="number"
                          step="0.01"
                          min="0.01"
                          placeholder="1.0"
                          class="text-center"
                        />
                      </div>
                      <div class="space-y-2">
                        <Label>Unit *</Label>
                        <Input
                          v-model="newIngredient.unit"
                          placeholder="e.g., kg, lbs, pieces, cups"
                        />
                      </div>
                      <div class="space-y-2">
                        <Label>Cost per {{ newIngredient.unit || 'Unit' }} (₱)</Label>
                        <Input
                          v-model="newIngredient.cost_per_unit"
                          type="number"
                          step="0.01"
                          min="0"
                          placeholder="0.00"
                        />
                        <div class="text-xs text-muted-foreground">
                          Base cost for 1 {{ newIngredient.unit || 'unit' }} of {{ newIngredient.ingredient_name || 'this ingredient' }}
                        </div>
                      </div>
                    </div>

                    <!-- Preparation Note -->
                    <div class="space-y-2">
                      <Label>Preparation Instructions</Label>
                      <Textarea
                        v-model="newIngredient.preparation_note"
                        placeholder="e.g., diced finely, marinated overnight, sautéed until golden"
                        class="min-h-[80px]"
                      />
                    </div>

                    <!-- Optional Checkbox and Cost -->
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2">
                        <Checkbox 
                          id="modal-optional"
                          v-model="newIngredient.is_optional"
                        />
                        <Label for="modal-optional" class="cursor-pointer">
                          Optional ingredient
                        </Label>
                      </div>
                      <div class="text-right">
                        <div class="text-sm text-muted-foreground">Total Cost for Recipe</div>
                        <div class="text-xl font-bold text-green-600">
                          ₱{{ getNewIngredientCost.toFixed(2) }}
                        </div>
                        <div class="text-xs text-muted-foreground mt-1">
                          {{ newIngredient.quantity }} {{ newIngredient.unit }} × ₱{{ (newIngredient.cost_per_unit || 0).toFixed(2) }}
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <DialogFooter>
                    <Button 
                      variant="outline" 
                      @click="isAddIngredientModalOpen = false"
                    >
                      Cancel
                    </Button>
                    <Button 
                      @click="addIngredient"
                      :disabled="!newIngredient.ingredient_name?.trim() || !newIngredient.quantity || !newIngredient.unit?.trim()"
                    >
                      Add to Recipe
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>
            </div>

            <!-- Ingredients Table -->
            <div v-if="form.ingredients.length > 0" class="border rounded-lg">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-12">#</TableHead>
                    <TableHead>Ingredient</TableHead>
                    <TableHead class="text-center">Quantity</TableHead>
                    <TableHead class="text-center">Unit</TableHead>
                    <TableHead class="text-right">Cost</TableHead>
                    <TableHead class="text-center">Optional</TableHead>
                    <TableHead class="w-20">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(ingredient, index) in form.ingredients" :key="index" class="group">
                    <TableCell class="font-medium">{{ index + 1 }}</TableCell>
                    <TableCell>
                      <div class="flex flex-col">
                        <div class="font-medium">
                          {{ ingredient.ingredient_name }}
                        </div>
                        <div v-if="ingredient.preparation_note" class="text-xs text-muted-foreground mt-1">
                          {{ ingredient.preparation_note }}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell class="text-center font-medium">{{ ingredient.quantity }}</TableCell>
                    <TableCell class="text-center text-muted-foreground">{{ ingredient.unit }}</TableCell>
                    <TableCell class="text-right font-medium text-green-600">
                      ₱{{ ingredient.cost_per_unit 
                        ? (ingredient.cost_per_unit * ingredient.quantity).toFixed(2) 
                        : '0.00' }}
                    </TableCell>
                    <TableCell class="text-center">
                      <Badge v-if="ingredient.is_optional" variant="secondary" class="text-xs">
                        Optional
                      </Badge>
                      <span v-else class="text-muted-foreground">—</span>
                    </TableCell>
                    <TableCell>
                      <div class="flex items-center gap-1">
                        <Button 
                          variant="ghost" 
                          size="sm"
                          @click="removeIngredient(index)"
                          class="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                        >
                          <Trash2 class="w-3 h-3" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <div class="mx-auto w-20 h-20 bg-muted rounded-full flex items-center justify-center mb-4">
                <Package class="w-10 h-10 text-muted-foreground" />
              </div>
              <h3 class="text-lg font-medium mb-2">No ingredients added yet</h3>
              <p class="text-muted-foreground mb-6">Click "Add Ingredient" above to start building your recipe</p>
            </div>

            <!-- Validation error -->
            <div v-if="form.errors.ingredients" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
              <p class="text-sm text-destructive font-medium">
                {{ form.errors.ingredients }}
              </p>
            </div>
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
            <div v-for="(pricing, index) in form.pricing" :key="pricing.price_type" class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium">{{ priceTypes.find(p => p.value === pricing.price_type)?.label }}</h4>
                <Badge variant="outline">
                  Suggested: ₱{{ suggestedPrices[index]?.suggested_price.toFixed(2) }}
                </Badge>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
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
            </div>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-end space-x-2">
          <Button type="button" variant="outline" @click="$inertia.visit('/menu')">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Clock v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ form.processing ? 'Creating...' : 'Create Dish' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>