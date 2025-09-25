<script setup lang="ts">
import { ref, computed } from 'vue';
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
import { Users, Clock, AlertTriangle, UtensilsCrossed, CheckCircle2, XCircle, Wrench, TrendingUp } from 'lucide-vue-next';

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

const props = defineProps<Props>();

const statusUpdateForm = useForm({
  status: ''
});

const selectedFilter = ref<string | null>(null);

const filteredTables = computed(() => {
  if (!selectedFilter.value) return props.tables;
  return props.tables.filter(table => table.status === selectedFilter.value);
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

    <div class="absolute inset-x-0 top-16 bottom-0 overflow-y-auto bg-gray-50/50">
      <div class="p-4 sm:p-6 lg:p-8 space-y-6">
        <!-- Page Header -->
        <div class="mb-8">
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Table Management</h2>
          <p class="text-gray-600 mt-1">Monitor and manage restaurant table statuses</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
          <Card class="overflow-hidden border-2 border-green-200 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-[1.02]">
            <div class="bg-white p-6">
              <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-100 rounded-xl">
                  <CheckCircle2 class="h-7 w-7 text-green-600" />
                </div>
                <TrendingUp class="h-5 w-5 text-green-500" />
              </div>
              <div>
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Available</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ tables.filter(t => t.status === 'available').length }}</p>
                <p class="text-sm text-gray-500 mt-1">Ready for guests</p>
              </div>
            </div>
          </Card>

          <Card class="overflow-hidden border-2 border-blue-200 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-[1.02]">
            <div class="bg-white p-6">
              <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-100 rounded-xl">
                  <UtensilsCrossed class="h-7 w-7 text-blue-600" />
                </div>
                <Users class="h-5 w-5 text-blue-500" />
              </div>
              <div>
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Occupied</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ tables.filter(t => t.status === 'occupied').length }}</p>
                <p class="text-sm text-gray-500 mt-1">Currently serving</p>
              </div>
            </div>
          </Card>

          <Card class="overflow-hidden border-2 border-amber-200 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-[1.02]">
            <div class="bg-white p-6">
              <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-amber-100 rounded-xl">
                  <Clock class="h-7 w-7 text-amber-600" />
                </div>
                <AlertTriangle class="h-5 w-5 text-amber-500" />
              </div>
              <div>
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Reserved</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ tables.filter(t => t.status === 'reserved').length }}</p>
                <p class="text-sm text-gray-500 mt-1">Upcoming bookings</p>
              </div>
            </div>
          </Card>

          <Card class="overflow-hidden border-2 border-purple-200 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-[1.02]">
            <div class="bg-white p-6">
              <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-100 rounded-xl">
                  <Users class="h-7 w-7 text-purple-600" />
                </div>
                <div class="flex gap-1">
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                  <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                </div>
              </div>
              <div>
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Tables</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ tables.length }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ tables.filter(t => t.status === 'maintenance').length }} in maintenance</p>
              </div>
            </div>
          </Card>
        </div>

        <!-- Filter Tabs -->
        <div class="flex flex-wrap gap-2">
            <Button
              @click="selectedFilter = null"
              :variant="selectedFilter === null ? 'default' : 'outline'"
              size="sm"
              class="min-w-fit shadow-none"
              :class="selectedFilter === null ? 'bg-gray-900 hover:bg-gray-800 text-white border-gray-900' : 'bg-white hover:bg-gray-50'"
            >
              All Tables
              <span class="ml-2 px-1.5 py-0.5 text-xs font-semibold rounded-md" 
                :class="selectedFilter === null ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700'">
                {{ tables.length }}
              </span>
            </Button>
            <Button
              @click="selectedFilter = 'available'"
              :variant="selectedFilter === 'available' ? 'default' : 'outline'"
              size="sm"
              class="min-w-fit shadow-none"
              :class="selectedFilter === 'available' ? 'bg-green-600 hover:bg-green-700 text-white border-green-600' : 'bg-white hover:bg-green-50 hover:text-green-700'"
            >
              <CheckCircle2 class="h-4 w-4 mr-1.5" />
              Available
              <span class="ml-2 px-1.5 py-0.5 text-xs font-semibold rounded-md" 
                :class="selectedFilter === 'available' ? 'bg-green-700 text-white' : 'bg-green-100 text-green-700'">
                {{ tables.filter(t => t.status === 'available').length }}
              </span>
            </Button>
            <Button
              @click="selectedFilter = 'occupied'"
              :variant="selectedFilter === 'occupied' ? 'default' : 'outline'"
              size="sm"
              class="min-w-fit shadow-none"
              :class="selectedFilter === 'occupied' ? 'bg-blue-600 hover:bg-blue-700 text-white border-blue-600' : 'bg-white hover:bg-blue-50 hover:text-blue-700'"
            >
              <UtensilsCrossed class="h-4 w-4 mr-1.5" />
              Occupied
              <span class="ml-2 px-1.5 py-0.5 text-xs font-semibold rounded-md" 
                :class="selectedFilter === 'occupied' ? 'bg-blue-700 text-white' : 'bg-blue-100 text-blue-700'">
                {{ tables.filter(t => t.status === 'occupied').length }}
              </span>
            </Button>
            <Button
              @click="selectedFilter = 'reserved'"
              :variant="selectedFilter === 'reserved' ? 'default' : 'outline'"
              size="sm"
              class="min-w-fit shadow-none"
              :class="selectedFilter === 'reserved' ? 'bg-amber-600 hover:bg-amber-700 text-white border-amber-600' : 'bg-white hover:bg-amber-50 hover:text-amber-700'"
            >
              <Clock class="h-4 w-4 mr-1.5" />
              Reserved
              <span class="ml-2 px-1.5 py-0.5 text-xs font-semibold rounded-md" 
                :class="selectedFilter === 'reserved' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-700'">
                {{ tables.filter(t => t.status === 'reserved').length }}
              </span>
            </Button>
            <Button
              @click="selectedFilter = 'maintenance'"
              :variant="selectedFilter === 'maintenance' ? 'default' : 'outline'"
              size="sm"
              class="min-w-fit shadow-none"
              :class="selectedFilter === 'maintenance' ? 'bg-gray-600 hover:bg-gray-700 text-white border-gray-600' : 'bg-white hover:bg-gray-50'"
            >
              <Wrench class="h-4 w-4 mr-1.5" />
              Maintenance
              <span class="ml-2 px-1.5 py-0.5 text-xs font-semibold rounded-md" 
                :class="selectedFilter === 'maintenance' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700'">
                {{ tables.filter(t => t.status === 'maintenance').length }}
              </span>
            </Button>
          </div>

        <!-- Tables Grid View -->
        <div class="mt-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <Card
              v-for="table in filteredTables"
              :key="table.id"
              class="group relative overflow-hidden transition-all duration-200 hover:shadow-lg border-2"
              :class="[
                table.status === 'available' ? 'border-green-300 hover:border-green-400' :
                table.status === 'occupied' ? 'border-blue-300 hover:border-blue-400' :
                table.status === 'reserved' ? 'border-amber-300 hover:border-amber-400' :
                'border-gray-300 hover:border-gray-400'
              ]"
            >
              <!-- Status Indicator Bar -->
              <div 
                class="absolute top-0 left-0 right-0 h-2"
                :class="[
                  table.status === 'available' ? 'bg-gradient-to-r from-green-400 to-green-500' :
                  table.status === 'occupied' ? 'bg-gradient-to-r from-blue-400 to-blue-500' :
                  table.status === 'reserved' ? 'bg-gradient-to-r from-amber-400 to-amber-500' :
                  'bg-gradient-to-r from-gray-400 to-gray-500'
                ]"
              ></div>

              <CardContent class="p-5">
                <!-- Table Header -->
                <div class="flex items-start justify-between mb-3">
                  <div>
                    <h3 class="text-lg font-bold text-gray-900">
                      {{ table.table_name }}
                    </h3>
                    <p class="text-sm text-gray-500 font-medium">Table #{{ table.table_number }}</p>
                  </div>
                  <div
                    class="p-2 rounded-xl"
                    :class="[
                      table.status === 'available' ? 'bg-green-100' :
                      table.status === 'occupied' ? 'bg-blue-100' :
                      table.status === 'reserved' ? 'bg-amber-100' :
                      'bg-gray-100'
                    ]"
                  >
                    <component 
                      :is="table.status === 'available' ? CheckCircle2 : 
                           table.status === 'occupied' ? UtensilsCrossed :
                           table.status === 'reserved' ? Clock : Wrench"
                      class="h-5 w-5"
                      :class="[
                        table.status === 'available' ? 'text-green-600' :
                        table.status === 'occupied' ? 'text-blue-600' :
                        table.status === 'reserved' ? 'text-amber-600' :
                        'text-gray-600'
                      ]"
                    />
                  </div>
                </div>

                <!-- Table Info -->
                <div class="space-y-3 mb-4">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <div class="p-1.5 bg-gray-100 rounded-lg">
                        <Users class="h-4 w-4 text-gray-600" />
                      </div>
                      <span class="text-sm font-medium text-gray-700">{{ table.seats }} seats</span>
                    </div>
                    <Badge 
                      class="px-3 py-1 font-semibold text-xs"
                      :class="[
                        table.status === 'available' ? 'bg-green-100 text-green-700 hover:bg-green-200' :
                        table.status === 'occupied' ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' :
                        table.status === 'reserved' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' :
                        'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      ]"
                    >
                      {{ table.status }}
                    </Badge>
                  </div>
                  
                  <div v-if="table.description" class="text-sm text-gray-600 leading-relaxed">
                    {{ table.description }}
                  </div>
                </div>

                <!-- Status Update -->
                <div class="pt-4 border-t border-gray-200">
                  <p class="text-xs font-medium text-gray-500 mb-2">Change Status</p>
                  <Select
                    :model-value="table.status"
                    @update:model-value="(value) => updateTableStatus(table.id, value)"
                  >
                    <SelectTrigger class="w-full h-10 font-medium bg-white border-2 hover:bg-gray-50 transition-colors">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="option in statusOptions"
                        :key="option.value"
                        :value="option.value"
                        class="font-medium"
                      >
                        <div class="flex items-center gap-2">
                          <div 
                            class="w-3 h-3 rounded-full border-2"
                            :class="[
                              option.value === 'available' ? 'bg-green-500 border-green-600' :
                              option.value === 'occupied' ? 'bg-blue-500 border-blue-600' :
                              option.value === 'reserved' ? 'bg-amber-500 border-amber-600' :
                              'bg-gray-500 border-gray-600'
                            ]"
                          ></div>
                          {{ option.label }}
                        </div>
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Empty State -->
          <div v-if="filteredTables.length === 0" class="bg-white rounded-xl p-12 text-center">
            <div class="max-w-sm mx-auto">
              <div class="p-4 bg-gray-100 rounded-full w-fit mx-auto mb-4">
                <AlertTriangle class="h-8 w-8 text-gray-400" />
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">
                {{ selectedFilter ? `No ${selectedFilter} tables` : 'No Tables Found' }}
              </h3>
              <p class="text-gray-600 mb-4">
                {{ selectedFilter ? `There are currently no tables with ${selectedFilter} status.` : 'There are no tables configured for this restaurant yet.' }}
              </p>
              <Button 
                v-if="selectedFilter"
                @click="selectedFilter = null"
                variant="outline"
              >
                View All Tables
              </Button>
            </div>
          </div>
        </div>

        <!-- Table List View - Hidden on Mobile, Shown on Larger Screens -->
        <Card class="hidden xl:block border-0 shadow-sm overflow-hidden">
          <CardHeader class="bg-gray-50 border-b">
            <div class="flex items-center justify-between">
              <div>
                <CardTitle class="text-xl">Detailed Table View</CardTitle>
                <CardDescription class="mt-1">
                  Complete overview of all restaurant tables
                </CardDescription>
              </div>
              <div class="flex items-center gap-1.5 text-gray-600 mr-30">
                <span class="text-lg font-semibold">{{ filteredTables.length }}</span>
                <span class="text-sm font-medium">{{ filteredTables.length === 1 ? 'table' : 'tables' }}</span>
              </div>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <div class="overflow-x-auto">
              <TableComponent>
                <TableHeader>
                  <TableRow class="bg-gray-50 border-b">
                    <TableHead class="font-semibold text-xs uppercase text-gray-700">Table</TableHead>
                    <TableHead class="font-semibold text-xs uppercase text-gray-700">Name</TableHead>
                    <TableHead class="font-semibold text-xs uppercase text-gray-700">Capacity</TableHead>
                    <TableHead class="font-semibold text-xs uppercase text-gray-700">Status</TableHead>
                    <TableHead class="font-semibold text-xs uppercase text-gray-700">Description</TableHead>
                    <TableHead class="font-semibold text-xs uppercase text-gray-700">Quick Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow 
                    v-for="table in filteredTables" 
                    :key="table.id"
                    class="hover:bg-gray-50 transition-colors border-b"
                  >
                    <TableCell class="font-medium">
                      <div 
                        class="w-10 h-10 rounded-lg flex items-center justify-center text-sm font-bold"
                        :class="[
                          table.status === 'available' ? 'bg-green-100 text-green-700' :
                          table.status === 'occupied' ? 'bg-blue-100 text-blue-700' :
                          table.status === 'reserved' ? 'bg-amber-100 text-amber-700' :
                          'bg-gray-100 text-gray-700'
                        ]"
                      >
                        {{ table.table_number }}
                      </div>
                    </TableCell>
                    <TableCell class="font-medium">{{ table.table_name }}</TableCell>
                    <TableCell>
                      <div class="flex items-center gap-1.5">
                        <Users class="h-4 w-4 text-gray-400" />
                        <span>{{ table.seats }} seats</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="flex items-center gap-2">
                        <component 
                          :is="table.status === 'available' ? CheckCircle2 : 
                               table.status === 'occupied' ? UtensilsCrossed :
                               table.status === 'reserved' ? Clock : Wrench"
                          class="h-4 w-4"
                          :class="[
                            table.status === 'available' ? 'text-green-600' :
                            table.status === 'occupied' ? 'text-blue-600' :
                            table.status === 'reserved' ? 'text-amber-600' :
                            'text-gray-600'
                          ]"
                        />
                        <span 
                          class="text-sm font-medium capitalize"
                          :class="[
                            table.status === 'available' ? 'text-green-700' :
                            table.status === 'occupied' ? 'text-blue-700' :
                            table.status === 'reserved' ? 'text-amber-700' :
                            'text-gray-700'
                          ]"
                        >
                          {{ table.status }}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <span class="text-sm text-gray-600">
                        {{ table.description || '—' }}
                      </span>
                    </TableCell>
                    <TableCell>
                      <Select
                        :model-value="table.status"
                        @update:model-value="(value) => updateTableStatus(table.id, value)"
                      >
                        <SelectTrigger class="w-32 h-8 text-sm">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                            class="text-sm"
                          >
                            <div class="flex items-center gap-2">
                              <div 
                                class="w-2 h-2 rounded-full"
                                :class="[
                                  option.value === 'available' ? 'bg-green-500' :
                                  option.value === 'occupied' ? 'bg-blue-500' :
                                  option.value === 'reserved' ? 'bg-amber-500' :
                                  'bg-gray-500'
                                ]"
                              ></div>
                              {{ option.label }}
                            </div>
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </TableComponent>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </WaiterLayout>
</template>