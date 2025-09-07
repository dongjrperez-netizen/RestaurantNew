<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  contact_number: string;
  email: string;
  address: string;
  payment_terms: string;
  credit_limit: number;
  is_active: boolean;
  ingredients?: any[];
  purchase_orders?: any[];
}

interface Props {
  suppliers: Supplier[];
  restaurant_id: number;
}

const props = defineProps<Props>();
const processing = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Suppliers', href: '/suppliers' },
];

const getPaymentTermsLabel = (terms: string) => {
  const labels: Record<string, string> = {
    'COD': 'Cash on Delivery',
    'NET_7': 'Net 7 days',
    'NET_15': 'Net 15 days',
    'NET_30': 'Net 30 days',
    'NET_60': 'Net 60 days',
    'NET_90': 'Net 90 days',
  };
  return labels[terms] || terms;
};

const getStatusBadgeVariant = (isActive: boolean) => {
  return isActive ? 'default' : 'secondary';
};

const toggleSupplierStatus = (supplier: Supplier) => {
  if (processing.value) return;
  
  processing.value = true;
  router.post(`/suppliers/${supplier.supplier_id}/toggle-status`, {}, {
    onSuccess: () => {
      supplier.is_active = !supplier.is_active;
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

const sendSupplierInvitation = (supplier: Supplier) => {
  if (!supplier.email) {
    alert('Supplier must have an email to send invitation');
    return;
  }
  
  if (processing.value) return;
  
  processing.value = true;
  router.post(`/suppliers/${supplier.supplier_id}/send-invitation`, {}, {
    onSuccess: () => {
      alert('Invitation sent successfully!');
    },
    onError: () => {
      alert('Failed to send invitation');
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

const getInvitationLink = (supplier: Supplier) => {
  const baseUrl = window.location.origin;
  return `${baseUrl}/supplier/register?restaurant_id=${props.restaurant_id}&supplier_id=${supplier.supplier_id}`;
};

const copyInvitationLink = async (supplier: Supplier) => {
  const link = getInvitationLink(supplier);
  try {
    await navigator.clipboard.writeText(link);
    alert('Invitation link copied to clipboard!');
  } catch (err) {
    alert('Failed to copy link');
  }
};
</script>

<template>
  <Head title="Suppliers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Suppliers</h1>
          <p class="text-muted-foreground">Manage your suppliers and their contact information</p>
        </div>
        <Link href="/suppliers/create">
          <Button>Add Supplier</Button>
        </Link>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Suppliers</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ suppliers.length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Suppliers</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ suppliers.filter(s => s.is_active).length }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">With Email</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ suppliers.filter(s => s.email).length }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Suppliers Table -->
      <Card>
        <CardHeader>
          <CardTitle>Suppliers List</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Contact</TableHead>
                <TableHead>Payment Terms</TableHead>
                <TableHead>Credit Limit</TableHead>
                <TableHead>Ingredients</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="supplier in suppliers" :key="supplier.supplier_id">
                <TableCell class="font-medium">
                  <div>
                    <div class="font-semibold">{{ supplier.supplier_name }}</div>
                    <div v-if="supplier.address" class="text-sm text-muted-foreground">
                      {{ supplier.address }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="space-y-1">
                    <div v-if="supplier.contact_number" class="text-sm">
                      📞 {{ supplier.contact_number }}
                    </div>
                    <div v-if="supplier.email" class="text-sm">
                      📧 {{ supplier.email }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  {{ getPaymentTermsLabel(supplier.payment_terms) }}
                </TableCell>
                <TableCell>
                  <span v-if="supplier.credit_limit">
                    ₱{{ Number(supplier.credit_limit).toLocaleString() }}
                  </span>
                  <span v-else class="text-muted-foreground">-</span>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">
                    {{ supplier.ingredients?.length || 0 }} items
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(supplier.is_active)">
                    {{ supplier.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end space-x-2 flex-wrap">
                    <Link :href="`/suppliers/${supplier.supplier_id}`">
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                    <Link :href="`/suppliers/${supplier.supplier_id}/edit`">
                      <Button variant="outline" size="sm">Edit</Button>
                    </Link>
                    <Button 
                      v-if="supplier.email"
                      variant="outline" 
                      size="sm"
                      @click="sendSupplierInvitation(supplier)"
                      :disabled="processing"
                    >
                      Send Invite
                    </Button>
                    <Button 
                      variant="outline" 
                      size="sm"
                      @click="copyInvitationLink(supplier)"
                      title="Copy invitation link"
                    >
                      📋
                    </Button>
                    <Button 
                      :variant="supplier.is_active ? 'destructive' : 'default'" 
                      size="sm"
                      @click="toggleSupplierStatus(supplier)"
                      :disabled="processing"
                    >
                      {{ supplier.is_active ? 'Deactivate' : 'Activate' }}
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="suppliers.length === 0">
                <TableCell colspan="7" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No suppliers found</div>
                    <div class="text-sm">Get started by adding your first supplier.</div>
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