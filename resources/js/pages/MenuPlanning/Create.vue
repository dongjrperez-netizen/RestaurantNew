<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  selling_price: number;
  category?: string;
  is_available: boolean;
}

interface DishPlan {
  dish_id: number;
  planned_quantity: number;
  meal_type: string;
  planned_date: string;
  day_of_week?: number;
  notes?: string;
}

interface Props {
  dishes: Dish[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Menu Planning', href: '/menu-plans' },
  { title: 'Create Plan', href: '/menu-plans/create' },
];

const form = useForm({
  plan_name: '',
  plan_type: 'daily' as 'daily' | 'weekly',
  start_date: '',
  end_date: '',
  description: '',
  dishes: [] as DishPlan[],
});

const mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];
const daysOfWeek = [
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' },
  { value: 7, label: 'Sunday' },
];

const selectedDate = ref('');
const selectedMealType = ref('');
const showDishSelector = ref(false);

// Generate date range based on plan type and dates
const dateRange = computed(() => {
  if (!form.start_date || !form.end_date) return [];

  const start = new Date(form.start_date);
  const end = new Date(form.end_date);
  const dates = [];

  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    dates.push(new Date(d).toISOString().split('T')[0]);
  }

  return dates;
});

// Watch plan type to set appropriate end date
watch(() => form.plan_type, (newType) => {
  if (form.start_date) {
    const startDate = new Date(form.start_date);
    if (newType === 'daily') {
      form.end_date = form.start_date;
    } else if (newType === 'weekly') {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 6);
      form.end_date = endDate.toISOString().split('T')[0];
    }
  }
});

// Watch start date to auto-set end date
watch(() => form.start_date, (newStartDate) => {
  if (newStartDate) {
    const startDate = new Date(newStartDate);
    if (form.plan_type === 'daily') {
      form.end_date = newStartDate;
    } else if (form.plan_type === 'weekly') {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 6);
      form.end_date = endDate.toISOString().split('T')[0];
    }
  }
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  });
};

const getDishesForDateAndMeal = (date: string, mealType: string) => {
  return form.dishes.filter(d => d.planned_date === date && d.meal_type === mealType);
};

const addDishToPlan = (dish: Dish) => {
  if (!selectedDate.value || !selectedMealType.value) return;

  // Check if dish already exists for this date and meal
  const exists = form.dishes.some(d =>
    d.dish_id === dish.dish_id &&
    d.planned_date === selectedDate.value &&
    d.meal_type === selectedMealType.value
  );

  if (exists) {
    alert('This dish is already planned for this date and meal type');
    return;
  }

  const dayOfWeek = form.plan_type === 'weekly' ? new Date(selectedDate.value).getDay() : undefined;

  form.dishes.push({
    dish_id: dish.dish_id,
    planned_quantity: 1,
    meal_type: selectedMealType.value,
    planned_date: selectedDate.value,
    day_of_week: dayOfWeek === 0 ? 7 : dayOfWeek, // Convert Sunday (0) to 7
    notes: '',
  });

  showDishSelector.value = false;
};

const removeDishFromPlan = (index: number) => {
  form.dishes.splice(index, 1);
};

const getDishName = (dishId: number) => {
  const dish = props.dishes.find(d => d.dish_id === dishId);
  return dish?.dish_name || 'Unknown Dish';
};

const openDishSelector = (date: string, mealType: string) => {
  selectedDate.value = date;
  selectedMealType.value = mealType;
  showDishSelector.value = true;
};

const submit = () => {
  form.post(route('menu-plans.store'));
};

const cancel = () => {
  router.visit(route('menu-plans.index'));
};
</script>

<template>
  <Head title="Create Menu Plan" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-8 p-6 bg-gradient-to-br from-gray-50 via-white to-blue-50 min-h-screen">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-3xl font-extrabold text-gray-800 dark:text-gray-100">Create Menu Plan</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Plan your daily or weekly menu</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-8">
        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Plan Name *
              </label>
              <input
                v-model="form.plan_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                placeholder="Enter plan name"
              />
              <div v-if="form.errors.plan_name" class="text-red-500 text-sm mt-1">{{ form.errors.plan_name }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Plan Type *
              </label>
              <select
                v-model="form.plan_type"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
              </select>
              <div v-if="form.errors.plan_type" class="text-red-500 text-sm mt-1">{{ form.errors.plan_type }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Start Date *
              </label>
              <input
                v-model="form.start_date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              />
              <div v-if="form.errors.start_date" class="text-red-500 text-sm mt-1">{{ form.errors.start_date }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                End Date *
              </label>
              <input
                v-model="form.end_date"
                type="date"
                required
                :min="form.start_date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              />
              <div v-if="form.errors.end_date" class="text-red-500 text-sm mt-1">{{ form.errors.end_date }}</div>
            </div>
          </div>

          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Description
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              placeholder="Enter plan description (optional)"
            ></textarea>
            <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
          </div>
        </div>

        <!-- Menu Planning Grid -->
        <div v-if="dateRange.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Menu Planning</h3>

          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <th class="text-left py-3 px-2 font-medium text-gray-700 dark:text-gray-300">Date</th>
                  <th v-for="mealType in mealTypes" :key="mealType" class="text-center py-3 px-2 font-medium text-gray-700 dark:text-gray-300 capitalize">
                    {{ mealType }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="date in dateRange" :key="date" class="border-b border-gray-100 dark:border-gray-700">
                  <td class="py-3 px-2 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                    {{ formatDate(date) }}
                  </td>
                  <td v-for="mealType in mealTypes" :key="`${date}-${mealType}`" class="py-3 px-2 min-w-[200px]">
                    <div class="space-y-2">
                      <!-- Planned dishes for this date/meal -->
                      <div v-for="(dish, index) in getDishesForDateAndMeal(date, mealType)" :key="index"
                           class="bg-blue-50 dark:bg-blue-900 rounded-lg p-2 border border-blue-200 dark:border-blue-700">
                        <div class="flex items-center justify-between">
                          <div class="flex-1">
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                              {{ getDishName(dish.dish_id) }}
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                              <input
                                v-model="dish.planned_quantity"
                                type="number"
                                min="1"
                                class="w-16 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                              />
                              <span class="text-xs text-gray-600 dark:text-gray-400">qty</span>
                            </div>
                          </div>
                          <button
                            type="button"
                            @click="removeDishFromPlan(form.dishes.findIndex(d => d === dish))"
                            class="text-red-500 hover:text-red-700 ml-2"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                          </button>
                        </div>
                        <input
                          v-model="dish.notes"
                          type="text"
                          placeholder="Notes (optional)"
                          class="w-full mt-2 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                      </div>

                      <!-- Add dish button -->
                      <button
                        type="button"
                        @click="openDishSelector(date, mealType)"
                        class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 hover:border-blue-500 hover:text-blue-500 transition-colors text-sm"
                      >
                        + Add Dish
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between">
          <button
            type="button"
            @click="cancel"
            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition disabled:opacity-50"
          >
            {{ form.processing ? 'Creating...' : 'Create Menu Plan' }}
          </button>
        </div>
      </form>

      <!-- Dish Selector Modal -->
      <div v-if="showDishSelector" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Select Dish for {{ formatDate(selectedDate) }} - {{ selectedMealType }}
            </h3>
            <button
              @click="showDishSelector = false"
              class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="dish in dishes" :key="dish.dish_id"
                 class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition"
                 @click="addDishToPlan(dish)">
              <h4 class="font-medium text-gray-900 dark:text-white">{{ dish.dish_name }}</h4>
              <p v-if="dish.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ dish.description }}</p>
              <p class="text-sm text-blue-600 dark:text-blue-400 mt-2">${{ dish.selling_price }}</p>
              <span v-if="dish.category" class="inline-block mt-2 px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded">
                {{ dish.category }}
              </span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>