<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';


interface Payment {
  payment_id: number;
  payment_reference: string;
  payment_amount: number;
  payment_method: string;
  payment_date: string;
  transaction_reference?: string;
  notes?: string;
  status: string;
  created_by?: {
    first_name: string;
    last_name: string;
  };
}

interface PurchaseOrderItem {
  purchase_order_item_id: number;
  ingredient: {
    ingredient_name: string;
  };
  ordered_quantity: number;
  received_quantity: number;
  unit_price: number;
  total_price: number;
  unit_of_measure: string;
}

interface Bill {
  bill_id: number;
  bill_number: string;
  supplier_invoice_number?: string;
  status: string;
  bill_date: string;
  due_date: string;
  subtotal: number;
  tax_amount: number;
  discount_amount: number;
  total_amount: number;
  paid_amount: number;
  outstanding_amount: number;
  notes?: string;
  is_overdue: boolean;
  days_overdue: number;
  supplier: {
    supplier_id: number;
    supplier_name: string;
    contact_number?: string;
    email?: string;
    payment_terms?: string;
  };
  purchase_order?: {
    po_number: string;
    order_date: string;
    items: PurchaseOrderItem[];
  };
  payments: Payment[];
}

interface Props {
  bill: Bill;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Bills', href: '/bills' },
  { title: props.bill.bill_number, href: `/bills/${props.bill.bill_id}` },
];

// Status color mapping
const getStatusVariant = (status: string) => {
  switch (status.toLowerCase()) {
    case 'paid':
      return 'default'; // Green
    case 'pending':
      return 'secondary'; // Gray
    case 'partially_paid':
      return 'outline'; // Blue outline
    case 'overdue':
      return 'destructive'; // Red
    case 'cancelled':
      return 'outline'; // Gray outline
    default:
      return 'secondary';
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(amount);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const formatStatus = (status: string) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Payment form
const showPaymentDialog = ref(false);
const paymentForm = useForm({
  payment_amount: 0,
  payment_method: 'cash',
  payment_date: new Date().toISOString().split('T')[0],
  transaction_reference: '',
  notes: ''
});

const recordPayment = () => {
  paymentForm.post(route('bills.quick-payment', props.bill.bill_id), {
    onSuccess: () => {
      showPaymentDialog.value = false;
      paymentForm.reset();
    },
  });
};

// Set payment amount to full outstanding amount
const payFullAmount = () => {
  paymentForm.payment_amount = props.bill.outstanding_amount;
};

const getPaymentMethodDisplay = (method: string) => {
  const methods: Record<string, string> = {
    'cash': 'Cash',
    'bank_transfer': 'Bank Transfer',
    'check': 'Check',
    'credit_card': 'Credit Card',
    'online': 'Online Payment',
    'other': 'Other'
  };
  return methods[method] || method;
};
</script>

<template>
  <Head :title="`Bill ${bill.bill_number}`" />

  <AppLayout title="Bill Details" :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Bill Header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold tracking-tight">{{ bill.bill_number }}</h2>
          <p class="text-muted-foreground">
            Supplier: {{ bill.supplier.supplier_name }}
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <Badge :variant="getStatusVariant(bill.status)">
            {{ formatStatus(bill.status) }}
          </Badge>
          <Badge v-if="bill.is_overdue" variant="destructive">
            {{ bill.days_overdue }} days overdue
          </Badge>
          <Dialog v-if="bill.outstanding_amount > 0" v-model:open="showPaymentDialog">
            <DialogTrigger as-child>
              <Button>Record Payment</Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
              <DialogHeader>
                <DialogTitle>Record Payment</DialogTitle>
              </DialogHeader>
              <form @submit.prevent="recordPayment" class="space-y-4">
                <div>
                  <Label for="amount">Payment Amount</Label>
                  <div class="flex space-x-2">
                    <Input
                      id="amount"
                      v-model.number="paymentForm.payment_amount"
                      type="number"
                      step="0.01"
                      min="0.01"
                      :max="bill.outstanding_amount"
                      required
                    />
                    <Button 
                      type="button" 
                      variant="outline" 
                      size="sm"
                      @click="payFullAmount"
                    >
                      Full
                    </Button>
                  </div>
                  <p class="text-sm text-muted-foreground mt-1">
                    Outstanding: {{ formatCurrency(bill.outstanding_amount) }}
                  </p>
                </div>

                <div>
                  <Label for="method">Payment Method</Label>
                  <Select v-model="paymentForm.payment_method" required>
                    <SelectTrigger>
                      <SelectValue placeholder="Select payment method" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="cash">Cash</SelectItem>
                      <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                      <SelectItem value="check">Check</SelectItem>
                      <SelectItem value="credit_card">Credit Card</SelectItem>
                      <SelectItem value="online">Online Payment</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label for="date">Payment Date</Label>
                  <Input
                    id="date"
                    v-model="paymentForm.payment_date"
                    type="date"
                    required
                  />
                </div>

                <div>
                  <Label for="reference">Transaction Reference</Label>
                  <Input
                    id="reference"
                    v-model="paymentForm.transaction_reference"
                    placeholder="Optional transaction reference"
                  />
                </div>

                <div>
                  <Label for="notes">Notes</Label>
                  <Textarea
                    id="notes"
                    v-model="paymentForm.notes"
                    placeholder="Optional payment notes"
                    rows="2"
                  />
                </div>

                <div class="flex justify-end space-x-2">
                  <Button 
                    type="button" 
                    variant="outline" 
                    @click="showPaymentDialog = false"
                  >
                    Cancel
                  </Button>
                  <Button 
                    type="submit" 
                    :disabled="paymentForm.processing"
                  >
                    {{ paymentForm.processing ? 'Recording...' : 'Record Payment' }}
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <!-- Bill Information -->
        <Card>
          <CardHeader>
            <CardTitle>Bill Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Bill Number</p>
                <p class="font-medium">{{ bill.bill_number }}</p>
              </div>
              <div v-if="bill.supplier_invoice_number">
                <p class="text-sm font-medium text-muted-foreground">Invoice Number</p>
                <p class="font-medium">{{ bill.supplier_invoice_number }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Bill Date</p>
                <p class="font-medium">{{ formatDate(bill.bill_date) }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Due Date</p>
                <p class="font-medium" :class="bill.is_overdue ? 'text-destructive' : ''">
                  {{ formatDate(bill.due_date) }}
                </p>
              </div>
            </div>
            <div v-if="bill.notes" class="pt-4">
              <p class="text-sm font-medium text-muted-foreground">Notes</p>
              <p class="text-sm mt-1">{{ bill.notes }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Supplier Information -->
        <Card>
          <CardHeader>
            <CardTitle>Supplier Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Supplier</p>
              <p class="font-medium">{{ bill.supplier.supplier_name }}</p>
            </div>
            <div v-if="bill.supplier.contact_number" class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Contact</p>
                <p class="font-medium">{{ bill.supplier.contact_number }}</p>
              </div>
              <div v-if="bill.supplier.payment_terms">
                <p class="text-sm font-medium text-muted-foreground">Terms</p>
                <p class="font-medium">{{ bill.supplier.payment_terms }}</p>
              </div>
            </div>
            <div v-if="bill.supplier.email">
              <p class="text-sm font-medium text-muted-foreground">Email</p>
              <p class="font-medium">{{ bill.supplier.email }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Amount Breakdown -->
      <Card>
        <CardHeader>
          <CardTitle>Amount Breakdown</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div class="flex justify-between">
              <span>Subtotal</span>
              <span>{{ formatCurrency(bill.subtotal) }}</span>
            </div>
            <div v-if="bill.discount_amount > 0" class="flex justify-between text-green-600">
              <span>Discount</span>
              <span>-{{ formatCurrency(bill.discount_amount) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Tax</span>
              <span>{{ formatCurrency(bill.tax_amount) }}</span>
            </div>
            <Separator />
            <div class="flex justify-between font-semibold">
              <span>Total Amount</span>
              <span>{{ formatCurrency(bill.total_amount) }}</span>
            </div>
            <div v-if="bill.paid_amount > 0" class="flex justify-between text-green-600">
              <span>Paid Amount</span>
              <span>{{ formatCurrency(bill.paid_amount) }}</span>
            </div>
            <div v-if="bill.outstanding_amount > 0" class="flex justify-between font-semibold text-orange-600">
              <span>Outstanding</span>
              <span>{{ formatCurrency(bill.outstanding_amount) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Purchase Order Items (if available) -->
      <Card v-if="bill.purchase_order">
        <CardHeader>
          <CardTitle>Purchase Order Items</CardTitle>
          <p class="text-sm text-muted-foreground">
            From PO: {{ bill.purchase_order.po_number }} 
            ({{ formatDate(bill.purchase_order.order_date) }})
          </p>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Item</TableHead>
                <TableHead>Ordered</TableHead>
                <TableHead>Received</TableHead>
                <TableHead>Unit Price</TableHead>
                <TableHead>Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in bill.purchase_order.items" :key="item.purchase_order_item_id">
                <TableCell class="font-medium">
                  {{ item.ingredient.ingredient_name }}
                </TableCell>
                <TableCell>
                  {{ item.ordered_quantity }} {{ item.unit_of_measure }}
                </TableCell>
                <TableCell>
                  {{ item.received_quantity }} {{ item.unit_of_measure }}
                </TableCell>
                <TableCell>{{ formatCurrency(item.unit_price) }}</TableCell>
                <TableCell>{{ formatCurrency(item.total_price) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Payment History -->
      <Card v-if="bill.payments.length > 0">
        <CardHeader>
          <CardTitle>Payment History</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Reference</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Method</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Created By</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="payment in bill.payments" :key="payment.payment_id">
                <TableCell class="font-medium">
                  {{ payment.payment_reference }}
                </TableCell>
                <TableCell>{{ formatDate(payment.payment_date) }}</TableCell>
                <TableCell>{{ formatCurrency(payment.payment_amount) }}</TableCell>
                <TableCell>{{ getPaymentMethodDisplay(payment.payment_method) }}</TableCell>
                <TableCell>
                  <Badge :variant="payment.status === 'completed' ? 'default' : 'secondary'">
                    {{ formatStatus(payment.status) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ payment.created_by ? `${payment.created_by.first_name} ${payment.created_by.last_name}` : '-' }}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex justify-between">
        <Button variant="outline" as-child>
          <Link href="/bills">← Back to Bills</Link>
        </Button>
        
        <div class="space-x-2">
          <Button v-if="bill.status !== 'paid'" variant="outline" as-child>
            <Link :href="`/bills/${bill.bill_id}/edit`">Edit Bill</Link>
          </Button>
          <Button variant="outline">
            Print Bill
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>