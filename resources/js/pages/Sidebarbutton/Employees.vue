<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

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

import { Button } from '@/components/ui/button';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: '/employees',
    },
];

// control modal visibility
const showModal = ref(false);


</script>


<template>
    <Head title="Employees" />

    <AppLayout :breadcrumbs="breadcrumbs">
         <div class="p-3">
            <Button @click="showModal = true">Add Employees</Button>
        </div>

    <!-- Modal with separate form -->
    <Modal :show="showModal" @close="showModal = false">
      <h2 class="text-lg font-bold mb-4">Add Supplier</h2>
      <AddSupplier @cancel="showModal = false" @saved="showModal = false" />
    </Modal>


    <div class = "p-5">
               <Table>
                    <TableCaption>A list of your recent invoices.</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[100px]">ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Address</TableHead>
                                <TableHead>Catigories</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell class="font-medium">001</TableCell>
                                    <TableCell>Roger Perez</TableCell>
                                    <TableCell>San Pedro</TableCell>
                                    <TableCell>Fruits</TableCell>
                                    <TableCell>Null</TableCell>
                                    <TableCell class="text-right" >
                                          <Button variant="destructive" href="Menu">Disabale</Button>
                                       
                                         <Button variant="secondary" href="Menu" class = "m-1">Edit</Button>
                                         <Button as="a" href="Menu">View</Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
            </Table>
         </div>

         
    </AppLayout>
</template>
