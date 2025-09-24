<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Select from '@/components/ui/select/Select.vue';
import SelectContent from '@/components/ui/select/SelectContent.vue';
import SelectItem from '@/components/ui/select/SelectItem.vue';
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/components/ui/select/SelectValue.vue';
import TableComponent from '@/components/ui/table/Table.vue';
import TableBody from '@/components/ui/table/TableBody.vue';
import TableCell from '@/components/ui/table/TableCell.vue';
import TableHead from '@/components/ui/table/TableHead.vue';
import TableHeader from '@/components/ui/table/TableHeader.vue';
import TableRow from '@/components/ui/table/TableRow.vue';
import { Users, Clock, AlertTriangle } from 'lucide-vue-next';

interface Table {
  id: number;
  table_number: string;
  table_name: string;
  seats: number;
  status: 'available' | 'occupied' | 'reserved' | 'maintenance';
  description?: string;
  x_position?: number;
  y_position?: number;
}

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
  tables: Table[];
  employee: Employee;
}

defineProps<Props>();

const statusUpdateForm = useForm({
  status: ''
});

const updateTableStatus = (tableId: number, newStatus: string) => {
  statusUpdateForm.status = newStatus;
  statusUpdateForm.patch(route('waiter.tables.update-status', tableId), {
    preserveScroll: true,
    onSuccess: () => {
      statusUpdateForm.reset();
    }
  });
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-green-100 text-green-800 border-green-200';
    case 'occupied':
      return 'bg-red-100 text-red-800 border-red-200';
    case 'reserved':
      return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    case 'maintenance':
      return 'bg-gray-100 text-gray-800 border-gray-200';
    default:
      return 'bg-gray-100 text-gray-800 border-gray-200';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'available':
      return 'text-green-600';
    case 'occupied':
      return 'text-red-600';
    case 'reserved':
      return 'text-yellow-600';
    case 'maintenance':
      return 'text-gray-600';
    default:
      return 'text-gray-600';
  }
};

const statusOptions = [
  { value: 'available', label: 'Available' },
  { value: 'occupied', label: 'Occupied' },
  { value: 'reserved', label: 'Reserved' },
  { value: 'maintenance', label: 'Maintenance' },
];
</script>

<template>
  <Head title="Waiter Dashboard" />

  <WaiterLayout :employee="employee">
    <template #title>Tables Overview</template>

    <div style="position: absolute; top: 60px; left: 0; right: 0; padding: 0 24px;">
      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1">
            <CardTitle class="text-sm font-medium">Available Tables</CardTitle>
            <div class="h-3 w-3 bg-green-500 rounded-full"></div>
          </CardHeader>
          <CardContent class="pt-1">
            <div class="text-2xl font-bold">
              {{ tables.filter(t => t.status === 'available').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1">
            <CardTitle class="text-sm font-medium">Occupied Tables</CardTitle>
            <div class="h-3 w-3 bg-red-500 rounded-full"></div>
          </CardHeader>
          <CardContent class="pt-1">
            <div class="text-2xl font-bold">
              {{ tables.filter(t => t.status === 'occupied').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1">
            <CardTitle class="text-sm font-medium">Reserved Tables</CardTitle>
            <div class="h-3 w-3 bg-yellow-500 rounded-full"></div>
          </CardHeader>
          <CardContent class="pt-1">
            <div class="text-2xl font-bold">
              {{ tables.filter(t => t.status === 'reserved').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1">
            <CardTitle class="text-sm font-medium">Total Tables</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent class="pt-1">
            <div class="text-2xl font-bold">{{ tables.length }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Tables Grid View -->
      <Card>
        <CardHeader>
          <CardTitle>Restaurant Tables</CardTitle>
          <CardDescription>
            Manage table statuses and track occupancy
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div
              v-for="table in tables"
              :key="table.id"
              class="border rounded-lg p-3 sm:p-4 space-y-3"
              :class="[
                table.status === 'available' ? 'border-green-200 bg-green-50' :
                table.status === 'occupied' ? 'border-red-200 bg-red-50' :
                table.status === 'reserved' ? 'border-yellow-200 bg-yellow-50' :
                'border-gray-200 bg-gray-50'
              ]"
            >
              <!-- Table Header -->
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-base sm:text-lg truncate">{{ table.table_name }}</h3>
                  <p class="text-xs sm:text-sm text-muted-foreground">Table #{{ table.table_number }}</p>
                </div>
                <Badge :class="getStatusColor(table.status)" class="capitalize text-xs ml-2">
                  {{ table.status }}
                </Badge>
              </div>

              <!-- Table Info -->
              <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm">
                  <Users :class="getStatusIcon(table.status)" class="h-4 w-4 flex-shrink-0" />
                  <span>{{ table.seats }} seats</span>
                </div>
                <div v-if="table.description" class="text-xs sm:text-sm text-muted-foreground">
                  {{ table.description }}
                </div>
              </div>

              <!-- Status Update -->
              <div class="pt-2 border-t">
                <Select
                  :model-value="table.status"
                  @update:model-value="(value) => updateTableStatus(table.id, value)"
                >
                  <SelectTrigger class="w-full h-9 text-sm">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in statusOptions"
                      :key="option.value"
                      :value="option.value"
                      class="text-sm"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="tables.length === 0" class="text-center py-12">
            <AlertTriangle class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-muted-foreground mb-2">No Tables Found</h3>
            <p class="text-muted-foreground">
              There are no tables configured for this restaurant yet.
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Table List View - Hidden on Mobile, Shown on Larger Screens -->
      <Card class="hidden lg:block">
        <CardHeader>
          <CardTitle>Table Details</CardTitle>
          <CardDescription>
            Complete table information and quick actions
          </CardDescription>
        </CardHeader>
        <CardContent>
          <TableComponent>
            <TableHeader>
              <TableRow>
                <TableHead>Table</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Seats</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="table in tables" :key="table.id">
                <TableCell class="font-medium">#{{ table.table_number }}</TableCell>
                <TableCell>{{ table.table_name }}</TableCell>
                <TableCell>
                  <div class="flex items-center gap-1">
                    <Users class="h-4 w-4" />
                    {{ table.seats }}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :class="getStatusColor(table.status)" class="capitalize">
                    {{ table.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <span class="text-sm text-muted-foreground">
                    {{ table.description || 'No description' }}
                  </span>
                </TableCell>
                <TableCell>
                  <Select
                    :model-value="table.status"
                    @update:model-value="(value) => updateTableStatus(table.id, value)"
                  >
                    <SelectTrigger class="w-32">
                      <SelectValue />
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
                </TableCell>
              </TableRow>
            </TableBody>
          </TableComponent>
        </CardContent>
      </Card>
    </div>
  </WaiterLayout>
</template>