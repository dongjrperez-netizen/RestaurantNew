<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

import { Check, ChevronsUpDown, Search } from "lucide-vue-next"

import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'



import { ref } from 'vue';
import Modal from '@/components/Modal.vue';
import AddStocks from '../Kitchens/AddStocks.vue';

import { Button } from '@/components/ui/button';
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxTrigger } from "@/components/ui/combobox"
import { cn } from "@/lib/utils"
const frameworks = [
  { value: "next.js", label: "Seafoods" },
  { value: "sveltekit", label: "Food Additive" },
  { value: "nuxt", label: "Condiments" },
  { value: "remix", label: "Fat" },
  { value: "astro", label: "Herb" },
]

const value = ref<typeof frameworks[0]>()


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kitchen Inventory',
        href: '/kitchen',
    },
];

// control modal visibility
const showModal = ref(false);
</script>


<template>
    <Head title="Kitchen Inventory" />

    <AppLayout :breadcrumbs="breadcrumbs">


        
      <div class="p-3">
            <!-- Flex container -->
            <div class="flex items-center justify-between">
                <!-- Left Button -->
                <Button @click="showModal = true">Add</Button>

                <!-- Right Combobox -->
                <div class="text-right">
                <Combobox v-model="value" by="label">
                    <ComboboxAnchor as-child>
                    <ComboboxTrigger as-child>
                        <Button variant="outline" class="justify-between">
                        {{ value?.label ?? 'Select Catigories' }}
                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                    </ComboboxTrigger>
                    </ComboboxAnchor>

                    <ComboboxList>
                    <div class="relative w-full max-w-sm items-center">
                        <ComboboxInput class="pl-9 focus-visible:ring-0 border-0 border-b rounded-none h-10" placeholder="Select Catigories..." />
                        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                        <Search class="size-4 text-muted-foreground" />
                        </span>
                    </div>

                    <ComboboxEmpty>
                        No Catigories found.
                    </ComboboxEmpty>

                    <ComboboxGroup>
                        <ComboboxItem
                        v-for="framework in frameworks"
                        :key="framework.value"
                        :value="framework"
                        >
                        {{ framework.label }}

                        <ComboboxItemIndicator>
                            <Check :class="cn('ml-auto h-4 w-4')" />
                        </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                    </ComboboxList>
                </Combobox>
                </div>
            </div>
        </div>

        

        
        <!-- Modal with separate form -->
        <Modal :show="showModal" @close="showModal = false">
        <h2 class="text-lg font-bold mb-4">Add Stocks</h2>
        <AddStocks @cancel="showModal = false" @saved="showModal = false" />
        </Modal>

        <div class = "p-5">
               <Table>
                    <TableCaption>A list of your recent invoices.</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[100px]">ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Catigories</TableHead>
                                <TableHead>Quantity</TableHead>
                                <TableHead>Expire Date</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell class="font-medium">001</TableCell>
                                    <TableCell>Fish</TableCell>
                                    <TableCell>Seafood</TableCell>
                                    <TableCell>30</TableCell>
                                    <TableCell>Null</TableCell>
                                    <TableCell class="text-right" >
                                         <Button as="a" href="Menu" class = "m-1">Edit</Button>
                                         <Button as="a" href="Menu">View</Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
            </Table>
         </div>

    </AppLayout>
</template>
