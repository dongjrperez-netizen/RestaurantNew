<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';

interface MenuPlan {
  menu_plan_id: number;
  plan_name: string;
  plan_type: 'daily' | 'weekly';
  start_date: string;
  end_date: string;
  description?: string;
  is_active: boolean;
  created_at: string;
  dishes_count?: number;
}

interface Props {
  menuPlans: {
    data: MenuPlan[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Menu Planning', href: '/menu-plans' },
];

const selectedPlans = ref<number[]>([]);

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const getPlanTypeColor = (type: string) => {
  switch (type) {
    case 'daily':
      return 'bg-blue-100 text-blue-800';
    case 'weekly':
      return 'bg-green-100 text-green-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const getStatusColor = (isActive: boolean) => {
  return isActive
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800';
};

const deletePlan = (id: number) => {
  if (confirm('Are you sure you want to delete this menu plan?')) {
    router.delete(`/menu-plans/${id}`);
  }
};

const toggleActive = (id: number) => {
  router.post(`/menu-plans/${id}/toggle-active`);
};

const toggleSelectAll = () => {
  if (selectedPlans.value.length === props.menuPlans.data.length) {
    selectedPlans.value = [];
  } else {
    selectedPlans.value = props.menuPlans.data.map(plan => plan.menu_plan_id);
  }
};

const deleteSelected = () => {
  if (selectedPlans.value.length === 0) return;

  if (confirm(`Are you sure you want to delete ${selectedPlans.value.length} menu plan(s)?`)) {
    selectedPlans.value.forEach(id => {
      router.delete(`/menu-plans/${id}`, {
        preserveState: true,
      });
    });
    selectedPlans.value = [];
  }
};
</script>

<template>
  <Head title="Menu Planning" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-8 p-6 bg-gradient-to-br from-gray-50 via-white to-blue-50 min-h-screen">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-3xl font-extrabold text-gray-800 dark:text-gray-100">Menu Planning</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your daily and weekly menu plans</p>
        </div>
        <Link
          :href="route('menu-plans.create')"
          class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-lg shadow transition flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Create Menu Plan
        </Link>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Plans</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ menuPlans.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Plans</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ menuPlans.data.filter(plan => plan.is_active).length }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border">
          <div class="flex items-center">
            <div class="p-2 bg-indigo-100 rounded-lg">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3M8 7v1m8-1v1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Daily Plans</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ menuPlans.data.filter(plan => plan.plan_type === 'daily').length }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3M8 7v1m8-1v1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Weekly Plans</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ menuPlans.data.filter(plan => plan.plan_type === 'weekly').length }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedPlans.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
          <span class="text-blue-700 font-medium">{{ selectedPlans.length }} menu plan(s) selected</span>
          <div class="flex gap-2">
            <button
              @click="deleteSelected"
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition"
            >
              Delete Selected
            </button>
            <button
              @click="selectedPlans = []"
              class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition"
            >
              Clear Selection
            </button>
          </div>
        </div>
      </div>

      <!-- Menu Plans Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-6 py-3 text-left">
                  <input
                    type="checkbox"
                    @change="toggleSelectAll"
                    :checked="selectedPlans.length === menuPlans.data.length && menuPlans.data.length > 0"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                  >
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Plan Name
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Type
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Duration
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Created
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="plan in menuPlans.data" :key="plan.menu_plan_id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4">
                  <input
                    type="checkbox"
                    v-model="selectedPlans"
                    :value="plan.menu_plan_id"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                  >
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ plan.plan_name }}
                      </div>
                      <div v-if="plan.description" class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                        {{ plan.description }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getPlanTypeColor(plan.plan_type)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize">
                    {{ plan.plan_type }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  <div>{{ formatDate(plan.start_date) }}</div>
                  <div class="text-gray-500 dark:text-gray-400">to {{ formatDate(plan.end_date) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusColor(plan.is_active)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ plan.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatDate(plan.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2">
                    <Link
                      :href="route('menu-plans.show', plan.menu_plan_id)"
                      class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                      title="View"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                      </svg>
                    </Link>
                    <Link
                      :href="route('menu-plans.edit', plan.menu_plan_id)"
                      class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300"
                      title="Edit"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                    </Link>
                    <button
                      @click="toggleActive(plan.menu_plan_id)"
                      :class="plan.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                      :title="plan.is_active ? 'Deactivate' : 'Activate'"
                    >
                      <svg v-if="plan.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                    </button>
                    <button
                      @click="deletePlan(plan.menu_plan_id)"
                      class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                      title="Delete"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="menuPlans.data.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No menu plans</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating your first menu plan.</p>
          <div class="mt-6">
            <Link
              :href="route('menu-plans.create')"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Create Menu Plan
            </Link>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="menuPlans.last_page > 1" class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
          <Link
            v-if="menuPlans.current_page > 1"
            :href="route('menu-plans.index', { page: menuPlans.current_page - 1 })"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Previous
          </Link>
          <Link
            v-if="menuPlans.current_page < menuPlans.last_page"
            :href="route('menu-plans.index', { page: menuPlans.current_page + 1 })"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Next
          </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Showing
              <span class="font-medium">{{ (menuPlans.current_page - 1) * menuPlans.per_page + 1 }}</span>
              to
              <span class="font-medium">{{ Math.min(menuPlans.current_page * menuPlans.per_page, menuPlans.total) }}</span>
              of
              <span class="font-medium">{{ menuPlans.total }}</span>
              results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <!-- Pagination buttons would go here -->
            </nav>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>