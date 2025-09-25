<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Input from '@/components/ui/input/Input.vue';
import { Users, Clock, AlertTriangle, ShoppingCart, Search, CheckCircle2, UtensilsCrossed, Wrench, Filter, TrendingUp } from 'lucide-vue-next';

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

// Debug logs
console.log('TakeOrder mounted');
console.log('Props:', props);
console.log('Tables:', props.tables);
console.log('Employee:', props.employee);

const searchQuery = ref('');
const selectedStatus = ref<string | null>(null);

const filteredTables = computed(() => {
  if (!props.tables || !Array.isArray(props.tables)) {
    console.error('Tables prop is not an array:', props.tables);
    return [];
  }
  return props.tables.filter(table => {
    const matchesSearch = table.table_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         table.table_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         (table.description && table.description.toLowerCase().includes(searchQuery.value.toLowerCase()));

    const matchesStatus = selectedStatus.value === null || table.status === selectedStatus.value;

    return matchesSearch && matchesStatus;
  });
});

const availableTables = computed(() => filteredTables.value.filter(t => t.status === 'available'));
const occupiedTables = computed(() => filteredTables.value.filter(t => t.status === 'occupied'));
const reservedTables = computed(() => filteredTables.value.filter(t => t.status === 'reserved'));

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

const canTakeOrder = (table: Table) => {
  return table.status === 'available' || table.status === 'occupied' || table.status === 'reserved';
};

const statusFilters = [
  { value: null, label: 'All Tables', count: computed(() => filteredTables.value.length) },
  { value: 'available', label: 'Available', count: computed(() => availableTables.value.length) },
  { value: 'occupied', label: 'Occupied', count: computed(() => occupiedTables.value.length) },
  { value: 'reserved', label: 'Reserved', count: computed(() => reservedTables.value.length) },
];
</script>

<template>
  <Head title="Take Order - Select Table" />

  <WaiterLayout :employee="employee">
    <template #title>Take Order</template>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Header -->
        <div class="mb-8">
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Select a Table to Take Order</h2>
          <p class="text-gray-600 mt-1">Choose an available table to start taking a customer's order</p>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <Card class="overflow-hidden border-2 border-green-200 shadow-sm hover:shadow-md transition-all duration-200">
            <div class="bg-white p-4">
              <div class="flex items-center justify-between mb-2">
                <div class="p-2 bg-green-100 rounded-lg">
                  <CheckCircle2 class="h-5 w-5 text-green-600" />
                </div>
                <TrendingUp class="h-4 w-4 text-green-500" />
              </div>
              <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Available</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.tables?.filter(t => t.status === 'available')?.length || 0 }}</p>
              <p class="text-xs text-gray-500 mt-0.5">Ready for orders</p>
            </div>
          </Card>

          <Card class="overflow-hidden border-2 border-blue-200 shadow-sm hover:shadow-md transition-all duration-200">
            <div class="bg-white p-4">
              <div class="flex items-center justify-between mb-2">
                <div class="p-2 bg-blue-100 rounded-lg">
                  <UtensilsCrossed class="h-5 w-5 text-blue-600" />
                </div>
                <ShoppingCart class="h-4 w-4 text-blue-500" />
              </div>
              <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Occupied</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.tables?.filter(t => t.status === 'occupied')?.length || 0 }}</p>
              <p class="text-xs text-gray-500 mt-0.5">Can add more orders</p>
            </div>
          </Card>

          <Card class="overflow-hidden border-2 border-amber-200 shadow-sm hover:shadow-md transition-all duration-200">
            <div class="bg-white p-4">
              <div class="flex items-center justify-between mb-2">
                <div class="p-2 bg-amber-100 rounded-lg">
                  <Clock class="h-5 w-5 text-amber-600" />
                </div>
                <Users class="h-4 w-4 text-amber-500" />
              </div>
              <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Reserved</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.tables?.filter(t => t.status === 'reserved')?.length || 0 }}</p>
              <p class="text-xs text-gray-500 mt-0.5">Awaiting customers</p>
            </div>
          </Card>

          <Card class="overflow-hidden border-2 border-gray-200 shadow-sm hover:shadow-md transition-all duration-200">
            <div class="bg-white p-4">
              <div class="flex items-center justify-between mb-2">
                <div class="p-2 bg-gray-100 rounded-lg">
                  <Wrench class="h-5 w-5 text-gray-600" />
                </div>
                <AlertTriangle class="h-4 w-4 text-gray-500" />
              </div>
              <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Maintenance</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.tables?.filter(t => t.status === 'maintenance')?.length || 0 }}</p>
              <p class="text-xs text-gray-500 mt-0.5">Not available</p>
            </div>
          </Card>
        </div>

        <!-- Search and Filter Section -->
        <Card class="border-0 shadow-sm">
          <CardContent class="p-4">
            <div class="space-y-4">
              <!-- Search Bar -->
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search tables by name, number, or description..."
                  class="pl-10 h-10 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                />
              </div>

              <!-- Status Filters -->
              <div class="flex flex-wrap gap-2">
                <Button
                  @click="selectedStatus = null"
                  :variant="selectedStatus === null ? 'default' : 'outline'"
                  size="sm"
                  class="text-xs">
                  All Tables ({{ props.tables?.length || 0 }})
                </Button>
                <Button
                  @click="selectedStatus = 'available'"
                  :variant="selectedStatus === 'available' ? 'default' : 'outline'"
                  size="sm"
                  class="text-xs">
                  Available ({{ props.tables?.filter(t => t.status === 'available')?.length || 0 }})
                </Button>
                <Button
                  @click="selectedStatus = 'occupied'"
                  :variant="selectedStatus === 'occupied' ? 'default' : 'outline'"
                  size="sm"
                  class="text-xs">
                  Occupied ({{ props.tables?.filter(t => t.status === 'occupied')?.length || 0 }})
                </Button>
                <Button
                  @click="selectedStatus = 'reserved'"
                  :variant="selectedStatus === 'reserved' ? 'default' : 'outline'"
                  size="sm"
                  class="text-xs">
                  Reserved ({{ props.tables?.filter(t => t.status === 'reserved')?.length || 0 }})
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Tables Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 mt-6">
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

              <!-- Action Section -->
              <div class="pt-4 border-t border-gray-200">
                <Link
                  v-if="canTakeOrder(table)"
                  :href="route('waiter.orders.create', { tableId: table.id })"
                  class="block"
                >
                  <Button 
                    class="w-full font-medium transition-all"
                    :class="[
                      table.status === 'available' ? 'bg-gray-900 hover:bg-gray-800 text-white' :
                      table.status === 'occupied' ? 'bg-blue-600 hover:bg-blue-700 text-white' :
                      table.status === 'reserved' ? 'bg-amber-600 hover:bg-amber-700 text-white' :
                      ''
                    ]"
                  >
                    <ShoppingCart class="h-4 w-4 mr-2" />
                    Take Order
                  </Button>
                </Link>

                <Button
                  v-else
                  disabled
                  class="w-full opacity-50 cursor-not-allowed"
                  variant="outline"
                >
                  <AlertTriangle class="h-4 w-4 mr-2" />
                  Not Available
                </Button>

                <!-- Status Message -->
                <p class="text-xs text-center mt-2"
                  :class="[
                    table.status === 'available' ? 'text-green-600' :
                    table.status === 'occupied' ? 'text-blue-600' :
                    table.status === 'reserved' ? 'text-amber-600' :
                    'text-gray-600'
                  ]"
                >
                  <span v-if="table.status === 'available'">
                    Ready for new customers
                  </span>
                  <span v-else-if="table.status === 'occupied'">
                    Currently occupied - can add more orders
                  </span>
                  <span v-else-if="table.status === 'reserved'">
                    Reserved - can take orders
                  </span>
                  <span v-else>
                    Under maintenance
                  </span>
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Empty State -->
        <div v-if="filteredTables.length === 0" class="bg-white rounded-xl shadow-sm p-12 text-center">
          <div class="max-w-sm mx-auto">
            <div class="p-4 bg-gray-100 rounded-full w-fit mx-auto mb-4">
              <AlertTriangle class="h-8 w-8 text-gray-400" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              {{ searchQuery || selectedStatus ? 'No tables match your criteria' : 'No Tables Found' }}
            </h3>
            <p class="text-gray-600 mb-4">
              {{ searchQuery || selectedStatus ? 'Try adjusting your search or filter settings' : 'There are no tables configured for this restaurant yet.' }}
            </p>
            <Button
              v-if="searchQuery || selectedStatus"
              @click="() => { searchQuery = ''; selectedStatus = null; }"
              variant="outline"
            >
              Clear Filters
            </Button>
          </div>
        </div>
    </div>
  </WaiterLayout>
</template>
