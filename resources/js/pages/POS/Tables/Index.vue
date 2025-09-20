<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Plus, Search, MoreVertical, Users, Edit, Trash2, Eye } from 'lucide-vue-next';

interface Table {
  id: number;
  table_number: string;
  table_name: string;
  seats: number;
  status: 'available' | 'occupied' | 'reserved' | 'maintenance';
  description?: string;
  x_position?: number;
  y_position?: number;
  created_at: string;
  updated_at: string;
}

interface Props {
  tables: Table[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'POS Management', href: '#' },
  { title: 'Tables', href: '/pos/tables' },
];

const searchQuery = ref('');

const filteredTables = computed(() => {
  if (!searchQuery.value) return props.tables;

  const query = searchQuery.value.toLowerCase();
  return props.tables.filter(table =>
    table.table_number.toLowerCase().includes(query) ||
    table.table_name.toLowerCase().includes(query)
  );
});

const getStatusColor = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-green-500';
    case 'occupied':
      return 'bg-red-500';
    case 'reserved':
      return 'bg-yellow-500';
    case 'maintenance':
      return 'bg-gray-500';
    default:
      return 'bg-gray-400';
  }
};

const getStatusText = (status: string) => {
  return status.charAt(0).toUpperCase() + status.slice(1);
};

const tableStats = computed(() => {
  const stats = {
    total: props.tables.length,
    available: 0,
    occupied: 0,
    reserved: 0,
    maintenance: 0,
  };

  props.tables.forEach(table => {
    stats[table.status as keyof typeof stats]++;
  });

  return stats;
});

const deleteTable = (table: Table) => {
  if (confirm(`Are you sure you want to delete table ${table.table_number}? This action cannot be undone.`)) {
    router.delete(`/pos/tables/${table.id}`, {
      onSuccess: () => {
        // Table deleted successfully
      },
    });
  }
};
</script>

<template>
  <Head title="Tables" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Tables</h1>
          <p class="text-muted-foreground">Manage your restaurant tables and seating arrangements</p>
        </div>
        <Link href="/pos/tables/create">
          <Button>
            <Plus class="w-4 h-4 mr-2" />
            Add Table
          </Button>
        </Link>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <Users class="w-5 h-5 text-muted-foreground" />
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Tables</p>
                <p class="text-2xl font-bold">{{ tableStats.total }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Available</p>
                <p class="text-2xl font-bold">{{ tableStats.available }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Occupied</p>
                <p class="text-2xl font-bold">{{ tableStats.occupied }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Reserved</p>
                <p class="text-2xl font-bold">{{ tableStats.reserved }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-gray-500"></div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Maintenance</p>
                <p class="text-2xl font-bold">{{ tableStats.maintenance }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Search and Filters -->
      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-4">
            <div class="relative flex-1 max-w-sm">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
              <Input
                v-model="searchQuery"
                placeholder="Search tables..."
                class="pl-10"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Tables List -->
      <Card>
        <CardHeader>
          <CardTitle>All Tables ({{ filteredTables.length }})</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Table #</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Seats</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Description</TableHead>
                <TableHead class="w-20">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="table in filteredTables" :key="table.id">
                <TableCell class="font-medium">{{ table.table_number }}</TableCell>
                <TableCell>{{ table.table_name }}</TableCell>
                <TableCell>
                  <div class="flex items-center space-x-1">
                    <Users class="w-4 h-4 text-muted-foreground" />
                    <span>{{ table.seats }}</span>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :class="getStatusColor(table.status)" class="text-white">
                    {{ getStatusText(table.status) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <span class="text-sm text-muted-foreground">
                    {{ table.description || 'No description' }}
                  </span>
                </TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="sm">
                        <MoreVertical class="w-4 h-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem asChild>
                        <Link :href="`/pos/tables/${table.id}`">
                          <Eye class="w-4 h-4 mr-2" />
                          View
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link :href="`/pos/tables/${table.id}/edit`">
                          <Edit class="w-4 h-4 mr-2" />
                          Edit
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        class="text-destructive"
                        @click="deleteTable(table)"
                      >
                        <Trash2 class="w-4 h-4 mr-2" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="filteredTables.length === 0">
                <TableCell colspan="6" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <Users class="w-12 h-12 mx-auto mb-4 text-muted-foreground" />
                    <div class="text-lg mb-2">No tables found</div>
                    <div class="text-sm">
                      {{ searchQuery ? 'Try adjusting your search terms.' : 'Get started by adding your first table.' }}
                    </div>
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