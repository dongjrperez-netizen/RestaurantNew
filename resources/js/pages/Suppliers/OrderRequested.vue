<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from "vue";

const orders = ref([
  {
    id: 101,
    status: "Pending",
    items: [
      { id: 1, name: "Tomatoes", quantity: 5 },
      { id: 2, name: "Onions", quantity: 3 }
    ]
  },
  {
    id: 102,
    status: "Pending",
    items: [
      { id: 3, name: "Cheese", quantity: 2 },
      { id: 4, name: "Beef Patty", quantity: 4 }
    ]
  },
  {
    id: 103,
    status: "Pending",
    items: [
      { id: 3, name: "Cheese", quantity: 2 },
      { id: 4, name: "Beef Patty", quantity: 4 }
    ]
  },
    {
    id: 104,
    status: "Pending",
    items: [
      { id: 3, name: "Cheese", quantity: 2 },
      { id: 4, name: "Beef Patty", quantity: 4 }
    ]
  },
  {
    id: 105,
    status: "Pending",
    items: [
      { id: 3, name: "Cheese", quantity: 2 },
      { id: 4, name: "Beef Patty", quantity: 4 }
    ]
  }
]);

const expanded = ref(null);
function toggleExpand(id: null) {
  expanded.value = expanded.value === id ? null : id;
}

</script>

<template>
      <div>
            <Link
              :href="route('reorder')"
              class="inline-block rounded px-5 py-2 text-base font-semibold text-[#1b1b18] transition dark:text-[#EDEDEC]"
          >
              Back
          </Link>
      </div>

          <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
          
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <div class="p-6 max-w-5xl mx-auto">
                <h1 class="text-2xl font-bold mb-6">📥 Incoming Orders</h1>

                <div
                  v-for="order in orders"
                  :key="order.id"
                  class="border rounded-lg shadow-sm p-4 mb-4 bg-white"
                >
                  <div class="flex justify-between items-center">
                    <div>
                      <span class="font-semibold">Order #{{ order.id }}</span>  
                      <span class="text-gray-500">({{ order.status }})</span>
                    </div>
                    <button
                      @click="toggleExpand(order.id)"
                      class="text-blue-600 hover:underline"
                    >
                      {{ expanded === order.id ? "Hide" : "View" }} Details
                    </button>
                  </div>

                  <div v-if="expanded === order.id" class="mt-3">
                    <table class="w-full border text-sm">
                      <thead>
                        <tr class="bg-gray-100">
                          <th class="border p-2 text-left">Product</th>
                          <th class="border p-2 text-left">Quantity</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="item in order.items" :key="item.id">
                          <td class="border p-2">{{ item.name }}</td>
                          <td class="border p-2">{{ item.quantity }}</td>
                        </tr>
                      </tbody>
                    </table>

                    <div class="flex gap-2 mt-4">
                      <button
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                      >
                        Approve
                      </button>
                      <button
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
                      >
                        Refuse
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </div>


         
</template>




