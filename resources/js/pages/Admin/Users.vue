<script setup lang="ts">
import AppLayoutAdministrator from '@/layouts/AppLayoutAdministrator.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Users, CheckCircle, Clock, XCircle, MoreVertical, Eye, Settings, Trash2, RefreshCw, Mail } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'User Management',
    href: '/admin/users',
  },
];

interface User {
  id: number;
  name: string;
  email: string;
  phone: string;
  status: 'Pending' | 'Approved' | 'Rejected';
  email_verified: boolean;
  restaurant_name: string;
  subscription_status: string;
  created_at: string;
  last_login: string;
}

interface Stats {
  total: number;
  approved: number;
  pending: number;
  rejected: number;
}

const props = defineProps<{
  users: User[];
  stats: Stats;
}>();

const filterStatus = ref<string>('all');
const searchQuery = ref<string>('');

const filteredUsers = computed(() => {
  let filtered = props.users;

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(user => user.status === filterStatus.value);
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(user => 
      user.name.toLowerCase().includes(query) ||
      user.email.toLowerCase().includes(query) ||
      user.restaurant_name.toLowerCase().includes(query)
    );
  }

  return filtered;
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'Approved':
      return 'default';
    case 'Pending':
      return 'secondary';
    case 'Rejected':
      return 'destructive';
    default:
      return 'outline';
  }
};

const getSubscriptionBadge = (status: string) => {
  switch (status) {
    case 'active':
      return 'default';
    case 'archive':
      return 'destructive';
    case 'none':
      return 'outline';
    default:
      return 'secondary';
  }
};

const updateUserStatus = (userId: number, status: string) => {
  router.post(`/admin/users/${userId}/status`, { status });
};

const toggleEmailVerification = (userId: number) => {
  router.post(`/admin/users/${userId}/toggle-email`);
};

const resetPassword = (userId: number) => {
  router.post(`/admin/users/${userId}/reset-password`);
};

const deleteUser = (userId: number) => {
  if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
    router.delete(`/admin/users/${userId}`);
  }
};
</script>

<template>
  <Head title="User Management" />

  <AppLayoutAdministrator :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Users</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Approved</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ stats.approved }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
            <Clock class="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Rejected</CardTitle>
            <XCircle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ stats.rejected }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Users Table -->
      <Card>
        <CardHeader>
          <CardTitle>User Management</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <Label for="search">Search Users</Label>
              <Input 
                id="search"
                v-model="searchQuery" 
                placeholder="Search by name, email, or restaurant..." 
                class="max-w-sm"
              />
            </div>
            <div>
              <Label for="status">Filter by Status</Label>
              <select 
                id="status"
                v-model="filterStatus" 
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
              >
                <option value="all">All Users</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
                <option value="Rejected">Rejected</option>
              </select>
            </div>
          </div>

          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Restaurant</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Email Status</TableHead>
                  <TableHead>Subscription</TableHead>
                  <TableHead>Joined</TableHead>
                  <TableHead class="text-center">Actions</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow
                  v-for="user in filteredUsers"
                  :key="user.id"
                  class="hover:bg-muted/50"
                >
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ user.name }}</div>
                      <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                      <div class="text-sm text-muted-foreground">{{ user.phone }}</div>
                    </div>
                  </TableCell>
                  <TableCell>{{ user.restaurant_name }}</TableCell>
                  <TableCell>
                    <Badge :variant="getStatusBadge(user.status)">
                      {{ user.status }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <Badge :variant="user.email_verified ? 'default' : 'secondary'">
                        {{ user.email_verified ? 'Verified' : 'Unverified' }}
                      </Badge>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getSubscriptionBadge(user.subscription_status)">
                      {{ user.subscription_status || 'None' }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                  <TableCell class="text-center">
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                          <MoreVertical class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem as-child>
                          <Link :href="`/admin/users/${user.id}`" class="flex items-center gap-2">
                            <Eye class="h-4 w-4" />
                            View Details
                          </Link>
                        </DropdownMenuItem>
                        
                        <DropdownMenuItem 
                          v-if="user.status === 'Pending'"
                          @click="updateUserStatus(user.id, 'Approved')"
                          class="flex items-center gap-2"
                        >
                          <CheckCircle class="h-4 w-4" />
                          Approve
                        </DropdownMenuItem>
                        
                        <DropdownMenuItem 
                          v-if="user.status === 'Pending'"
                          @click="updateUserStatus(user.id, 'Rejected')"
                          class="flex items-center gap-2"
                        >
                          <XCircle class="h-4 w-4" />
                          Reject
                        </DropdownMenuItem>
                        
                        <DropdownMenuItem 
                          @click="toggleEmailVerification(user.id)"
                          class="flex items-center gap-2"
                        >
                          <Mail class="h-4 w-4" />
                          {{ user.email_verified ? 'Unverify Email' : 'Verify Email' }}
                        </DropdownMenuItem>
                        
                        <DropdownMenuItem 
                          @click="resetPassword(user.id)"
                          class="flex items-center gap-2"
                        >
                          <RefreshCw class="h-4 w-4" />
                          Reset Password
                        </DropdownMenuItem>
                        
                        <DropdownMenuItem 
                          @click="deleteUser(user.id)"
                          class="flex items-center gap-2 text-destructive"
                        >
                          <Trash2 class="h-4 w-4" />
                          Delete User
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
                <TableRow v-if="filteredUsers.length === 0">
                  <TableCell colspan="7" class="text-center py-4 text-muted-foreground">
                    No users found.
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayoutAdministrator>
</template>