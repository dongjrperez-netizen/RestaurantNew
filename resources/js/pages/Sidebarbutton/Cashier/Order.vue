<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue'

// Sample cart logic
const cart = ref([])

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Menu',
        href: '/menu',
    },
];

// Sample menu data (this can later come from Laravel via props)
const menu = [
  {
    category: "Appetizers",
    items: [
      {
        id: 1,
        name: "Spring Rolls",
        image: "/images/GrildChicken.png" ,
        description: "Crispy vegetable rolls served with sweet chili sauce."
      },
        {
        id: 2,
        name: "Spring Rolls",
        image: "https://via.placeholder.com/150",
        description: "Crispy vegetable rolls served with sweet chili sauce."
      },
      {
        id: 3,
        name: "Spring Rolls",
        image: "https://via.placeholder.com/150",
        description: "Crispy vegetable rolls served with sweet chili sauce."
      },
    {
        id: 4,
        name: "Spring Rolls",
        image: "https://via.placeholder.com/150",
        description: "Crispy vegetable rolls served with sweet chili sauce."
      },
        {
        id: 5,
        name: "Spring Rolls",
        image: "https://via.placeholder.com/150",
        description: "Crispy vegetable rolls served with sweet chili sauce."
      },
   
      // ...add more
    ]
  },
  {
    category: "Main Course",
    items: [
      {
        id: 6,
        name: "Grilled Chicken",
        image: "https://via.placeholder.com/150",
        description: "Juicy grilled chicken served with veggies."
      },
      {
        id: 7,
        name: "Beef Steak",
        image: "https://via.placeholder.com/150",
        description: "Tender steak grilled to perfection."
      },
      {
        id: 8,
        name: "Beef Steak",
        image: "https://via.placeholder.com/150",
        description: "Tender steak grilled to perfection."
      },
      {
        id: 9,
        name: "Beef Steak",
        image: "https://via.placeholder.com/150",
        description: "Tender steak grilled to perfection."
      },
     {
        id: 10,
        name: "Beef Steak",
        image: "https://via.placeholder.com/150",
        description: "Tender steak grilled to perfection."
      },
      // ...add more
    ]
  }
]

function addToCart(item: { name: any; }) {
  if (!item || !item.name) {
    console.error("Invalid item passed to addToCart:", item)
    return
  }
  alert(`${item.name} added to cart!`)
}


</script>

<template>
    <Head title="Menu" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>

             <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                
                 <div class="p-6">
                    <h1 class="text-2xl font-bold mb-6">Menu</h1>

                    <div v-for="section in menu" :key="section.category" class="mb-10">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">{{ section.category }}</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                        <div
                        v-for="item in section.items"
                        :key="item.id"
                        class="p-4 bg-white rounded-lg shadow hover:shadow-lg transition"
                        >
                        <label class="block text-lg font-semibold text-gray-800 mb-2">
                            {{ item.name }}
                        </label>

                        <img
                            :src="item.image"
                            :alt="item.name"
                            class="w-full h-32 object-cover rounded mb-2"
                        />

                        <p class="text-sm text-gray-600 mb-4">
                            {{ item.description }}
                        </p>

                        <button
                            @click="addToCart(item)"
                            class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                        >
                            Add to Cart
                        </button>
                        </div>
                    </div>
                    </div>
                </div>

            </div>
          
        </div>


       

    </AppLayout>
</template>
