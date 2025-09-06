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
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { CreditCard, Users, Clock, Award, Calendar, Settings, Eye } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Subscription Management',
    href: '/admin/subscriptions',
  },
];

interface Subscription {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  start_date: string;
  end_date: string;
  remaining_days: number;
  status: 'active' | 'archive';
  is_trial: boolean;
  plan_id: number;
}

interface Stats {
  total: number;
  active: number;
  expired: number;
  trial: number;
}

const props = defineProps<{
  subscriptions: Subscription[];
  stats: Stats;
}>();

const filterStatus = ref<string>('all');
const searchQuery = ref<string>('');
const extendDays = ref<number>(30);
const selectedSubscription = ref<Subscription | null>(null);

const filteredSubscriptions = computed(() => {
  let filtered = props.subscriptions;

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(sub => sub.status === filterStatus.value);
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(sub => 
      sub.user_name.toLowerCase().includes(query) ||
      sub.user_email.toLowerCase().includes(query)
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

const getStatusBadge = (status: string, isExpired: boolean = false) => {
  if (isExpired || status === 'archive') {
    return 'destructive';
  }
  return status === 'active' ? 'default' : 'secondary';
};

const isExpired = (endDate: string) => {
  return new Date(endDate) < new Date();
};

const extendSubscription = (subscription: Subscription) => {
  router.post(`/admin/subscriptions/${subscription.id}/extend`, {
    days: extendDays.value
  });
};

const toggleSubscription = (subscription: Subscription) => {
  const action = subscription.status === 'active' ? 'suspend' : 'activate';
  router.post(`/admin/subscriptions/${subscription.id}/${action}`);
};
</script>

<template>
  <Head title="Subscription Management" />

  <AppLayoutAdministrator :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Subscriptions</CardTitle>
            <CreditCard class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active</CardTitle>
            <Users class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Expired</CardTitle>
            <Clock class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ stats.expired }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Trial Users</CardTitle>
            <Award class="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">{{ stats.trial }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters and Search -->
      <Card>
        <CardHeader>
          <CardTitle>Subscription Management</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <Label for="search">Search Users</Label>
              <Input 
                id="search"
                v-model="searchQuery" 
                placeholder="Search by name or email..." 
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
                <option value="all">All Subscriptions</option>
                <option value="active">Active</option>
                <option value="archive">Expired/Archived</option>
              </select>
            </div>
          </div>

          <!-- Subscriptions Table -->
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Start Date</TableHead>
                  <TableHead>End Date</TableHead>
                  <TableHead>Days Left</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead class="text-center">Actions</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow
                  v-for="subscription in filteredSubscriptions"
                  :key="subscription.id"
                  class="hover:bg-muted/50"
                >
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ subscription.user_name }}</div>
                      <div class="text-sm text-muted-foreground">{{ subscription.user_email }}</div>
                    </div>
                  </TableCell>
                  <TableCell>{{ formatDate(subscription.start_date) }}</TableCell>
                  <TableCell>{{ formatDate(subscription.end_date) }}</TableCell>
                  <TableCell>
                    <span :class="subscription.remaining_days <= 7 ? 'text-red-600 font-medium' : ''">
                      {{ subscription.remaining_days }} days
                    </span>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getStatusBadge(subscription.status, isExpired(subscription.end_date))">
                      {{ isExpired(subscription.end_date) ? 'Expired' : subscription.status }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ subscription.is_trial ? 'Trial' : 'Paid' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <div class="flex justify-center gap-2">
                      <!-- View Details -->
                      <Link
                        :href="`/admin/subscriptions/${subscription.id}`"
                        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-3"
                      >
                        <Eye class="h-4 w-4" />
                      </Link>

                      <!-- Extend Subscription -->
                      <Dialog>
                        <DialogTrigger as-child>
                          <Button 
                            size="sm" 
                            variant="outline"
                            @click="selectedSubscription = subscription"
                          >
                            <Calendar class="h-4 w-4" />
                          </Button>
                        </DialogTrigger>
                        <DialogContent>
                          <DialogHeader>
                            <DialogTitle>Extend Subscription</DialogTitle>
                          </DialogHeader>
                          <div class="space-y-4">
                            <div>
                              <Label for="days">Extend by (days)</Label>
                              <Input
                                id="days"
                                v-model="extendDays"
                                type="number"
                                min="1"
                                max="365"
                                placeholder="30"
                              />
                            </div>
                            <Button 
                              @click="extendSubscription(subscription)"
                              class="w-full"
                            >
                              Extend Subscription
                            </Button>
                          </div>
                        </DialogContent>
                      </Dialog>

                      <!-- Toggle Status -->
                      <Button 
                        size="sm" 
                        :variant="subscription.status === 'active' ? 'destructive' : 'default'"
                        @click="toggleSubscription(subscription)"
                      >
                        <Settings class="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
                <TableRow v-if="filteredSubscriptions.length === 0">
                  <TableCell colspan="7" class="text-center py-4 text-muted-foreground">
                    No subscriptions found.
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