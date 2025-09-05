<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from "vue";
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Welcome Back Admin!!',
        href: '/reorder',
    },
];
const products = ref([
  { id: 1, name: "Tomatoes", stock: 4 },
  { id: 2, name: "Onions", stock: 3 },
  { id: 3, name: "Cheese", stock: 2 },
  { id: 4, name: "Beef Patty", stock: 2 }
]);

const selectedItems = ref([]);

function toggleProduct(product) {
  const exists = selectedItems.value.find(i => i.id === product.id);
  if (exists) {
    selectedItems.value = selectedItems.value.filter(i => i.id !== product.id);
  } else {
    selectedItems.value.push({ ...product, quantity: 1 });
  }
}
</script>


<template>
    <Head title="Reorder"" />


    <AppLayout :breadcrumbs="breadcrumbs">
            <div>
                <Link :href="route('supplier.OrderedRequested')"> <Button>Request</Button> </Link>
            </div>

    
        


            <div class="p-6 max-w-5xl mx-auto">
                <h1 class="text-2xl font-bold mb-6">📦 Reorder</h1>

                <div class="grid md:grid-cols-3 gap-4">
           <div
                v-for="product in products"
                :key="product.id"
                @click="toggleProduct(product)"
                class="cursor-pointer border p-6 rounded-xl shadow-md hover:shadow-lg transition-all w-80 h-40 flex flex-col justify-between"
                :class="selectedItems.find(i => i.id === product.id) ? 'bg-green-100 border-green-400' : ''"
            >
                <h2 class="font-bold text-xl">{{ product.name }}</h2>
                <p class="text-base text-gray-600">Stock: {{ product.stock }}</p>
                <span class="text-sm text-red-500" v-if="product.stock < 5">⚠ Low stock</span>
            </div>

                </div>

                <!-- Selected Items -->
                <div v-if="selectedItems.length" class="mt-6 border rounded-lg p-4 bg-gray-50">
                <h2 class="font-semibold mb-4">📝 Selected Items to Order</h2>
                <div
                    v-for="item in selectedItems"
                    :key="item.id"
                    class="flex items-center gap-3 mb-3"
                >
                    <span class="flex-1">{{ item.name }}</span>
                    <input
                    type="number"
                    v-model="item.quantity"
                    min="1"
                    class="border rounded p-1 w-20"
                    />
                </div>
                <button
                    class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Send to Supplier
                </button>
                </div>
            </div>
      </AppLayout>
</template>
