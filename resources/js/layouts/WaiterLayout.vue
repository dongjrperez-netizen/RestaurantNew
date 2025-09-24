<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import {
  Menu,
  LogOut,
  Users,
  TableProperties,
  X
} from 'lucide-vue-next';

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  email: string;
  role: {
    role_name: string;
  };
}

interface Props {
  employee: Employee;
}

defineProps<Props>();

const sidebarOpen = ref(false);

const logoutForm = useForm({});

const logout = () => {
  logoutForm.post(route('employee.logout'));
};
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Mobile-Optimized Header -->
    <header class="bg-white shadow-sm border-b">
      <div class="flex items-center justify-between px-3 sm:px-4 h-12">
        <!-- Menu Button -->
        <Button
          variant="ghost"
          size="sm"
          @click="sidebarOpen = !sidebarOpen"
          class="p-2"
        >
          <Menu class="h-5 w-5" />
        </Button>

        <!-- Page Title -->
        <div class="flex-1 text-center lg:text-left lg:ml-4">
          <h1 class="text-lg sm:text-xl font-semibold text-gray-900 truncate">
            <slot name="title">Take Order</slot>
          </h1>
        </div>

        <!-- User Info -->
        <div class="flex items-center gap-1 sm:gap-3">
          <div class="text-right hidden md:block">
            <p class="text-sm font-medium text-gray-900">{{ employee.firstname }} {{ employee.lastname }}</p>
            <p class="text-xs text-gray-500">{{ employee.role.role_name }}</p>
          </div>
          <Button
            @click="logout"
            variant="ghost"
            size="sm"
            class="text-red-600 hover:text-red-700 hover:bg-red-50 p-2"
            :disabled="logoutForm.processing"
          >
            <LogOut class="h-4 w-4" />
            <span class="text-sm hidden sm:inline ml-2">{{ logoutForm.processing ? 'Logging out...' : 'Logout' }}</span>
          </Button>
        </div>
      </div>
    </header>

    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/50 z-40 lg:hidden"
      @click="sidebarOpen = false"
    ></div>

    <!-- Mobile-Optimized Sidebar -->
    <div
      class="fixed top-12 left-0 h-[calc(100vh-3rem)] bg-white shadow-lg border-r transition-transform duration-300 z-50"
      :class="[
        sidebarOpen ? 'translate-x-0 w-64 sm:w-72' : '-translate-x-full w-0',
        'lg:relative lg:top-0 lg:h-screen lg:z-40'
      ]"
    >
      <div class="flex flex-col h-full" v-if="sidebarOpen">
        <!-- Sidebar Header -->
        <div class="p-4 sm:p-6 border-b bg-primary text-primary-foreground">
          <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1">
              <h2 class="text-lg font-semibold truncate">Waiter Dashboard</h2>
              <p class="text-sm opacity-90 truncate">{{ employee.firstname }} {{ employee.lastname }}</p>
              <p class="text-xs opacity-75">{{ employee.role.role_name }}</p>
            </div>
            <Button
              variant="ghost"
              size="icon"
              @click="sidebarOpen = false"
              class="lg:hidden text-primary-foreground hover:bg-primary-foreground/20 ml-2 p-2"
            >
              <X class="h-5 w-5" />
            </Button>
          </div>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 p-4 sm:p-6 space-y-3">
          <Link
            :href="route('waiter.dashboard')"
            class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors touch-manipulation"
            :class="{ 'bg-gray-100': $page.component === 'Waiter/Dashboard' }"
            @click="sidebarOpen = false"
          >
            <TableProperties class="h-5 w-5 flex-shrink-0" />
            <span class="text-base">Tables</span>
          </Link>

          <Link
            :href="route('waiter.take-order')"
            class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors touch-manipulation"
            :class="{ 'bg-gray-100': $page.component === 'Waiter/TakeOrder' }"
            @click="sidebarOpen = false"
          >
            <Users class="h-5 w-5 flex-shrink-0" />
            <span class="text-base">Take Order</span>
          </Link>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 sm:p-6 border-t">
          <Button
            @click="logout"
            variant="ghost"
            class="w-full justify-start gap-3 px-4 py-3 text-red-600 hover:text-red-700 hover:bg-red-50 touch-manipulation"
            :disabled="logoutForm.processing"
          >
            <LogOut class="h-5 w-5 flex-shrink-0" />
            <span class="text-base">{{ logoutForm.processing ? 'Logging out...' : 'Logout' }}</span>
          </Button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <main
      class="transition-all duration-300"
      :class="[
        'lg:ml-0',
        sidebarOpen ? 'lg:ml-64' : 'lg:ml-0'
      ]"
      style="padding-top: 0 !important; margin-top: 0 !important;"
    >
      <div style="padding-top: 0 !important; margin-top: 0 !important;">
        <slot />
      </div>
    </main>
  </div>
</template>